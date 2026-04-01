<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

use App\Models\{
    User,
    SocialAccount,
    Wallet,
    WalletTransaction,
    Province,
    City,
    Brand,
    Attribute,
    AttributeGroup,
    AttributeOption,
    Tag,
    Product,
    ProductCategory,
    ProductVariant,
    ProductRelation,
    Warehouse,
    Inventory,
    InventoryTransaction,
    ShippingCarrier,
    ShippingMethod,
    ShippingRate,
    ShippingZone,
    ShippingZoneLocation,
};

test('all factories can successfully create a model in the database', function (string $modelClass) {
    $model = $modelClass::factory()->create();

    expect($model)
        ->toBeInstanceOf($modelClass)
        ->and($model->exists)
        ->toBeTrue();
})
    ->with([
        User::class,
        SocialAccount::class,
        Wallet::class,
        WalletTransaction::class,
        Province::class,
        City::class,
        Brand::class,
        Attribute::class,
        AttributeGroup::class,
        AttributeOption::class,
        Tag::class,
        Product::class,
        ProductCategory::class,
        ProductVariant::class,
        ProductRelation::class,
        Warehouse::class,
        Inventory::class,
        InventoryTransaction::class,
        ShippingCarrier::class,
        ShippingMethod::class,
        ShippingRate::class,
        ShippingZone::class,
        ShippingZoneLocation::class,
    ]);
