<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

use Carbon\CarbonImmutable;
use VmeRetail\ServiceDeskify\Enums\SortDirection;

final readonly class IncidentPostQueryData
{
    public function __construct(
        public int $page = 1,
        public ?int $perPage = null,
        public ?SortDirection $sortDirection = null,
        public ?CarbonImmutable $since = null,
    ) {}

    /**
     * @return array<string, int|string>
     */
    public function query(): array
    {
        return array_filter([
            'page' => $this->page,
            'per_page' => $this->perPage,
            'sort_direction' => $this->sortDirection?->value,
            'since' => $this->since?->toAtomString(),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
