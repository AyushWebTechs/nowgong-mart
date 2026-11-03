<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Preparing => 'Packing',
            self::OutForDelivery => 'Out for delivery',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    /** What the customer sees on the tracking page while the order is in this status. */
    public function customerMessage(): string
    {
        return match ($this) {
            self::Pending => 'Order placed. Waiting for the shop to accept it.',
            self::Confirmed => 'The shop has accepted your order.',
            self::Preparing => 'Your items are being packed.',
            self::OutForDelivery => 'Your order is on the way!',
            self::Delivered => 'Delivered. Enjoy!',
            self::Cancelled => 'This order was cancelled.',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800',
            self::Confirmed => 'bg-sky-100 text-sky-800',
            self::Preparing => 'bg-indigo-100 text-indigo-800',
            self::OutForDelivery => 'bg-purple-100 text-purple-800',
            self::Delivered => 'bg-emerald-100 text-emerald-800',
            self::Cancelled => 'bg-rose-100 text-rose-800',
        };
    }

    /** @return array<int, self> */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Preparing, self::Cancelled],
            self::Preparing => [self::OutForDelivery, self::Cancelled],
            self::OutForDelivery => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->nextStatuses(), true);
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Delivered, self::Cancelled], true);
    }

    /** @return array<int, self> */
    public static function progression(): array
    {
        return [self::Pending, self::Confirmed, self::Preparing, self::OutForDelivery, self::Delivered];
    }
}
