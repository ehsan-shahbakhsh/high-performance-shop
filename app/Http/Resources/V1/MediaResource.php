<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->getUrl(),
            'name' => $this->file_name,
            'size' => $this->human_readable_size,
            'mime_type' => $this->mime_type,
            'collection' => $this->collection_name,
        ];
    }
}
