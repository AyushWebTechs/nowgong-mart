<?php

namespace App\Support;

class Money
{
    public static function lineTotal(string $unitPrice, string $quantity): string
    {
        return self::round(bcmul($unitPrice, $quantity, 5));
    }

    public static function add(string ...$amounts): string
    {
        return array_reduce($amounts, fn (string $sum, string $amount) => bcadd($sum, $amount, 2), '0.00');
    }

    public static function round(string $amount): string
    {
        $offset = bccomp($amount, '0', 5) >= 0 ? '0.005' : '-0.005';

        return bcadd($amount, $offset, 2);
    }

    public static function format(string|int|float|null $amount): string
    {
        return '₹'.number_format((float) ($amount ?? 0), 2);
    }

    public static function qty(string|int|float|null $quantity): string
    {
        $formatted = rtrim(rtrim(number_format((float) $quantity, 3, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }
}
