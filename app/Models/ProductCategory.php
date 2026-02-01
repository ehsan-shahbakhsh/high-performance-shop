<?php

namespace App\Models;

use App\Exceptions\BusinessException;
use Database\Factories\ProductCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SolutionForest\FilamentTree\Concern\ModelTree;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProductCategory extends Model implements HasMedia
{
    use InteractsWithMedia;
    /** @use HasFactory<ProductCategoryFactory> */
    use HasFactory;
    use Sluggable;
    use ModelTree;

    protected $fillable = [
        'parent_id', 'path', 'level', 'name', 'slug',
        'icon', 'is_active', 'is_featured',
        'include_in_menu', 'position',
        'seo_title', 'seo_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'include_in_menu' => 'boolean',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('category_cover')
            ->singleFile()
            ->useDisk('public');
    }

    protected static function booted(): void
    {
        static::created(function (self $category) {
            if ($category->parent_id) {
                $parent = static::find($category->parent_id);

                if (!$parent || is_null($parent->path)) {
                    throw new \RuntimeException("Parent category #{$category->parent_id} has a missing or invalid path.");
                }

                $category->path = $parent->path . $category->id . '/';

                $category->level = $parent->level + 1;
            } else {
                $category->path = '/' . $category->id . '/';
                $category->level = 0;
            }

            $category->saveQuietly();

            self::clearCache();
        });

        static::updated(function (self $category) {
            if ($category->wasChanged('parent_id')) {
                $oldPath = $category->getOriginal('path');
                $oldLevel = $category->getOriginal('level');

                if ($category->parent_id) {
                    $parent = static::find($category->parent_id);

                    if (!$parent || is_null($parent->path)) {
                        throw new RuntimeException("Parent category #{$category->parent_id} has invalid path.");
                    }

                    $newPath = $parent->path . $category->id . '/';
                    $newLevel = $parent->level + 1;
                } else {
                    $newPath = '/' . $category->id . '/';
                    $newLevel = 0;
                }

                $category->path = $newPath;
                $category->level = $newLevel;
                $category->saveQuietly();

                if (!empty($oldPath)) {
                    $levelDelta = $newLevel - $oldLevel;

                    DB::table('product_categories')
                        ->where('path', 'LIKE', $oldPath . '%')
                        ->where('id', '!=', $category->id)
                        ->update([
                            'path' => DB::raw("REPLACE(path, '{$oldPath}', '{$newPath}')"),
                            'level' => DB::raw("level + ({$levelDelta})")
                        ]);
                }
            }

            self::clearCache();
        });

        static::deleting(function (self $category) {
            if ($category->children()->exists()) {
                throw new BusinessException("نمیتوانید این دسته را حذف کنید چون دارای زیرمجموعه است. ابتدا زیرمجموعه‌ها را جابجا یا حذف کنید.");
            }
        });
    }

    public static function clearCache(): void
    {
        Cache::forget('categories_tree');
        Cache::forget('categories_flat');
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
            ],
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    public function determineOrderColumnName(): string
    {
        return 'position';
    }

    public function determineTitleColumnName(): string
    {
        return 'name';
    }

    public static function defaultParentKey(): null
    {
        return null;
    }
}
