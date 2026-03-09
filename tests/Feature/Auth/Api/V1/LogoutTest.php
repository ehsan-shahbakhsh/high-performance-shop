<?php

use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{postJson, withToken};
use App\Models\User;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
});

describe('core logic and happy path', function () {
    it('logs out authenticated user successfully', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'با موفقیت از حساب خارج شدید.');
    });

    it('invalidates the authenticated user’s current Sanctum token after calling the logout endpoint', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        expect($user->tokens()->count())->toBe(1);

        withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        expect($user->tokens()->count())->toBe(0);
    });
});

describe('edge cases and errors', function () {
    it('returns 401 if user is not authenticated', function () {
        postJson('/api/v1/auth/logout')
            ->assertUnauthorized();
    });
});
