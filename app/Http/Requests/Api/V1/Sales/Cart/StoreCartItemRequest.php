<?php

namespace App\Http\Requests\Api\V1\Sales\Cart;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Closure;

class StoreCartItemRequest extends FormRequest
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
            'variant_id' => [
                'bail', 'required', 'integer',
                Rule::exists('product_variants', 'id')
                    ->where('is_active', true),
                function (string $attribute, mixed $value, Closure $fail) {
                    $variant = ProductVariant::with('product:id,is_active,status,published_at')
                        ->find($value);

                    if (!$variant->product->isAvailable()) {
                        $fail('این محصول در حال حاضر غیرفعال است و امکان خرید آن وجود ندارد.');
                    }
                },
            ],

            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.min' => 'تعداد محصول باید حداقل ۱ باشد.',
            'variant_id.exists' => 'تنوع انتخاب شده معتبر نیست.',
        ];
    }
}
