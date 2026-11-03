<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** https://2factor.in - "custom OTP" SMS API. */
class TwoFactorSmsSender implements SmsSender
{
    public function __construct(private string $key, private ?string $template = null) {}

    public function sendOtp(string $phone, string $code): void
    {
        $url = "https://2factor.in/API/V1/{$this->key}/SMS/+91{$phone}/{$code}".($this->template ? '/'.rawurlencode($this->template) : '');
        $response = Http::timeout(10)->get($url);

        if (! $response->successful() || $response->json('Status') !== 'Success') {
            throw new RuntimeException('2Factor SMS failed: '.$response->body());
        }
    }
}
