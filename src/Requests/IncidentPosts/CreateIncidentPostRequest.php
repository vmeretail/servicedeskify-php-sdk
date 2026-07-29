<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Requests\IncidentPosts;

use Saloon\Enums\Method;
use VmeRetail\ServiceDeskify\Data\CreateIncidentPostData;
use VmeRetail\ServiceDeskify\Requests\Concerns\PayloadRequest;

final class CreateIncidentPostRequest extends PayloadRequest
{
    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $incidentId,
        CreateIncidentPostData $data,
    ) {
        $this->setPayload($data->payload(), $data->attachments);
    }

    public function resolveEndpoint(): string
    {
        return "/api/incidents/{$this->incidentId}/posts";
    }
}
