<?php

namespace App\Services\Sms;

interface SmsSender
{
    /** Send a login code to a 10-digit Indian mobile number. Throws on delivery failure. */
    public function sendOtp(string $phone, string $code): void;
}
