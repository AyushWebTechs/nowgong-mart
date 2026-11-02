<table class="table">
    <thead>
        <tr><th>Order</th><th>Placed</th><th>Customer</th><th>Payment</th><th>Status</th><th class="text-right">Total</th></tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
        @forelse ($orders as $order)
            <tr>
                <td><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-brand-700 hover:underline">{{ $order->order_number }}</a></td>
                <td class="whitespace-nowrap">{{ $order->placed_at->format('d M, h:i A') }}</td>
                <td>{{ $order->customer_name }}<br><span class="text-xs text-gray-500">{{ $order->customer_phone }} · {{ $order->locality }}</span></td>
                <td>{{ $order->payment_method->label() }}<br><span class="badge {{ $order->payment_status->badgeClass() }}">{{ $order->payment_status->label() }}</span></td>
                <td>
                    <span class="badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
                    @if ($order->relationLoaded('deliveryPerson') && $order->deliveryPerson)
                        <br><span class="text-xs text-gray-500">🛵 {{ $order->deliveryPerson->name }}</span>
                    @endif
                </td>
                <td class="text-right font-semibold">{{ money($order->total) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="py-6 text-center text-gray-500">No orders found.</td></tr>
        @endforelse
    </tbody>
</table>
