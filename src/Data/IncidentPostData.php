<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

use Carbon\CarbonImmutable;
use VmeRetail\ServiceDeskify\Enums\PostType;

final readonly class IncidentPostData
{
    /**
     * @param  array<int, AttachmentData>  $attachments
     */
    public function __construct(
        public int $id,
        public string $detail,
        public ?CarbonImmutable $createdDate,
        public ?UserData $createdBy,
        public ?PostType $type,
        public array $attachments,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $createdBy = DataMapper::array($data['created_by'] ?? null);
        $attachments = is_array($data['attachments'] ?? null) ? $data['attachments'] : [];
        $type = is_string($data['type'] ?? null)
            ? PostType::tryFrom($data['type'])
            : null;

        return new self(
            id: (int) ($data['id'] ?? 0),
            detail: (string) ($data['detail'] ?? ''),
            createdDate: DataMapper::date($data['created_date'] ?? null),
            createdBy: $createdBy === null ? null : UserData::fromArray($createdBy),
            type: $type,
            attachments: array_values(array_map(
                static fn (array $attachment): AttachmentData => AttachmentData::fromArray($attachment),
                array_filter($attachments, 'is_array'),
            )),
        );
    }
}
