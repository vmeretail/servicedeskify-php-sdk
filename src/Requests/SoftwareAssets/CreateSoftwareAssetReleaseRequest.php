<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Requests\SoftwareAssets;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use VmeRetail\ServiceDeskify\Data\CreateSoftwareAssetReleaseData;

final class CreateSoftwareAssetReleaseRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $softwareAssetId,
        private readonly CreateSoftwareAssetReleaseData $data,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/api/software-assets/{$this->softwareAssetId}/releases";
    }

    /**
     * @return array<string, bool|string>
     */
    protected function defaultBody(): array
    {
        return $this->data->payload();
    }
}
