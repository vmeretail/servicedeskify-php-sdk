<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

use Carbon\CarbonImmutable;

final readonly class CreateSoftwareAssetReleaseData
{
    public function __construct(
        public string $version,
        public ?CarbonImmutable $date = null,
        public bool $setCurrentVersion = true,
    ) {}

    /**
     * @return array{version: string, date?: string, set_current_version: bool}
     */
    public function payload(): array
    {
        $data = [
            'version' => $this->version,
            'set_current_version' => $this->setCurrentVersion,
        ];

        if ($this->date !== null) {
            $data['date'] = $this->date->format('Y-m-d');
        }

        return $data;
    }
}
