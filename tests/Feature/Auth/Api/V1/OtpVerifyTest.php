<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\postJson;
use App\Models\User;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
    it('requires both identifier and code', function () {
        postJson('/api/v1/auth/otp/verify')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['identifier', 'code']);
    });

    it('validates formats of identifier and code', function (string $identifier, string $code) {
        postJson('/api/v1/auth/otp/verify', [
            'identifier' => $identifier,
            'code' => $code
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['identifier', 'code']);

    })->with([
        'short code and wrong email' => ['ehsan@com', '12'],
        'long code and wrong mobile' => ['0912', '1234567'],
    ]);
});

describe('core logic and happy path', function () {
    it('successfully logs in with a valid code', function (string $validIdentifier, string $validCode, string $type) {
        $cacheKey = "otp_{$type}_{$validIdentifier}";
        Cache::put($cacheKey, $validCode, 120);

        $response = postJson('/api/v1/auth/otp/verify', [
            'identifier' => $validIdentifier,
            'code' => $validCode,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['user', 'authorization' => ['token']]
            ]);

        expect(Cache::has($cacheKey))->toBeFalse();
    })->with([
        ['09123456789', '1234', 'mobile'],
        ['test@example.com', '5678', 'email'],
    ]);
});

describe('edge cases and errors', function () {
    it('returns error if the code is incorrect', function () {
        $identifier = '09123456789';
        Cache::put("otp_mobile_{$identifier}", '1111', 120);

        $response = postJson('/api/v1/auth/otp/verify', [
            'identifier' => $identifier,
            'code' => '2222'
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'کد وارد شده اشتباه است. لطفاً مجدداً بررسی کنید.');
    });

    it('prevents banned users from logging in even with a valid code', function () {
        User::factory()->banned()->create(['mobile' => '09123456789']);
        Cache::put("otp_mobile_09123456789", '1234', 120);

        $response = postJson('/api/v1/auth/otp/verify', [
            'identifier' => '09123456789',
            'code' => '1234',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'حساب کاربری شما مسدود شده است. لطفاً با پشتیبانی تماس بگیرید.');
    });

    it('returns an error if the otp code has expired', function () {
        $identifier = '09123456789';

        $response = postJson('/api/v1/auth/otp/verify', [
            'identifier' => $identifier,
            'code' => '1234',
        ]);

        $response->assertStatus(410)
            ->assertJsonPath('message', 'کد تأیید منقضی شده است. لطفاً درخواست ارسال مجدد دهید.');
    });

    it('throttles verification after too many failed attempts', function () {
        $identifier = '09123456789';
        Cache::put("otp_mobile_{$identifier}", '1234', 120);

        for ($i = 0; $i < 4; $i++) {
            postJson('/api/v1/auth/otp/verify', [
                'identifier' => $identifier,
                'code' => '1111',
            ])
                ->assertStatus(400)
                ->assertJsonPath('message', 'کد وارد شده اشتباه است. لطفاً مجدداً بررسی کنید.');
        }

        $response = postJson('/api/v1/auth/otp/verify', [
            'identifier' => $identifier,
            'code' => '1234',
        ]);

        $response->assertStatus(410);
    });
});
