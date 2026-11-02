<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $usingSaved = $this->filled('address_id');

        return [
            'address_id' => ['nullable', Rule::exists('addresses', 'id')->where('user_id', $this->user()->id)],
            ...AddressRequest::addressRules($usingSaved ? 'nullable' : 'required'),
            'save_address' => ['boolean'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'transaction_reference' => ['nullable', 'regex:/^[A-Za-z0-9]{6,35}$/'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a 10-digit mobile number.',
            'pincode.regex' => 'Enter a 6-digit pincode.',
            'transaction_reference.regex' => 'UPI reference should be 6-35 letters/numbers (the UTR shown in your UPI app).',
        ];
    }
}
