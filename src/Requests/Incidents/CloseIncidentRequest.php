<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Requests\Incidents;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class CloseIncidentRequest extends Request
{
    protected Method $method = Method::POST;

    public function __construct(private readonly int $incidentId) {}

    public function resolveEndpoint(): string
    {
        return "/api/incidents/{$this->incidentId}/close";
    }
}
