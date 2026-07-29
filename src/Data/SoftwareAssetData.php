<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

use Carbon\CarbonImmutable;

final readonly class SoftwareAssetData
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?int $currentReleaseId,
        public ?ReleaseData $currentRelease,
        public ?CarbonImmutable $closedAt,
        public array $extra = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $currentRelease = DataMapper::array($data['current_release'] ?? $data['currentRelease'] ?? null);

        return new self(
            id: (int) ($data['id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            currentReleaseId: DataMapper::integer(
                $data['current_release_id'] ?? $data['currentVersion'] ?? null,
            ),
            currentRelease: $currentRelease === null ? null : ReleaseData::fromArray($currentRelease),
            closedAt: DataMapper::date($data['closed_at'] ?? null),
            extra: $data,
        );
    }
}
