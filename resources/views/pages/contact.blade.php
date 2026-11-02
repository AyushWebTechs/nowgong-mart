@extends('pages.layout')

@section('page')
    <dl class="grid gap-3 sm:grid-cols-[10rem_1fr]">
        <dt class="font-semibold text-gray-900">Store</dt><dd>{{ $settings->get('store_name') }}</dd>
        <dt class="font-semibold text-gray-900">Address</dt><dd>{{ $settings->get('store_address') }}</dd>
        <dt class="font-semibold text-gray-900">Hours</dt><dd>{{ $settings->get('opening_hours') }}</dd>
        @if ($settings->get('store_phone'))
            <dt class="font-semibold text-gray-900">Phone</dt><dd><a class="text-brand-700 underline" href="tel:+91{{ $settings->get('store_phone') }}">{{ $settings->get('store_phone') }}</a></dd>
        @endif
        @if ($settings->get('whatsapp_number'))
            <dt class="font-semibold text-gray-900">WhatsApp</dt><dd><a class="text-brand-700 underline" href="https://wa.me/91{{ $settings->get('whatsapp_number') }}" target="_blank" rel="noopener">{{ $settings->get('whatsapp_number') }}</a></dd>
        @endif
        @if ($settings->get('store_email'))
            <dt class="font-semibold text-gray-900">Email</dt><dd><a class="text-brand-700 underline" href="mailto:{{ $settings->get('store_email') }}">{{ $settings->get('store_email') }}</a></dd>
        @endif
    </dl>
@endsection
