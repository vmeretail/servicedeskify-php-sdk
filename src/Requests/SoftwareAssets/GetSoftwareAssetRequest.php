<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Requests\SoftwareAssets;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetSoftwareAssetRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly int $softwareAssetId) {}

    public function resolveEndpoint(): string
    {
        return "/api/software-assets/{$this->softwareAssetId}";
    }
}
