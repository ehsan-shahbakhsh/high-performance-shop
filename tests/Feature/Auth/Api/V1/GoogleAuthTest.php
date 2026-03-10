<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{getJson, assertDatabaseHas};
use App\Models\{User, SocialAccount};

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
});

describe('core logic and happy path', function () {
    it('authenticates user via google callback', function () {
        mockGoogleUser();

        getJson('/api/v1/auth/google/callback')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'authorization' => ['token'],
                ],
            ]);

        $response = getJson('/api/v1/auth/google/callback')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'authorization' => ['token'],
                ],
            ]);

        expect($response->json('data.authorization.token'))->not->toBeEmpty();
    });

    it('creates user from google account', function () {
        expect(User::query()->count())->toBe(0);

        mockGoogleUser();

        $response = getJson('/api/v1/auth/google/callback')
            ->assertOk();

        expect(User::query()->count())->toBe(1);

        assertDatabaseHas('users', [
            'email' => 'test@gmail.com'
        ]);

        assertDatabaseHas('social_accounts', [
            'user_id' => $response->json('data.user.id'),
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);
    });

    it('logs in existing user via google', function () {
        $user = User::factory()->create([
            'email' => 'test@gmail.com'
        ]);

        mockGoogleUser();

        getJson('/api/v1/auth/google/callback')
            ->assertOk();

        expect(User::query()->count())->toBe(1);

        assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);
    });

    it('does not create duplicate social accounts', function () {
        $user = User::factory()->create([
            'email' => 'test@gmail.com',
        ]);

        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);

        mockGoogleUser();

        getJson('/api/v1/auth/google/callback')
            ->assertOk();

        expect(SocialAccount::query()->count())->toBe(1);
    });
});

describe('edge cases and errors', function () {
    it('fails when google does not return email', function () {
        mockGoogleUser(email: null);

        getJson('/api/v1/auth/google/callback')
            ->assertUnprocessable();
    });
});
