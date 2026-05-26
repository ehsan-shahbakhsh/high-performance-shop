<?php

namespace App\Actions\Sales\Cart;

use App\Data\Sales\ApplyCouponResultData;
use App\Exceptions\BusinessException;
use App\Models\{Cart, Coupon};
use App\Services\Sales\DiscountService;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class ApplyCouponAction
{
    /**
     * Create a new class instance.
     */
    public function __construct(private DiscountService $discountService)
    {
    }

    /**
     * @throws BusinessException
     * @throws Throwable
     */
    public function execute(Cart $cart, string $couponCode): ApplyCouponResultData
    {
        if ($cart->coupon_id) {
            throw new BusinessException('یک کد تخفیف قبلاً روی این سبد خرید اعمال شده است.');
        }

        $coupon = Coupon::query()
            ->with('discount')
            ->where('code', $couponCode)
            ->first();
        if (!$coupon) {
            throw new BusinessException('کد تخفیف وارد شده معتبر نیست.');
        }

        if (!$this->discountService->isEligible($coupon->discount, $cart, $coupon)) {
            throw new BusinessException('این کد تخفیف برای سبد خرید شما قابل استفاده نیست.');
        }

        return DB::transaction(function () use ($cart, $coupon) {
            $cart = Cart::query()
                ->whereKey($cart->id)
                ->lockForUpdate()
                ->firstOrFail();

            $resultData = $this->discountService->evaluateBestDiscountScenario($cart, $coupon);

            if (in_array($coupon->discount_id, $resultData->appliedDiscounts)) {
                $cart->coupon_id = $coupon->id;
                $cart->total = ($cart->total + $cart->discount_total) - $resultData->totalDiscountAmount;
                $cart->discount_total = $resultData->totalDiscountAmount;
                $cart->save();

                return new ApplyCouponResultData(
                    message: 'کد تخفیف با موفقیت روی سبد خرید شما اعمال شد.',
                    cartSummary: $resultData,
                );
            }

            return new ApplyCouponResultData(
                message: 'تخفیف‌های فعلی جشنواره سود بیشتری از این کد برای شما دارند!',
                cartSummary: $resultData,
            );
        });
    }
}
