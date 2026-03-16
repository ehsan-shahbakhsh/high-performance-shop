<?php

namespace App\Data\Sales;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\RequiredWithout;
use Spatie\LaravelData\Attributes\Validation\Nullable;

class AddItemToCartData extends Data
{
    public function __construct(
        #[Nullable, RequiredWithout('session_id')]
        public ?int    $userId,

        #[Nullable, RequiredWithout('user_id')]
        public ?string $sessionId,

        public int     $productId,
        public ?int    $variantId,
        public int     $quantity,
    )
    {
    }

    public static function messages(...$args): array
    {
        return [
            'user_id.required_without' => 'برای افزودن محصول باید کاربر احراز شده یا شناسه نشست ارسال شود.',
            'session_id.required_without' => 'شناسه نشست (Session ID) یافت نشد. لطفاً یک شناسه یکتا ایجاد کرده و در هدر درخواست ارسال کنید.',
        ];
    }
}
