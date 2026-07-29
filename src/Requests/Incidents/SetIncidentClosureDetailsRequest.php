<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Requests\Incidents;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use VmeRetail\ServiceDeskify\Data\IncidentClosureData;

final class SetIncidentClosureDetailsRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $incidentId,
        private readonly IncidentClosureData $data,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/api/incidents/{$this->incidentId}/closure-details";
    }

    /**
     * @return array<string, string>
     */
    protected function defaultBody(): array
    {
        return $this->data->payload();
    }
}
