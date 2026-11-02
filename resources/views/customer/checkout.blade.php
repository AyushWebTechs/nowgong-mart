@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
    <h1 class="mb-4 text-xl font-bold">Checkout</h1>

    <form method="POST" action="{{ route('checkout.store') }}" class="grid gap-6 lg:grid-cols-3">
        @csrf
        <div class="space-y-6 lg:col-span-2">
            <section class="card p-4">
                <h2 class="mb-3 font-bold">1. Delivery address</h2>
                @php($pincodes = $settings->servicePincodes())
                @if ($pincodes)
                    <p class="mb-3 text-xs text-gray-500">{{ $settings->get('service_area_note') }} Pincode{{ count($pincodes) > 1 ? 's' : '' }}: {{ implode(', ', $pincodes) }}</p>
                @endif

                @if ($addresses->isNotEmpty())
                    <div class="space-y-2">
                        @foreach ($addresses as $address)
                            <label class="flex cursor-pointer gap-3 rounded-lg border p-3 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                                <input type="radio" name="address_id" value="{{ $address->id }}" @checked(old('address_id', $addresses->first()->id) == $address->id)>
                                <span class="text-sm">
                                    <span class="font-semibold">{{ $address->recipient_name }}</span> · {{ $address->phone }}<br>
                                    <span class="text-gray-600">{{ $address->oneLine() }}</span>
                                </span>
                            </label>
                        @endforeach
                        <label class="flex cursor-pointer gap-3 rounded-lg border p-3 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                            <input type="radio" name="address_id" value="" @checked(old('address_id') === '')>
                            <span class="text-sm font-semibold">Use a new address</span>
                        </label>
                    </div>
                @endif

                <div class="mt-4 grid gap-3 sm:grid-cols-2 {{ $addresses->isNotEmpty() ? 'border-t pt-4' : '' }}">
                    @if ($addresses->isNotEmpty())
                        <p class="text-xs text-gray-500 sm:col-span-2">Fill this only for a new address.</p>
                    @endif
                    <div>
                        <label class="label" for="recipient_name">Name</label>
                        <input class="input" id="recipient_name" name="recipient_name" value="{{ old('recipient_name', auth()->user()->name) }}">
                    </div>
                    <div>
                        <label class="label" for="phone">Mobile number</label>
                        <input class="input" id="phone" name="phone" inputmode="numeric" maxlength="10" value="{{ old('phone', auth()->user()->phone) }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label" for="address_line">House no., street</label>
                        <input class="input" id="address_line" name="address_line" value="{{ old('address_line') }}">
                    </div>
                    <div>
                        <label class="label" for="landmark">Landmark (optional)</label>
                        <input class="input" id="landmark" name="landmark" value="{{ old('landmark') }}">
                    </div>
                    <div>
                        <label class="label" for="locality">Locality / mohalla</label>
                        <input class="input" id="locality" name="locality" value="{{ old('locality') }}">
                    </div>
                    <div>
                        <label class="label" for="pincode">Pincode</label>
                        <input class="input" id="pincode" name="pincode" inputmode="numeric" maxlength="6" value="{{ old('pincode', $pincodes[0] ?? '') }}">
                    </div>
                    <label class="flex items-center gap-2 self-end text-sm">
                        <input type="hidden" name="save_address" value="0">
                        <input type="checkbox" name="save_address" value="1" @checked(old('save_address', true))> Save this address
                    </label>
                </div>
            </section>

            <section class="card p-4">
                <h2 class="mb-3 font-bold">2. Payment</h2>
                <div class="space-y-2">
                    @foreach ($paymentMethods as $method)
                        <label class="flex cursor-pointer gap-3 rounded-lg border p-3 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                            <input type="radio" name="payment_method" value="{{ $method->value }}" @checked(old('payment_method', 'cod') === $method->value)>
                            <span class="text-sm">
                                <span class="font-semibold">{{ $method->label() }}</span><br>
                                <span class="text-gray-600">
                                    {{ $method === \App\Enums\PaymentMethod::Cod ? 'Pay cash to the delivery person.' : 'Pay to the shop UPI ID, then enter the transaction reference (UTR).' }}
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>

                <div class="mt-4 rounded-lg bg-gray-50 p-3 text-sm">
                    <p class="font-semibold">Paying by UPI?</p>
                    @if ($settings->get('upi_id'))
                        <p>Pay <strong>{{ money($summary['total']) }}</strong> to <strong class="select-all">{{ $settings->get('upi_id') }}</strong>{{ $settings->get('upi_payee_name') ? ' ('.$settings->get('upi_payee_name').')' : '' }}.</p>
                        @if ($settings->get('upi_qr_path'))
                            <img src="{{ Storage::disk('public')->url($settings->get('upi_qr_path')) }}" alt="Shop UPI QR code" class="mt-2 h-40 w-40 rounded border bg-white">
                        @endif
                    @else
                        <p class="text-gray-600">The shop has not set up its UPI ID yet. Please choose Cash on Delivery.</p>
                    @endif
                    <label class="label mt-3" for="transaction_reference">UPI transaction reference / UTR (optional now, you can add it later)</label>
                    <input class="input" id="transaction_reference" name="transaction_reference" value="{{ old('transaction_reference') }}" autocomplete="off">
                    <p class="mt-1 text-xs text-gray-500">Your order stays “payment pending” until the shop checks the payment in its bank/UPI app. Screenshots are not accepted as proof.</p>
                </div>
            </section>

            <section class="card p-4">
                <label class="label" for="notes">Notes for the shop (optional)</label>
                <textarea class="input" id="notes" name="notes" rows="2" maxlength="500" placeholder="e.g. call before arriving, ring the bell twice">{{ old('notes') }}</textarea>
            </section>
        </div>

        <aside class="card h-fit space-y-2 p-4 text-sm">
            <h2 class="text-base font-bold">Your order</h2>
            @foreach ($summary['lines'] as $line)
                <div class="flex justify-between gap-2">
                    <span>{{ $line['product']->name }} × {{ qty($line['item']->quantity) }}</span>
                    <span>{{ money($line['line_total']) }}</span>
                </div>
            @endforeach
            <div class="flex justify-between border-t pt-2"><span>Subtotal</span><span>{{ money($summary['subtotal']) }}</span></div>
            <div class="flex justify-between"><span>Delivery fee</span><span>{{ (float) $summary['delivery_fee'] ? money($summary['delivery_fee']) : 'FREE' }}</span></div>
            <div class="flex justify-between border-t pt-2 text-base font-bold"><span>Total</span><span>{{ money($summary['total']) }}</span></div>
            <button class="btn btn-primary mt-2 w-full">Place order</button>
            <p class="text-xs text-gray-500">By placing the order you agree to our <a class="underline" href="{{ route('pages.show', 'terms') }}">terms</a> and <a class="underline" href="{{ route('pages.show', 'refunds') }}">cancellation policy</a>.</p>
        </aside>
    </form>
@endsection
