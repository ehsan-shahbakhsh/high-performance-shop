<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{deleteJson, assertSoftDeleted};
use App\Models\{User, Address};
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('core logic and happy path', function () {
    it('deletes a regular address', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Address::factory()->for($user)->count(5)->create();
        $address = Address::factory()->for($user)->create();

        deleteJson("/api/v1/addresses/{$address->id}")
            ->assertOk()
            ->assertJsonPath('message', 'آدرس مورد نظر از دفترچه آدرس شما حذف شد.');

        assertSoftDeleted($address);
    });

    it('deletes the default address if it is the only one left', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $address = Address::factory()->for($user)->default()->create();

        deleteJson("/api/v1/addresses/{$address->id}")
            ->assertOk()
            ->assertJsonPath('message', 'آدرس مورد نظر از دفترچه آدرس شما حذف شد.');

        assertSoftDeleted($address);
    });
});

describe('edge cases and errors', function () {
    it('denies access to unauthenticated users', function () {
        deleteJson('/api/v1/addresses/9999')
            ->assertUnauthorized();
    });

    it('returns 404 when trying to delete a non‑existent address', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        deleteJson('/api/v1/addresses/9999')
            ->assertNotFound();
    });

    it('returns 403 when a user tries to delete someone else’s address', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $otherUser = User::factory()->create();
        $address = Address::factory()->for($otherUser)->create();

        deleteJson("/api/v1/addresses/{$address->id}")
            ->assertForbidden();
    });

    it('returns 403 when trying to delete the default address while the user has other addresses', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $defaultAddress = Address::factory()->for($user)->default()->create();
        Address::factory()->for($user)->create();

        deleteJson("/api/v1/addresses/{$defaultAddress->id}")
            ->assertForbidden();
    });
});
