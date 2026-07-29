<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

use Carbon\CarbonImmutable;

final readonly class MasterReleaseData
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?CarbonImmutable $closedAt,
        public array $extra = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            closedAt: DataMapper::date($data['closed_at'] ?? null),
            extra: $data,
        );
    }
}
