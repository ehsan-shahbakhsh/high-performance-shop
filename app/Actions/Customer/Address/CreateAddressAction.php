<?php

namespace App\Actions\Customer\Address;

use App\Data\Customer\AddressData;
use App\Exceptions\BusinessException;
use App\Models\{Address, User};
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CreateAddressAction
{
    /**
     * @throws Throwable
     */
    public function execute(User $user, AddressData $data): Address
    {
        return DB::transaction(function () use ($user, $data) {

            $maxLimit = config('commerce.address.max_per_user', 10);
            if ($user->addresses()->count() >= $maxLimit) {
                throw new BusinessException(
                    sprintf("شما به سقف مجاز ثبت آدرس (%s عدد) رسیده‌اید.", $maxLimit),
                    httpCode: Response::HTTP_UNPROCESSABLE_ENTITY,
                    errorCode: 'ADDRESS_LIMIT_REACHED',
                );
            }

            $isFirstAddress = $user->addresses()->doesntExist();
            $shouldBeDefault = $isFirstAddress || $data->isDefault;

            if ($shouldBeDefault) {
                $user->addresses()->where('is_default', true)->update(['is_default' => false]);
            }

            $address = $user->addresses()->create(array_merge(
                $data->toArray(),
                ['is_default' => $shouldBeDefault],
            ));

            return $address->fresh(['province', 'city']);
        });
    }
}
