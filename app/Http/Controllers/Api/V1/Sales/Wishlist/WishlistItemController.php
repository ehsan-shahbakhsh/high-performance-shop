<?php

namespace App\Http\Controllers\Api\V1\Sales\Wishlist;

use App\Actions\Sales\Wishlist\AddWishlistItemAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sales\Wishlist\StoreWishlistItemRequest;
use App\Http\Resources\V1\Sales\WishlistItemResource;
use App\Http\Responses\ApiResponse;
use App\Models\{Wishlist, WishlistItem};
use Illuminate\Support\Facades\Gate;
use Throwable;

class WishlistItemController extends Controller
{
    public function index(Wishlist $wishlist)
    {
        Gate::authorize('view', $wishlist);

        $items = $wishlist->items()
            ->with(['product.media', 'variant'])
            ->latest('id')
            ->cursorPaginate();

        return ApiResponse::success(WishlistItemResource::collection($items));
    }

    /**
     * @throws Throwable
     */
    public function store(StoreWishlistItemRequest $request, Wishlist $wishlist, AddWishlistItemAction $action)
    {
        Gate::authorize('create', [WishlistItem::class, $wishlist]);

        $item = $action->execute(
            $wishlist,
            $request->validated('product_id'),
            $request->validated('variant_id'),
        );

        $item->load(['product.media', 'variant']);

        return ApiResponse::created(WishlistItemResource::make($item), 'محصول به لیست اضافه شد.');
    }

    public function destroy(Wishlist $wishlist, WishlistItem $item)
    {
        Gate::authorize('delete', $item);

        $item->delete();

        return ApiResponse::deleted('محصول از لیست حذف شد.');
    }
}
