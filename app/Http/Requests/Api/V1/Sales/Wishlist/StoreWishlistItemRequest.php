<?php

namespace App\Http\Requests\Api\V1\Sales\Wishlist;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWishlistItemRequest extends FormRequest
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
            'product_id' => [
                'required', 'integer',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'variant_id' => [
                'nullable', 'integer',
                Rule::exists('product_variants', 'id')
                    ->where('product_id', $this->product_id)
                    ->where('is_active', true),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'variant_id.exists' => 'تنوع انتخاب شده معتبر نیست یا متعلق به این محصول نمی‌باشد.',
        ];
    }
}
