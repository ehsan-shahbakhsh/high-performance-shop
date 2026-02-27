<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\postJson;
use Illuminate\Support\Facades\Notification;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
    it('requires an identifier', function () {
        postJson('/api/v1/auth/otp/request')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['identifier']);
    });

    it('rejects invalid identifier formats', function (string $invalidIdentifier) {
        postJson('/api/v1/auth/otp/request', [
            'identifier' => $invalidIdentifier
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['identifier']);

    })->with([
        'wrong_mobile' => '12345',
        'wrong_email' => 'ehsan@com',
        'just_string' => 'hello_world',
    ]);
});

describe('core logic and happy path', function () {
    it('generates otp and saves it in cache for valid identifiers', function (string $validIdentifier, string $type) {
        Notification::fake();

        postJson('/api/v1/auth/otp/request', ['identifier' => $validIdentifier])
            ->assertOk()
            ->assertJsonPath('message', "کد تایید برای {$validIdentifier} ارسال شد.");

        $cacheKey = "otp_{$type}_{$validIdentifier}";

        expect(Cache::has($cacheKey))->toBeTrue();
    })->with([
        ['09123456789', 'mobile'],
        ['test@example.com', 'email'],
    ]);
});

describe('edge cases and errors', function () {
    it('throttles requests if user asks for otp too quickly', function () {
        $mobile = '09123456789';

        for ($i = 0; $i < 3; $i++) {
            postJson('/api/v1/auth/otp/request', ['identifier' => $mobile]);
        }

        $response = postJson('/api/v1/auth/otp/request', ['identifier' => $mobile]);
        $response->assertStatus(429);
    });
});
