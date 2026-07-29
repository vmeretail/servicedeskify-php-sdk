<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Resources;

use VmeRetail\ServiceDeskify\Data\CreatedReleaseData;
use VmeRetail\ServiceDeskify\Data\CreateSoftwareAssetReleaseData;
use VmeRetail\ServiceDeskify\Data\SoftwareAssetData;
use VmeRetail\ServiceDeskify\Data\SoftwareAssetSummaryData;
use VmeRetail\ServiceDeskify\Exceptions\AuthenticationException;
use VmeRetail\ServiceDeskify\Exceptions\ValidationException;
use VmeRetail\ServiceDeskify\Requests\SoftwareAssets\CreateSoftwareAssetReleaseRequest;
use VmeRetail\ServiceDeskify\Requests\SoftwareAssets\GetSoftwareAssetRequest;
use VmeRetail\ServiceDeskify\Requests\SoftwareAssets\ListSoftwareAssetsRequest;
use VmeRetail\ServiceDeskify\ServiceDeskifyClient;

final readonly class SoftwareAssetResource
{
    public function __construct(private ServiceDeskifyClient $client) {}

    /**
     * @return array<int, SoftwareAssetSummaryData>
     */
    public function all(): array
    {
        $response = $this->client->send(new ListSoftwareAssetsRequest);
        $payload = $response->json();

        return array_values(array_map(
            static fn (array $asset): SoftwareAssetSummaryData => SoftwareAssetSummaryData::fromArray($asset),
            array_filter($payload, 'is_array'),
        ));
    }

    public function find(int|string $softwareAssetId): SoftwareAssetData
    {
        $response = $this->client->send(new GetSoftwareAssetRequest((int) $softwareAssetId));

        /** @var array<string, mixed> $payload */
        $payload = $response->json();

        return SoftwareAssetData::fromArray($payload);
    }

    /**
     * Requires an agent account. Versions are unique per software asset, so
     * posting an existing version is a validation failure rather than a no-op.
     *
     * @throws AuthenticationException
     * @throws ValidationException
     */
    public function createRelease(
        int|string $softwareAssetId,
        CreateSoftwareAssetReleaseData $data,
    ): CreatedReleaseData {
        $response = $this->client->send(new CreateSoftwareAssetReleaseRequest(
            (int) $softwareAssetId,
            $data,
        ));

        /** @var array<string, mixed> $payload */
        $payload = $response->json();

        return CreatedReleaseData::fromArray($payload);
    }
}
