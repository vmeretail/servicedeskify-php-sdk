<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

use Carbon\CarbonImmutable;

final readonly class LocationData
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $storeNumber,
        public ?string $telephone,
        public ?string $address,
        public ?bool $enabled,
        public ?string $latitude,
        public ?string $longitude,
        public ?CarbonImmutable $createdAt,
        public ?OrganizationData $customer,
        public array $extra = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $customer = DataMapper::array($data['customer'] ?? null);

        return new self(
            id: (int) ($data['id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            storeNumber: DataMapper::string($data['location_number'] ?? $data['store_number'] ?? null),
            telephone: DataMapper::string($data['telephone'] ?? null),
            address: DataMapper::string($data['address'] ?? null),
            enabled: DataMapper::boolean($data['enabled'] ?? null),
            latitude: DataMapper::string($data['latitude'] ?? null),
            longitude: DataMapper::string($data['longitude'] ?? null),
            createdAt: DataMapper::date($data['createdAt'] ?? $data['created_at'] ?? null),
            customer: $customer === null ? null : OrganizationData::fromArray($customer),
            extra: $data,
        );
    }
}
