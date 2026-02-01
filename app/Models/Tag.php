<?php

namespace App\Models;

use App\Enums\TagType;
use Cviebrock\EloquentSluggable\Sluggable;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;
    use Sluggable;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'icon',
        'color',
        'description',
        'seo_title',
        'seo_description',
        'canonical_url',
        'usage_count',
        'position',
        'is_visible',
        'is_featured',
    ];

    protected $casts = [
        'type' => TagType::class,
        'is_visible' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
            ],
        ];
    }
}
