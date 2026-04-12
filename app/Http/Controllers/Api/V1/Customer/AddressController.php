<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Actions\Customer\Address\{CreateAddressAction, UpdateAddressAction};
use App\Data\Customer\AddressData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\Address\{StoreAddressRequest, UpdateAddressRequest};
use App\Http\Resources\V1\Customer\AddressResource;
use App\Http\Responses\ApiResponse;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Throwable;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $addresses = $request->user()
            ->addresses()
            ->with(['province', 'city'])
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return ApiResponse::success(AddressResource::collection($addresses));
    }

    /**
     * Store a newly created resource in storage.
     * @throws Throwable
     */
    public function store(StoreAddressRequest $request, CreateAddressAction $action)
    {
        $address = $action->execute($request->user(), AddressData::fromStoreRequest($request));

        return ApiResponse::created(AddressResource::make($address), 'آدرس جدید شما با موفقیت ثبت شد.');
    }

    /**
     * Update the specified resource in storage.
     * @throws Throwable
     */
    public function update(UpdateAddressRequest $request, Address $address, UpdateAddressAction $action)
    {
        $address = $action->execute($request->user(), $address, AddressData::fromUpdateRequest($request));

        return ApiResponse::success(AddressResource::make($address), 'اطلاعات آدرس بروزرسانی شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Address $address)
    {
        Gate::authorize('delete', $address);

        $address->delete();

        return ApiResponse::deleted('آدرس مورد نظر از دفترچه آدرس شما حذف شد.');
    }
}
