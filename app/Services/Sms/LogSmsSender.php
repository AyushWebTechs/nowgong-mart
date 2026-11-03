<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/** Local / demo driver: no SMS leaves the server, the code only goes to the log. */
class LogSmsSender implements SmsSender
{
    public function sendOtp(string $phone, string $code): void
    {
        Log::info("OTP for {$phone}: {$code}");
    }
}
