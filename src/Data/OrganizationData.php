<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

final readonly class OrganizationData
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public ?int $id,
        public ?string $name,
        public ?string $type,
        public ?bool $enabled,
        public array $extra = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: DataMapper::integer($data['id'] ?? null),
            name: DataMapper::string($data['name'] ?? null),
            type: DataMapper::string($data['type'] ?? null),
            enabled: DataMapper::boolean($data['enabled'] ?? null),
            extra: $data,
        );
    }
}
