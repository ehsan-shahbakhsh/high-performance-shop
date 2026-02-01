<?php

namespace App\Http\Resources\V1\Auth;

use App\Http\Resources\Concerns\DateFormatted;
use App\Http\Resources\V1\UserResource;
use Illuminate\Http\Request;

class AuthUserResource extends UserResource
{
    use DateFormatted;

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
            'mobile_verified_at' => $this->toPersianDate($this->mobile_verified_at),
            'email_verified_at' => $this->toPersianDate($this->email_verified_at),
            'banned_at' => $this->toPersianDate($this->banned_at),
            'last_login_at' => $this->toPersianDate($this->last_login_at),
            'last_login_ip' => $this->last_login_ip,
            'created_at' => $this->toPersianDate($this->created_at),
        ]);
    }
}
