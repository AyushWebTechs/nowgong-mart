<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** https://www.fast2sms.com - OTP route. */
class Fast2SmsSender implements SmsSender
{
    public function __construct(private string $key) {}

    public function sendOtp(string $phone, string $code): void
    {
        $response = Http::timeout(10)
            ->withHeaders(['authorization' => $this->key])
            ->asForm()
            ->post('https://www.fast2sms.com/dev/bulkV2', [
                'route' => 'otp',
                'variables_values' => $code,
                'numbers' => $phone,
                'flash' => 0,
            ]);

        if (! $response->successful() || $response->json('return') !== true) {
            throw new RuntimeException('Fast2SMS failed: '.$response->body());
        }
    }
}
