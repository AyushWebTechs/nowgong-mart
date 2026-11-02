@extends('pages.layout')

@section('page')
    <ul class="list-inside list-disc space-y-1">
        <li>Orders are accepted for delivery within our service area only ({{ implode(', ', $settings->servicePincodes()) ?: 'Nowgong' }}).</li>
        <li>Prices and stock are confirmed when you place the order. If an item becomes unavailable, we will contact you before substituting anything.</li>
        <li>Minimum order value: {{ money($settings->get('min_order_value')) }}. Delivery fee: {{ money($settings->get('delivery_fee')) }}@if ((float) $settings->get('free_delivery_above') > 0), free above {{ money($settings->get('free_delivery_above')) }}@endif.</li>
        <li>Cash on Delivery: please keep the exact amount ready where possible.</li>
        <li>UPI: an order is treated as paid only after the shop verifies the payment in its bank/UPI app.</li>
        <li>Ice cream is perishable. Please be available to receive it; we cannot accept returns of melted products caused by delayed receipt.</li>
        <li>Store hours: {{ $settings->get('opening_hours') }}.</li>
    </ul>
@endsection
