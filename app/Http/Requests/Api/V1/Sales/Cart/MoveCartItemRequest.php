<?php

namespace App\Http\Requests\Api\V1\Sales\Cart;

use App\Enums\CartStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveCartItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'destination_cart_id' => [
                'required',
                'ulid',
                Rule::exists('carts', 'id')
                    ->where('user_id', $this->user()->id)
                    ->where('status', CartStatus::Active),
            ],
        ];
    }
}
