<?php

namespace App\Enums;

enum MovementType: string
{
    case Initial = 'initial';
    case Restock = 'restock';
    case CounterSale = 'counter_sale';
    case Correction = 'correction';
    case OnlineOrder = 'online_order';
    case OrderCancelled = 'order_cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Initial => 'Opening stock',
            self::Restock => 'Restock / purchase',
            self::CounterSale => 'Counter sale',
            self::Correction => 'Stock count correction',
            self::OnlineOrder => 'Online order',
            self::OrderCancelled => 'Order cancelled (restored)',
        };
    }

    /** @return array<int, self> */
    public static function manual(): array
    {
        return [self::Restock, self::CounterSale, self::Correction];
    }
}
