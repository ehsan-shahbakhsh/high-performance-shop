<?php

namespace App\Http\Requests\Api\V1\Sales\Cart;

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('is_active', true),
            ],

            'variant_id' => [
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id')
                    ->where('product_id', $this->product_id)
                    ->where('is_active', true),
                function ($attribute, $value, $fail) {
                    $product = Product::query()->find($this->product_id);

                    if (!$product) {
                        return;
                    }

                    if ($product->type === ProductType::Variable && empty($value)) {
                        $fail('برای این محصول باید یک تنوع (رنگ/سایز) انتخاب کنید.');
                    }

                    if ($product->type === ProductType::Simple && !empty($value)) {
                        $fail('این محصول دارای تنوع نیست.');
                    }

                    $variantsExists = $product->variants()->exists();

                    if ($variantsExists && empty($value)) {
                        $fail('برای این محصول باید یک تنوع انتخاب کنید.');
                    }

                    if (!$variantsExists && !empty($value)) {
                        $fail('این محصول دارای تنوع نیست.');
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
            'variant_id.exists' => 'تنوع انتخاب شده معتبر نیست یا متعلق به این محصول نمی‌باشد.',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $product = Product::query()->find($this->product_id);

                if (!$product) {
                    return;
                }

                $hasVariants = $product->variants()->exists();
                $variantId = $this->variant_id;

                if ($product->type === ProductType::Variable && !filled($variantId)) {
                    $validator->errors()->add('variant_id', 'برای این محصول باید یک تنوع (رنگ/سایز) انتخاب کنید.');
                } elseif ($product->type === ProductType::Simple && filled($variantId)) {
                    $validator->errors()->add('variant_id', 'این محصول دارای تنوع نیست.');
                } elseif ($hasVariants && !filled($variantId)) {
                    $validator->errors()->add('variant_id', 'برای این محصول باید یک تنوع انتخاب کنید.');
                } elseif (!$hasVariants && filled($variantId)) {
                    $validator->errors()->add('variant_id', 'این محصول دارای تنوع نیست.');
                }
            }
        ];
    }
}
