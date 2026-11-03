<?php

return [

    /*
    | How login codes are delivered: "log" (writes the code to the log - local/demo only),
    | "2factor" (2factor.in) or "fast2sms" (fast2sms.com).
    */
    'driver' => env('OTP_DRIVER', 'log'),

    /*
    | Demo mode shows the code on the verify screen so the site can be tried without an SMS
    | provider. Anyone can then log in as any customer number - keep it off once SMS works.
    | Defaults to on while the "log" driver is used, since nobody could receive a code otherwise.
    */
    'demo' => (bool) env('OTP_DEMO', env('OTP_DRIVER', 'log') === 'log'),

    'length' => 6,
    'ttl_minutes' => 5,
    'max_attempts' => 5,
    'resend_seconds' => 30,
    'max_per_hour' => 5,

    '2factor' => [
        'key' => env('TWOFACTOR_API_KEY'),
        'template' => env('TWOFACTOR_TEMPLATE'),
    ],

    'fast2sms' => [
        'key' => env('FAST2SMS_API_KEY'),
    ],

];
