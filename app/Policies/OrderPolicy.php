<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin()
            || ($user->isCustomer() && $order->user_id === $user->id)
            || ($user->isDelivery() && $order->delivery_person_id === $user->id);
    }

    public function deliver(User $user, Order $order): bool
    {
        return $user->isDelivery() && $order->delivery_person_id === $user->id;
    }
}
