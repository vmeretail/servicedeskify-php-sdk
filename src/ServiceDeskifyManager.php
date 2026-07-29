<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify;

use VmeRetail\ServiceDeskify\Resources\IncidentResource;
use VmeRetail\ServiceDeskify\Resources\LocationResource;
use VmeRetail\ServiceDeskify\Resources\ReleaseResource;
use VmeRetail\ServiceDeskify\Resources\SoftwareAssetResource;

final readonly class ServiceDeskifyManager
{
    public function __construct(private ServiceDeskifyClient $client) {}

    public function incidents(): IncidentResource
    {
        return new IncidentResource($this->client);
    }

    public function locations(): LocationResource
    {
        return new LocationResource($this->client);
    }

    public function softwareAssets(): SoftwareAssetResource
    {
        return new SoftwareAssetResource($this->client);
    }

    public function releases(): ReleaseResource
    {
        return new ReleaseResource($this->client);
    }
}
