<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Resources;

use VmeRetail\ServiceDeskify\Data\LocationData;
use VmeRetail\ServiceDeskify\Data\LocationQueryData;
use VmeRetail\ServiceDeskify\Data\PaginatedData;
use VmeRetail\ServiceDeskify\Requests\Locations\ListLocationsRequest;
use VmeRetail\ServiceDeskify\ServiceDeskifyClient;

final readonly class LocationResource
{
    public function __construct(private ServiceDeskifyClient $client) {}

    /**
     * @return PaginatedData<LocationData>
     */
    public function all(?LocationQueryData $query = null): PaginatedData
    {
        $response = $this->client->send(new ListLocationsRequest(
            $query ?? new LocationQueryData,
        ));

        /** @var array<string, mixed> $payload */
        $payload = $response->json();

        return PaginatedData::fromArray($payload, LocationData::fromArray(...));
    }
}
