<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class DeliveryUserController extends Controller
{
    public function index(): View
    {
        return view('admin.delivery-users', [
            'users' => User::where('role', UserRole::Delivery)
                ->withCount([
                    'assignedOrders as open_orders_count' => fn ($q) => $q->whereIn('status', [OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::OutForDelivery]),
                    'assignedOrders as delivered_count' => fn ($q) => $q->where('status', OrderStatus::Delivered),
                ])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^[6-9]\d{9}$/', 'unique:users,phone'],
            'password' => ['required', Password::min(8)],
        ]);

        $user = new User($data);
        $user->forceFill(['role' => UserRole::Delivery, 'is_active' => true])->save();

        return back()->with('success', "Delivery account created for {$user->name}. Share the password with them privately.");
    }

    public function toggle(User $user): RedirectResponse
    {
        abort_unless($user->isDelivery(), 404);

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        return back()->with('success', $user->is_active ? "{$user->name} activated." : "{$user->name} deactivated.");
    }
}
