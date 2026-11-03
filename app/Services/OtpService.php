<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use App\Services\Sms\SmsSender;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * One-time login codes for mobile numbers. Codes are stored only as an HMAC, expire after a few
 * minutes, allow a handful of tries, and every code ever sent stays in otp_codes as history.
 */
class OtpService
{
    public function __construct(private SmsSender $sms) {}

    /** Sends a fresh code and returns it (the caller only shows it in demo mode). */
    public function send(string $phone, ?string $ip = null, ?string $userAgent = null): string
    {
        $recent = OtpCode::where('phone', $phone)->where('created_at', '>=', now()->subHour())->latest('id')->get(['id', 'created_at']);

        if ($wait = $this->secondsUntilResend($recent->first())) {
            throw ValidationException::withMessages(['phone' => "Please wait {$wait} seconds before asking for a new code."]);
        }

        if ($recent->count() >= config('otp.max_per_hour')) {
            throw ValidationException::withMessages(['phone' => 'Too many codes requested for this number. Please try again in an hour.']);
        }

        $length = config('otp.length');
        $code = str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);

        $otp = OtpCode::create([
            'phone' => $phone,
            'user_id' => User::where('phone', $phone)->value('id'),
            'code_hash' => $this->hash($phone, $code),
            'driver' => config('otp.driver'),
            'ip_address' => $ip,
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 255) : null,
            'expires_at' => now()->addMinutes(config('otp.ttl_minutes')),
        ]);

        try {
            $this->sms->sendOtp($phone, $code);
        } catch (Throwable $e) {
            report($e);
            $otp->delete();

            throw ValidationException::withMessages(['phone' => 'We could not send the SMS right now. Please try again in a minute.']);
        }

        return $code;
    }

    /** Checks the latest code for this number; marks it verified or throws with the tries left. */
    public function verify(string $phone, string $code): OtpCode
    {
        $max = config('otp.max_attempts');

        // The attempt counter must be saved even when the code is wrong, so decide inside and throw outside.
        [$otp, $error] = DB::transaction(function () use ($phone, $code, $max) {
            $otp = OtpCode::where('phone', $phone)->latest('id')->lockForUpdate()->first();

            if (! $otp || $otp->verified_at || $otp->expires_at->isPast() || $otp->attempts >= $max) {
                return [null, 'This code has expired. Please request a new one.'];
            }

            $otp->increment('attempts');

            if (! hash_equals($otp->code_hash, $this->hash($phone, $code))) {
                $left = $max - $otp->attempts;

                return [null, $left > 0 ? "Incorrect code. {$left} ".($left === 1 ? 'try' : 'tries').' left.' : 'Incorrect code. Please request a new one.'];
            }

            $otp->update(['verified_at' => now()]);

            return [$otp, null];
        });

        if ($error) {
            throw ValidationException::withMessages(['code' => $error]);
        }

        return $otp;
    }

    public function secondsUntilResend(?OtpCode $latest): int
    {
        if (! $latest) {
            return 0;
        }

        return max(0, (int) ceil(config('otp.resend_seconds') - $latest->created_at->diffInSeconds(now())));
    }

    public function latestFor(string $phone): ?OtpCode
    {
        return OtpCode::where('phone', $phone)->latest('id')->first();
    }

    private function hash(string $phone, string $code): string
    {
        return hash_hmac('sha256', "{$phone}|{$code}", (string) config('app.key'));
    }
}
