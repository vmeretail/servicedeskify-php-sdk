<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Resources;

use Generator;
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

    /**
     * Lazily retrieve locations from the requested page through to the final page.
     *
     * @return Generator<int, LocationData>
     */
    public function lazy(?LocationQueryData $query = null): Generator
    {
        $query ??= new LocationQueryData;
        $page = max(1, $query->page);

        do {
            $locations = $this->all($query->withPage($page));

            if ($locations->items === []) {
                return;
            }

            foreach ($locations as $location) {
                yield $location;
            }

            $page++;
        } while ($page <= $locations->lastPage);
    }

    /**
     * Iterate lazily, stopping early when the callback returns false.
     *
     * @param  callable(LocationData): mixed  $callback
     */
    public function each(callable $callback, ?LocationQueryData $query = null): void
    {
        foreach ($this->lazy($query) as $location) {
            if ($callback($location) === false) {
                return;
            }
        }
    }
}
