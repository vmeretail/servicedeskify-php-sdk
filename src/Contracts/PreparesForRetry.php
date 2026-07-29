<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Contracts;

interface PreparesForRetry
{
    public function prepareForRetry(): void;
}
