<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

use Illuminate\Http\UploadedFile;
use SplFileInfo;
use VmeRetail\ServiceDeskify\Enums\PostType;

final readonly class CreateIncidentPostData
{
    /**
     * @param  array<int, UploadedFile|SplFileInfo|string>  $attachments
     */
    public function __construct(
        public string $details,
        public ?PostType $type = null,
        public array $attachments = [],
    ) {}

    /**
     * @return array<string, string>
     */
    public function payload(): array
    {
        return array_filter([
            'details' => $this->details,
            'type' => $this->type?->value,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
