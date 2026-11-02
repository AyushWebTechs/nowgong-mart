<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Customer = 'customer';
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin / Owner',
            self::Customer => 'Customer',
            self::Delivery => 'Delivery person',
        };
    }

    public function homeRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::Customer => 'home',
            self::Delivery => 'delivery.index',
        };
    }
}
