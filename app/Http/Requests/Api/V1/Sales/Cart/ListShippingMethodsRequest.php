<?php

namespace App\Http\Requests\Api\V1\Sales\Cart;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListShippingMethodsRequest extends FormRequest
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
            'address_id' => ['nullable', 'int', Rule::exists('addresses', 'id')->where('user_id', $this->user()->id)],
        ];
    }
}
