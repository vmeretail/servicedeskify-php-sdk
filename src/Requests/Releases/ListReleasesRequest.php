<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Requests\Releases;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use VmeRetail\ServiceDeskify\Data\ReleaseQueryData;

final class ListReleasesRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(ReleaseQueryData $query)
    {
        $this->query()->merge($query->query());
    }

    public function resolveEndpoint(): string
    {
        return '/api/releases';
    }
}
