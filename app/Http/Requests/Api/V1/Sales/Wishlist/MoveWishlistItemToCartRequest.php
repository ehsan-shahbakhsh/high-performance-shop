<?php

namespace App\Http\Requests\Api\V1\Sales\Wishlist;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveWishlistItemToCartRequest extends FormRequest
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
        $wishlistItem = $this->route('item');

        return [
            'variant_id' => [
                'bail', 'nullable', 'integer',
                Rule::exists('product_variants', 'id')
                    ->where('is_active', true)
                    ->where('product_id', $wishlistItem->product_id),
            ],
        ];
    }
}
