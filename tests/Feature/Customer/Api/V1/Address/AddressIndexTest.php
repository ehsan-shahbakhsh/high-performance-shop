<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\getJson;
use App\Models\{User, Address, Province, City};
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('core logic and happy path', function () {
    it('shows only the user\'s own addresses', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user);

        Address::factory()->count(3)->for($user)->create();
        Address::factory()->count(2)->for($otherUser)->create();

        getJson('/api/v1/addresses')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('orders addresses by default status first, then by id descending', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Address::factory()->for($user)->create(['created_at' => now()->subDays(2)]);

        $defaultAddress = Address::factory()->for($user)->default()->create();

        getJson('/api/v1/addresses')
            ->assertOk()
            ->assertJsonPath('data.0.id', $defaultAddress->id);
    });

    it('returns the address resource with the correct structure and grouped fields', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $province = Province::factory()->create();
        $city = City::factory()->for($province)->create();

        Address::factory()
            ->for($user)
            ->for($province)
            ->for($city)
            ->create();

        getJson('/api/v1/addresses')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'recipient' => [
                            'first_name',
                            'last_name',
                            'mobile',
                        ],
                        'province' => [
                            'id',
                            'name',
                        ],
                        'city' => [
                            'id',
                            'name',
                            'has_shipping',
                        ],
                        'title',
                        'address_line',
                        'plaque',
                        'unit',
                        'postal_code',
                        'coordinates' => [
                            'latitude',
                            'longitude',
                        ],
                        'is_default',
                    ]
                ]
            ]);
    });
});

describe('edge cases and errors', function () {
    it('denies access to unauthenticated users', function () {
        getJson('/api/v1/addresses')
            ->assertUnauthorized();
    });
});
