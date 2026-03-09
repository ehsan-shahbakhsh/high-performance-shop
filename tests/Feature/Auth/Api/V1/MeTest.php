<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\getJson;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
});

describe('core logic and happy path', function () {
    it('returns authenticated user data', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'avatar',
                    'mobile',
                    'email',
                    'mobile_verified_at',
                    'email_verified_at',
                    'banned_at',
                    'last_login_at',
                    'last_login_ip',
                    'created_at',
                ]
            ]);
    });
});

describe('edge cases and errors', function () {
    it('returns 401 if user is not authenticated', function () {
        getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    });
});
