<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public static function addressRules(string $required = 'required'): array
    {
        return [
            'recipient_name' => [$required, 'string', 'max:100'],
            'phone' => [$required, 'regex:/^[6-9]\d{9}$/'],
            'address_line' => [$required, 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:150'],
            'locality' => [$required, 'string', 'max:100'],
            'pincode' => [$required, 'regex:/^\d{6}$/'],
        ];
    }

    public function rules(): array
    {
        return self::addressRules();
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a 10-digit mobile number.',
            'pincode.regex' => 'Enter a 6-digit pincode.',
        ];
    }
}
