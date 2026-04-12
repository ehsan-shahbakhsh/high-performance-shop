<?php

namespace App\Data\Customer;

use App\Http\Requests\Api\V1\Customer\Address\StoreAddressRequest;
use App\Http\Requests\Api\V1\Customer\Address\UpdateAddressRequest;
use Spatie\LaravelData\Data;

class AddressData extends Data
{
    public function __construct(
        public ?int    $provinceId = null,
        public ?int    $cityId = null,
        public ?string $addressLine = null,
        public ?string $postalCode = null,
        public ?string $recipientFirstName = null,
        public ?string $recipientLastName = null,
        public ?string $recipientMobile = null,
        public ?string $title = null,
        public ?string $plaque = null,
        public ?string $unit = null,
        public ?float  $latitude = null,
        public ?float  $longitude = null,
        public ?bool   $isDefault = null,
    )
    {
    }

    public static function fromStoreRequest(StoreAddressRequest $request): self
    {
        $validated = $request->validated();
        $user = $request->user();

        return new self(
            provinceId: $validated['province_id'],
            cityId: $validated['city_id'],
            addressLine: $validated['address_line'],
            postalCode: $validated['postal_code'],
            recipientFirstName: $validated['recipient_first_name'] ?? $user->first_name,
            recipientLastName: $validated['recipient_last_name'] ?? $user->last_name,
            recipientMobile: $validated['recipient_mobile'] ?? $user->mobile,
            title: $validated['title'] ?? null,
            plaque: $validated['plaque'] ?? null,
            unit: $validated['unit'] ?? null,
            latitude: isset($validated['latitude']) ? floatval($validated['latitude']) : null,
            longitude: isset($validated['longitude']) ? floatval($validated['longitude']) : null,
            isDefault: boolval($validated['is_default'] ?? false),
        );
    }

    public static function fromUpdateRequest(UpdateAddressRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            provinceId: $validated['province_id'] ?? null,
            cityId: $validated['city_id'] ?? null,
            addressLine: $validated['address_line'] ?? null,
            postalCode: $validated['postal_code'] ?? null,
            recipientFirstName: $validated['recipient_first_name'] ?? null,
            recipientLastName: $validated['recipient_last_name'] ?? null,
            recipientMobile: $validated['recipient_mobile'] ?? null,
            title: $validated['title'] ?? null,
            plaque: $validated['plaque'] ?? null,
            unit: $validated['unit'] ?? null,
            latitude: isset($validated['latitude']) ? floatval($validated['latitude']) : null,
            longitude: isset($validated['longitude']) ? floatval($validated['longitude']) : null,
            isDefault: isset($validated['is_default']) ? boolval($validated['is_default']) : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'province_id' => $this->provinceId,
            'city_id' => $this->cityId,
            'address_line' => $this->addressLine,
            'postal_code' => $this->postalCode,
            'recipient_first_name' => $this->recipientFirstName,
            'recipient_last_name' => $this->recipientLastName,
            'recipient_mobile' => $this->recipientMobile,
            'title' => $this->title,
            'plaque' => $this->plaque,
            'unit' => $this->unit,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_default' => $this->isDefault,
        ], static fn($value) => !is_null($value));
    }
}
