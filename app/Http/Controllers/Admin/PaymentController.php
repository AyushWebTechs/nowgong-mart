<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function verify(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:255']]);

        $this->orders->reviewPayment($payment, $request->user(), true, $data['note'] ?? null);

        return back()->with('success', 'Payment marked as received.');
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:255']]);

        $this->orders->reviewPayment($payment, $request->user(), false, $data['note']);

        return back()->with('success', 'Payment marked as not received.');
    }
}
