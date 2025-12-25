<?php

namespace App\Http\Resources\V1\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isDetailedView = !is_null($this->seo_description) || !is_null($this->seo_title);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'parent_id' => $this->parent_id,
            'level' => $this->level,

            'icon' => $this->icon,
            'cover_image' => $this->cover_image ? Storage::url($this->cover_image) : null,

            'is_featured' => $this->is_featured,

            'seo' => $this->when($isDetailedView, [
                'title' => $this->seo_title ?? $this->name,
                'description' => $this->seo_description,
            ]),

            'children' => ProductCategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
