<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $customers = User::where('role', UserRole::Customer)
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->withCount('orders')
            ->withSum(['orders as spent' => fn ($q) => $q->where('status', OrderStatus::Delivered)], 'total')
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.customers', ['customers' => $customers]);
    }

    public function toggle(User $user): RedirectResponse
    {
        abort_unless($user->isCustomer(), 404);

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        return back()->with('success', $user->is_active ? "{$user->name} activated." : "{$user->name} blocked.");
    }
}
