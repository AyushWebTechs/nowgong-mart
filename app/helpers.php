<?php

use App\Support\Money;

if (! function_exists('money')) {
    function money(string|int|float|null $amount): string
    {
        return Money::format($amount);
    }
}

if (! function_exists('qty')) {
    function qty(string|int|float|null $quantity): string
    {
        return Money::qty($quantity);
    }
}
