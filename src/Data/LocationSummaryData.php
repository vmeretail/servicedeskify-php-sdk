<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

final readonly class LocationSummaryData
{
    public function __construct(
        public ?string $name,
        public ?string $customer,
        public ?string $storeNumber,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: DataMapper::string($data['name'] ?? null),
            customer: DataMapper::string($data['customer'] ?? null),
            storeNumber: DataMapper::string($data['store_number'] ?? null),
        );
    }
}
