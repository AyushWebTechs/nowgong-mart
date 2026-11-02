<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:100'],
            'store_tagline' => ['nullable', 'string', 'max:150'],
            'store_phone' => ['nullable', 'regex:/^[6-9]\d{9}$/'],
            'whatsapp_number' => ['nullable', 'regex:/^[6-9]\d{9}$/'],
            'store_email' => ['nullable', 'email', 'max:150'],
            'store_address' => ['nullable', 'string', 'max:255'],
            'opening_hours' => ['nullable', 'string', 'max:150'],
            'accepting_orders' => ['boolean'],
            'service_pincodes' => ['nullable', 'string', 'max:255', 'regex:/^[\d,\s]*$/'],
            'service_area_note' => ['nullable', 'string', 'max:255'],
            'min_order_value' => ['required', 'decimal:0,2', 'min:0'],
            'delivery_fee' => ['required', 'decimal:0,2', 'min:0'],
            'free_delivery_above' => ['required', 'decimal:0,2', 'min:0'],
            'customer_cancel_allowed' => ['boolean'],
            'upi_id' => ['nullable', 'regex:/^[\w.\-]{2,256}@[a-zA-Z]{2,64}$/'],
            'upi_payee_name' => ['nullable', 'string', 'max:100'],
            'upi_qr' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'ice_cream_note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['upi_id.regex' => 'Enter a valid UPI ID, e.g. nowgongmart@okaxis.'];
    }
}
