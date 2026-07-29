<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

use UnexpectedValueException;

final readonly class SoftwareAssetSummaryData
{
    public function __construct(
        public int $id,
        public string $name,
        public ?int $currentReleaseId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (! array_key_exists('id', $data) || ! is_numeric($data['id'])) {
            throw new UnexpectedValueException('The software asset summary response is missing a valid [id].');
        }

        return new self(
            id: (int) $data['id'],
            name: (string) ($data['name'] ?? ''),
            currentReleaseId: DataMapper::integer(
                $data['current_release_id'] ?? $data['currentVersion'] ?? null,
            ),
        );
    }
}
