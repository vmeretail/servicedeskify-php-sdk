<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Requests\Incidents;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use VmeRetail\ServiceDeskify\Data\IncidentQueryData;

final class ListIncidentsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(IncidentQueryData $query)
    {
        $this->query()->merge($query->query());
    }

    public function resolveEndpoint(): string
    {
        return '/api/incidents';
    }
}
