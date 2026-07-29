<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

use VmeRetail\ServiceDeskify\Enums\IncidentStatus;

final readonly class IncidentQueryData
{
    public function __construct(
        public int $page = 1,
        public ?IncidentStatus $status = null,
    ) {}

    /**
     * @return array<string, int|string>
     */
    public function query(): array
    {
        return array_filter([
            'page' => $this->page,
            'status' => $this->status?->value,
        ], static fn (mixed $value): bool => $value !== null);
    }

    public function withPage(int $page): self
    {
        return new self(
            page: $page,
            status: $this->status,
        );
    }
}
