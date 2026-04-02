<?php

function mockGoogleUser($id = 'google-123', $email = 'test@gmail.com'): void
{
    $googleUser = Mockery::mock(\Laravel\Socialite\Two\User::class);

    $googleUser->shouldReceive('getId')->andReturn($id);
    $googleUser->shouldReceive('getEmail')->andReturn($email);
    $googleUser->shouldReceive('getAvatar')->andReturn('https://avatar.test');

    $googleUser->shouldReceive('getRaw')->andReturn([
        'given_name' => 'John',
        'family_name' => 'Doe',
    ]);

    $googleUser->token = 'fake-google-token';

    $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);

    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('user')->andReturn($googleUser);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($provider);
}

uses()
    ->beforeEach(function () {
        \Illuminate\Support\Facades\Redis::flushdb();
    })
    ->in('Feature');
