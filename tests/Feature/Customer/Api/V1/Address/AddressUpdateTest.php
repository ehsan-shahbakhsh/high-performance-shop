<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{patchJson, assertDatabaseHas};
use App\Models\{User, Address, City, Province};
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
    it('requires recipient fields if the user profile is missing first name, last name, or mobile', function () {
        $user = User::factory()->create(['mobile' => null]);
        Sanctum::actingAs($user);

        $address = Address::factory()->for($user)->create();

        patchJson("/api/v1/addresses/{$address->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['recipient_first_name', 'recipient_last_name', 'recipient_mobile']);
    });

    it('requires all three recipient fields together if at least one is provided', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $address = Address::factory()->for($user)->create();

        patchJson("/api/v1/addresses/{$address->id}", ['recipient_first_name' => fake()->firstName()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['recipient_last_name', 'recipient_mobile']);
    });

    it('fails if recipient fields have invalid formats', function (string $field, mixed $invalidValue) {
        $user = User::factory()->create(['first_name' => null]);
        Sanctum::actingAs($user);

        $address = Address::factory()->for($user)->create();

        $data = [
            'recipient_first_name' => 'علی',
            'recipient_last_name' => 'علوی',
            'recipient_mobile' => '09123456789',
        ];

        $data[$field] = $invalidValue;

        patchJson("/api/v1/addresses/{$address->id}", $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with([
        'first name is not string' => ['recipient_first_name', 12345],
        'first name exceeds max length' => ['recipient_first_name', str_repeat('a', 101)],

        'last name is not string' => ['recipient_last_name', 12345],
        'last name exceeds max length' => ['recipient_last_name', str_repeat('a', 101)],

        'mobile lacks leading zero' => ['recipient_mobile', '9123456789'],
        'mobile has letters' => ['recipient_mobile', '0912abcdefg'],
        'mobile is too short' => ['recipient_mobile', '0912345678'],
    ]);

    it('fails if province_id is invalid', function ($invalidValue, $expectedError) {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $address = Address::factory()->for($user)->create();

        patchJson("/api/v1/addresses/{$address->id}", ['province_id' => $invalidValue])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['province_id' => $expectedError]);
    })->with([
        'missing' => [null, 'الزامی است'],
        'not an integer' => ['not-an-int', 'integer'],
        'non-existent id' => [99999, 'معتبر نیست'],
    ]);

    it('fails if city_id format or existence is invalid', function ($invalidValue, $expectedError) {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $address = Address::factory()->for($user)->create();

        patchJson("/api/v1/addresses/{$address->id}", ['city_id' => $invalidValue])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_id' => $expectedError]);
    })->with([
        'missing' => [null, 'الزامی است'],
        'not an integer' => ['not-an-int', 'integer'],
        'non-existent id' => [99999, 'معتبر نیست'],
    ]);

    it('fails if the city does not belong to the selected province', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $city = City::factory()->create();
        $otherProvince = Province::factory()->create();

        $address = Address::factory()->for($user)->create();

        patchJson("/api/v1/addresses/{$address->id}", [
            'province_id' => $otherProvince->id,
            'city_id' => $city->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_id']);
    });

    it('fails if title format is invalid', function ($invalidValue, $expectedError) {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $address = Address::factory()->for($user)->create();

        patchJson("/api/v1/addresses/{$address->id}", ['title' => $invalidValue])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title' => $expectedError]);
    })->with([
        'not a string' => [['not-string-array'], 'باید رشته باشد'],
        'exceeds max length' => [str_repeat('a', 51), 'نباید بیشتر از'],
    ]);

    it('fails if address_line is invalid or missing', function ($invalidValue, $expectedError) {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $address = Address::factory()->for($user)->create();

        patchJson("/api/v1/addresses/{$address->id}", ['address_line' => $invalidValue])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['address_line' => $expectedError]);
    })->with([
        'missing' => [null, 'الزامی است'],
        'not a string' => [['not-string-array'], 'باید رشته باشد'],
        'exceeds max length' => [str_repeat('a', 1001), 'نباید بیشتر از'],
    ]);

    it('fails if plaque format is invalid', function ($invalidValue, $expectedError) {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $address = Address::factory()->for($user)->create();

        patchJson("/api/v1/addresses/{$address->id}", ['plaque' => $invalidValue])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plaque' => $expectedError]);
    })->with([
        'not a string' => [['not-string-array'], 'باید رشته باشد'],
        'exceeds max length' => [str_repeat('a', 31), 'نباید بیشتر از'],
    ]);

    it('fails if unit format is invalid', function ($invalidValue, $expectedError) {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $address = Address::factory()->for($user)->create();

        patchJson("/api/v1/addresses/{$address->id}", ['unit' => $invalidValue])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['unit' => $expectedError]);
    })->with([
        'not a string' => [['not-string-array'], 'باید رشته باشد'],
        'exceeds max length' => [str_repeat('a', 31), 'نباید بیشتر از'],
    ]);

    it('fails if postal_code is missing or invalid', function ($invalidValue, $expectedError) {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $address = Address::factory()->for($user)->create();

        patchJson("/api/v1/addresses/{$address->id}", ['postal_code' => $invalidValue])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['postal_code' => $expectedError]);
    })->with([
        'missing (null)' => [null, 'الزامی است'],

        'is an array' => [['1234567890'], 'باید رشته باشد'],
        'is an integer' => [1234567890, 'باید رشته باشد'],

        'contains letters' => ['123456789A', 'باید 10 رقم باشد'],
        'contains symbols' => ['123456-789', 'باید 10 رقم باشد'],
        'too short (9 digits)' => ['123456789', 'باید 10 رقم باشد'],
        'too long (11 digits)' => ['12345678901', 'باید 10 رقم باشد'],
    ]);

    it('fails if only one coordinate is provided without the other', function ($lat, $lng, $expectedErrorField, $expectedError) {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $address = Address::factory()->for($user)->create();

        patchJson("/api/v1/addresses/{$address->id}", [
            'latitude' => $lat,
            'longitude' => $lng,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$expectedErrorField => $expectedError]);
    })->with([
        'has lat but missing lng' => [25.3432, null, 'longitude', 'الزامی است تا زمانی که'],
        'has lng but missing lat' => [null, 30.34, 'latitude', 'الزامی است تا زمانی که'],
    ]);

    it('fails if coordinates are invalid', function ($field, $invalidValue, $expectedError) {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $address = Address::factory()->for($user)->create();

        patchJson("/api/v1/addresses/{$address->id}", [$field => $invalidValue])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field => $expectedError]);
    })->with([
        'lat is not numeric' => ['latitude', 'not-a-number', 'باید شامل عدد باشد'],
        'lng is not numeric' => ['longitude', 'not-a-number', 'باید شامل عدد باشد'],

        'lat is less than -90' => ['latitude', -91, 'باید بین'],
        'lat is greater than 90' => ['latitude', 91, 'باید بین'],

        'lng is less than -180' => ['longitude', -181, 'باید بین'],
        'lng is greater than 180' => ['longitude', 181, 'باید بین'],
    ]);

    it('fails if is_default is not a boolean', function ($invalidValue) {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $address = Address::factory()->for($user)->create();

        patchJson("/api/v1/addresses/{$address->id}", ['is_default' => $invalidValue])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_default' => 'فقط میتواند صحیح و یا غلط باشد']);
    })->with([
        'string instead of boolean' => ['yes'],
        'number out of boolean range' => [2],
        'array instead of boolean' => [['true']],
    ]);
});

describe('core logic and happy path', function () {
    it('updates the address details', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $address = Address::factory()->for($user)->create([
            'title' => fake()->word(),
            'address_line' => fake()->paragraph(),
        ]);

        patchJson("/api/v1/addresses/{$address->id}", [
            'title' => 'خانه',
            'address_line' => 'تست',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'اطلاعات آدرس بروزرسانی شد.')
            ->assertJsonPath('data.title', 'خانه');

        assertDatabaseHas('addresses', [
            'id' => $address->id,
            'title' => 'خانه',
            'address_line' => 'تست',
        ]);
    });

    it('sets the address as default and unsets others', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $oldDefaultAddress = Address::factory()->for($user)->default()->create();
        $targetAddress = Address::factory()->for($user)->create();

        patchJson("/api/v1/addresses/{$targetAddress->id}", ['is_default' => true])
            ->assertOk()
            ->assertJsonPath('message', 'اطلاعات آدرس بروزرسانی شد.')
            ->assertJsonPath('data.is_default', true);

        assertDatabaseHas('addresses', [
            'id' => $targetAddress->id,
            'is_default' => true,
        ]);

        assertDatabaseHas('addresses', [
            'id' => $oldDefaultAddress->id,
            'is_default' => false,
        ]);
    });
});

describe('edge cases and errors', function () {
    it('denies access to unauthenticated users', function () {
        patchJson('/api/v1/addresses/9999', [])
            ->assertUnauthorized();
    });

    it('returns 404 when trying to update a non‑existent address', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        patchJson('/api/v1/addresses/9999', [])
            ->assertNotFound();
    });
});
