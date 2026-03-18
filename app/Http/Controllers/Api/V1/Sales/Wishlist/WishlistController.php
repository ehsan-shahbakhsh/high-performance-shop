<?php

namespace App\Http\Controllers\Api\V1\Sales\Wishlist;

use App\Actions\Sales\Wishlist\CreateWishlistAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sales\Wishlist\{StoreWishlistRequest, UpdateWishlistRequest};
use App\Http\Resources\V1\Sales\WishlistResource;
use App\Http\Responses\ApiResponse;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Throwable;

class WishlistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $wishlists = $request->user()
            ->wishlists()
            ->withCount('items')
            ->orderByDesc('is_default')
            ->get();

        return ApiResponse::success(WishlistResource::collection($wishlists));
    }

    /**
     * Store a newly created resource in storage.
     * @throws Throwable
     */
    public function store(StoreWishlistRequest $request, CreateWishlistAction $action)
    {
        $wishlist = $action->execute(
            $request->user(),
            $request->validated('name'),
        );

        return ApiResponse::created(WishlistResource::make($wishlist), 'لیست علاقه‌مندی با موفقیت ایجاد شد.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWishlistRequest $request, Wishlist $wishlist)
    {
        $wishlist->update($request->validated());

        return ApiResponse::success(WishlistResource::make($wishlist), 'لیست علاقه‌مندی با موفقیت بروزرسانی شد.');
    }

    /**
     * Remove the specified resource from storage.
     * @throws Throwable
     */
    public function destroy(Wishlist $wishlist)
    {
        Gate::authorize('delete', $wishlist);

        $wishlist->delete();

        return ApiResponse::deleted('لیست علاقه‌مندی حذف شد.');
    }
}
