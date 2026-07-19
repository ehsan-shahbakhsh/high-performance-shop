<?php

namespace App\Http\Resources\V1\Auth;

use App\Http\Resources\V1\UserResource;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuthUserResource',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/UserResource'),
        new OA\Schema(properties: [
            new OA\Property(property: 'mobile', type: 'string', example: '09120000000', nullable: true),
            new OA\Property(property: 'email', type: 'string', example: 'ali@example.com', nullable: true),
            new OA\Property(property: 'mobile_verified_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'banned_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'last_login_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'last_login_ip', type: 'string', example: '192.168.1.1', nullable: true),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        ])
    ]
)]
class AuthUserResource extends UserResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $publicData = parent::toArray($request);

        return array_merge($publicData, [
            'mobile' => $this->mobile,
            'email' => $this->email,
            'mobile_verified_at' => $this->mobile_verified_at,
            'email_verified_at' => $this->email_verified_at,
            'banned_at' => $this->banned_at,
            'last_login_at' => $this->last_login_at,
            'last_login_ip' => $this->last_login_ip,
            'created_at' => $this->created_at,
        ]);
    }
}
