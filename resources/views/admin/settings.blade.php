@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
    <h1 class="mb-4 text-xl font-bold">Store settings</h1>

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="max-w-3xl space-y-6">
        @csrf @method('PUT')

        <section class="card grid gap-4 p-5 sm:grid-cols-2">
            <h2 class="font-bold sm:col-span-2">Store details</h2>
            <div><label class="label" for="store_name">Store name</label><input class="input" id="store_name" name="store_name" value="{{ old('store_name', $settings->get('store_name')) }}" required></div>
            <div><label class="label" for="store_tagline">Tagline</label><input class="input" id="store_tagline" name="store_tagline" value="{{ old('store_tagline', $settings->get('store_tagline')) }}"></div>
            <div><label class="label" for="store_phone">Phone (10 digits)</label><input class="input" id="store_phone" name="store_phone" inputmode="numeric" maxlength="10" value="{{ old('store_phone', $settings->get('store_phone')) }}"></div>
            <div><label class="label" for="whatsapp_number">WhatsApp number (10 digits)</label><input class="input" id="whatsapp_number" name="whatsapp_number" inputmode="numeric" maxlength="10" value="{{ old('whatsapp_number', $settings->get('whatsapp_number')) }}"></div>
            <div><label class="label" for="store_email">Email</label><input class="input" id="store_email" type="email" name="store_email" value="{{ old('store_email', $settings->get('store_email')) }}"></div>
            <div><label class="label" for="opening_hours">Opening hours</label><input class="input" id="opening_hours" name="opening_hours" value="{{ old('opening_hours', $settings->get('opening_hours')) }}"></div>
            <div class="sm:col-span-2"><label class="label" for="store_address">Shop address</label><input class="input" id="store_address" name="store_address" value="{{ old('store_address', $settings->get('store_address')) }}"></div>
        </section>

        <section class="card grid gap-4 p-5 sm:grid-cols-2">
            <h2 class="font-bold sm:col-span-2">Orders & delivery</h2>
            <label class="flex items-center gap-2 text-sm sm:col-span-2">
                <input type="hidden" name="accepting_orders" value="0">
                <input type="checkbox" name="accepting_orders" value="1" @checked(old('accepting_orders', $settings->bool('accepting_orders')))>
                <span><strong>Accepting online orders</strong> (untick when the shop is closed or too busy)</span>
            </label>
            <label class="flex items-center gap-2 text-sm sm:col-span-2">
                <input type="hidden" name="customer_cancel_allowed" value="0">
                <input type="checkbox" name="customer_cancel_allowed" value="1" @checked(old('customer_cancel_allowed', $settings->bool('customer_cancel_allowed')))>
                Customers may cancel their own order before it is confirmed
            </label>
            <div><label class="label" for="service_pincodes">Delivery pincodes (comma separated; blank = any)</label><input class="input" id="service_pincodes" name="service_pincodes" value="{{ old('service_pincodes', $settings->get('service_pincodes')) }}"></div>
            <div><label class="label" for="service_area_note">Service area note</label><input class="input" id="service_area_note" name="service_area_note" value="{{ old('service_area_note', $settings->get('service_area_note')) }}"></div>
            <div><label class="label" for="min_order_value">Minimum order (₹)</label><input class="input" id="min_order_value" name="min_order_value" inputmode="decimal" value="{{ old('min_order_value', $settings->get('min_order_value')) }}" required></div>
            <div><label class="label" for="delivery_fee">Delivery fee (₹)</label><input class="input" id="delivery_fee" name="delivery_fee" inputmode="decimal" value="{{ old('delivery_fee', $settings->get('delivery_fee')) }}" required></div>
            <div><label class="label" for="free_delivery_above">Free delivery above (₹, 0 = never)</label><input class="input" id="free_delivery_above" name="free_delivery_above" inputmode="decimal" value="{{ old('free_delivery_above', $settings->get('free_delivery_above')) }}" required></div>
            <div><label class="label" for="ice_cream_note">Ice cream delivery note</label><input class="input" id="ice_cream_note" name="ice_cream_note" value="{{ old('ice_cream_note', $settings->get('ice_cream_note')) }}"></div>
        </section>

        <section class="card grid gap-4 p-5 sm:grid-cols-2">
            <h2 class="font-bold sm:col-span-2">UPI (manual verification)</h2>
            <div><label class="label" for="upi_id">Store UPI ID</label><input class="input" id="upi_id" name="upi_id" value="{{ old('upi_id', $settings->get('upi_id')) }}" placeholder="nowgongmart@okaxis"></div>
            <div><label class="label" for="upi_payee_name">Payee name shown in UPI apps</label><input class="input" id="upi_payee_name" name="upi_payee_name" value="{{ old('upi_payee_name', $settings->get('upi_payee_name')) }}"></div>
            <div>
                <label class="label" for="upi_qr">UPI QR code image</label>
                <input class="input" id="upi_qr" type="file" name="upi_qr" accept="image/jpeg,image/png,image/webp">
                @if ($settings->get('upi_qr_path'))
                    <img src="{{ Storage::disk('public')->url($settings->get('upi_qr_path')) }}" alt="Current UPI QR" class="mt-2 h-28 w-28 rounded border">
                @endif
            </div>
            <p class="text-xs text-gray-500">Use only the shop's own verified UPI ID. Customers see this at checkout. Never put bank passwords or payment gateway secrets here.</p>
        </section>

        <button class="btn btn-primary">Save settings</button>
    </form>
@endsection
