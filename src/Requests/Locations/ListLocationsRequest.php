<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Requests\Locations;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use VmeRetail\ServiceDeskify\Data\LocationQueryData;

final class ListLocationsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(LocationQueryData $query)
    {
        $this->query()->merge($query->query());
    }

    public function resolveEndpoint(): string
    {
        return '/api/locations';
    }
}
