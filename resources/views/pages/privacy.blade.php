@extends('pages.layout')

@section('page')
    <p>{{ $settings->get('store_name') }} ("we") collects only the information needed to deliver your orders.</p>
    <h2 class="font-bold text-gray-900">What we collect</h2>
    <ul class="list-inside list-disc">
        <li>Your name, mobile number and (optionally) email address.</li>
        <li>Delivery addresses you save or enter at checkout.</li>
        <li>Your order history and UPI transaction references you submit.</li>
    </ul>
    <h2 class="font-bold text-gray-900">How we use it</h2>
    <p>To process and deliver orders, contact you about an order, verify payments and keep accounting records. Your phone number and address are shared only with the delivery person assigned to your order.</p>
    <h2 class="font-bold text-gray-900">What we do not do</h2>
    <p>We do not sell your data. We do not store card details or UPI PINs.</p>
    <h2 class="font-bold text-gray-900">Your choices</h2>
    <p>You can update your profile and delete saved addresses at any time. To delete your account, contact us using the details on the Contact page.</p>
@endsection
