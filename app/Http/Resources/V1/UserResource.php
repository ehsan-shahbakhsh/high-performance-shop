<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserResource',
    title: 'User Resource',
    description: 'ساختار خروجی اطلاعات کاربر در وب‌سرویس',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'first_name', type: 'string', example: 'احسان'),
        new OA\Property(property: 'last_name', type: 'string', example: 'شه بخش'),
        new OA\Property(property: 'avatar', type: 'string', format: 'uri', example: 'https://i.pravatar.cc/300?u=a042581f4e29026704d'),
    ]
)]
class UserResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'avatar' => $this->avatar,
        ];
    }
}
