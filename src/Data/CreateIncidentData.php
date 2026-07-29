<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

use Illuminate\Http\UploadedFile;
use SplFileInfo;
use VmeRetail\ServiceDeskify\Enums\IncidentPriority;

final readonly class CreateIncidentData
{
    /**
     * @param  array<int, UploadedFile|SplFileInfo|string>  $attachments
     */
    public function __construct(
        public string $title,
        public string $details,
        public ?int $storeNumber = null,
        public ?int $locationId = null,
        public IncidentPriority $priority = IncidentPriority::P3,
        public array $attachments = [],
    ) {}

    /**
     * @return array<string, int|string>
     */
    public function payload(): array
    {
        return array_filter([
            'title' => $this->title,
            'details' => $this->details,
            'store_number' => $this->storeNumber,
            'location' => $this->locationId,
            'priority' => $this->priority->value,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
