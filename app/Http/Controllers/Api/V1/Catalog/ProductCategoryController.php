<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Catalog\ProductCategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $categories = Cache::rememberForever('categories_tree', function () {
            return ProductCategory::query()
                ->select('id', 'parent_id', 'name', 'slug', 'icon', 'position')
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->where('include_in_menu', true)
                ->with([
                    'children' => function (HasMany $query) {
                        $query->select('id', 'parent_id', 'name', 'slug', 'icon', 'position')
                            ->where('is_active', true)
                            ->where('include_in_menu', true);
                    },
                    'children.children' => function (HasMany $query) {
                        $query->select('id', 'parent_id', 'name', 'slug', 'icon', 'position')
                            ->where('is_active', true)
                            ->where('include_in_menu', true);
                    },
                ])
                ->orderBy('position')
                ->get();
        });

        return ApiResponse::success(ProductCategoryResource::collection($categories));
    }
}
