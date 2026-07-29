<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Data;

use Carbon\CarbonImmutable;

final readonly class ReleaseQueryData
{
    public function __construct(public ?CarbonImmutable $fromDate = null) {}

    /**
     * @return array<string, string>
     */
    public function query(): array
    {
        return $this->fromDate === null
            ? []
            : ['date' => $this->fromDate->format('Y-m-d')];
    }
}
