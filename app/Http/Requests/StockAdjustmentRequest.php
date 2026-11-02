<?php

namespace App\Http\Requests;

use App\Enums\MovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', Rule::exists('products', 'id')],
            'type' => ['required', Rule::in(array_map(fn (MovementType $t) => $t->value, MovementType::manual()))],
            'quantity' => ['required', 'decimal:0,3', 'min:0', 'max:9999999'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
