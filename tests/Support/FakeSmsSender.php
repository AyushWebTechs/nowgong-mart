<?php

namespace Tests\Support;

use App\Services\Sms\SmsSender;
use RuntimeException;

class FakeSmsSender implements SmsSender
{
    /** @var array<int, array{phone: string, code: string}> */
    public array $sent = [];

    public bool $fail = false;

    public function sendOtp(string $phone, string $code): void
    {
        if ($this->fail) {
            throw new RuntimeException('SMS gateway down');
        }

        $this->sent[] = ['phone' => $phone, 'code' => $code];
    }

    public function lastCode(): ?string
    {
        return end($this->sent)['code'] ?? null;
    }
}
