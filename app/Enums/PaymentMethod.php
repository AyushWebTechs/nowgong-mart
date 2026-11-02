<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case Upi = 'upi';

    public function label(): string
    {
        return match ($this) {
            self::Cod => 'Cash on Delivery',
            self::Upi => 'UPI',
        };
    }
}
