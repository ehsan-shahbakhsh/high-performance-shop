<?php

namespace App\Actions\Customer\Address;

use App\Data\Customer\AddressData;
use Illuminate\Support\Facades\DB;
use App\Models\{User, Address};
use Throwable;

class UpdateAddressAction
{
    /**
     * @throws Throwable
     */
    public function execute(User $user, Address $address, AddressData $data): Address
    {
       DB::transaction(function () use ($user, $address, $data) {
           if ($data->isDefault) {
               $user->addresses()->where('is_default', true)->update(['is_default' => false]);
           }

           $address->update($data->toArray());
       });

        return $address->fresh(['province', 'city']);
    }
}
