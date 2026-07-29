<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Requests\IncidentPosts;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use VmeRetail\ServiceDeskify\Data\IncidentPostQueryData;

final class ListIncidentPostsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $incidentId,
        IncidentPostQueryData $query,
    ) {
        $this->query()->merge($query->query());
    }

    public function resolveEndpoint(): string
    {
        return "/api/incidents/{$this->incidentId}/posts";
    }
}
