<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

use Carbon\CarbonImmutable;

final readonly class ReleaseData
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public int $id,
        public string $version,
        public ?CarbonImmutable $date,
        public ?CarbonImmutable $closedAt,
        public bool $planned,
        public ?string $url,
        public ?SoftwareAssetSummaryData $softwareAsset,
        public ?MasterReleaseData $masterVersion,
        public array $extra = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $softwareAsset = DataMapper::array($data['software_asset'] ?? $data['softwareAsset'] ?? null);
        $masterVersion = DataMapper::array($data['master_version'] ?? $data['masterVersion'] ?? null);

        return new self(
            id: (int) ($data['id'] ?? 0),
            version: (string) ($data['version'] ?? ''),
            date: DataMapper::date($data['date'] ?? null),
            closedAt: DataMapper::date($data['closed_at'] ?? null),
            planned: (bool) ($data['planned'] ?? false),
            url: DataMapper::string($data['url'] ?? null),
            softwareAsset: $softwareAsset === null ? null : SoftwareAssetSummaryData::fromArray($softwareAsset),
            masterVersion: $masterVersion === null ? null : MasterReleaseData::fromArray($masterVersion),
            extra: $data,
        );
    }
}
