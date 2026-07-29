<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Requests\Incidents;

use Saloon\Enums\Method;
use VmeRetail\ServiceDeskify\Data\CreateIncidentData;
use VmeRetail\ServiceDeskify\Requests\Concerns\PayloadRequest;

final class CreateIncidentRequest extends PayloadRequest
{
    protected Method $method = Method::POST;

    public function __construct(CreateIncidentData $data)
    {
        $this->setPayload($data->payload(), $data->attachments);
    }

    public function resolveEndpoint(): string
    {
        return '/api/incidents';
    }
}
