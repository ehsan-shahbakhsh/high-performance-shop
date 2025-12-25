<?php

namespace App\Data\Auth;

use App\Enums\SocialAccountProviderEnum;
use Spatie\LaravelData\Data;
use Laravel\Socialite\Two\User as SocialiteUser;

class SocialUserData extends Data
{
    public function __construct(
        public ?string                   $firstName,
        public ?string                   $lastName,
        public string                    $email,
        public SocialAccountProviderEnum $provider,
        public string                    $providerId,
        public ?string                   $avatar,
        public ?string                   $token,
        public ?string                   $userIp,
    )
    {
    }

    public static function fromSocialite(SocialiteUser $socialUser, SocialAccountProviderEnum $provider, ?string $userIp = null): self
    {
        return new self(
            firstName: $socialUser->getRaw()['given_name'],
            lastName: $socialUser->getRaw()['family_name'],
            email: $socialUser->getEmail(),
            provider: $provider,
            providerId: $socialUser->getId(),
            avatar: $socialUser->getAvatar(),
            token: $socialUser->token,
            userIp: $userIp,
        );
    }
}
