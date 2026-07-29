<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

final readonly class CreatedReleaseData
{
    public function __construct(
        public int $softwareAssetVersionId,
        public int $masterSoftwareAssetVersionId,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            softwareAssetVersionId: (int) ($data['software_asset_version_id'] ?? 0),
            masterSoftwareAssetVersionId: (int) ($data['master_software_asset_version_id'] ?? 0),
        );
    }
}
