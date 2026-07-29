<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Resources;

use Generator;
use VmeRetail\ServiceDeskify\Data\CreateIncidentData;
use VmeRetail\ServiceDeskify\Data\IncidentClosureData;
use VmeRetail\ServiceDeskify\Data\IncidentData;
use VmeRetail\ServiceDeskify\Data\IncidentQueryData;
use VmeRetail\ServiceDeskify\Data\PaginatedData;
use VmeRetail\ServiceDeskify\Requests\Incidents\CloseIncidentRequest;
use VmeRetail\ServiceDeskify\Requests\Incidents\CreateIncidentRequest;
use VmeRetail\ServiceDeskify\Requests\Incidents\GetIncidentRequest;
use VmeRetail\ServiceDeskify\Requests\Incidents\ListIncidentsRequest;
use VmeRetail\ServiceDeskify\Requests\Incidents\SetIncidentClosureDetailsRequest;
use VmeRetail\ServiceDeskify\ServiceDeskifyClient;

final readonly class IncidentResource
{
    public function __construct(private ServiceDeskifyClient $client) {}

    /**
     * @return PaginatedData<IncidentData>
     */
    public function all(?IncidentQueryData $query = null): PaginatedData
    {
        $response = $this->client->send(new ListIncidentsRequest(
            $query ?? new IncidentQueryData,
        ));

        /** @var array<string, mixed> $payload */
        $payload = $response->json();

        return PaginatedData::fromArray($payload, IncidentData::fromArray(...));
    }

    /**
     * Lazily retrieve incidents from the requested page through to the final page.
     *
     * @return Generator<int, IncidentData>
     */
    public function lazy(?IncidentQueryData $query = null): Generator
    {
        $query ??= new IncidentQueryData;
        $page = max(1, $query->page);

        do {
            $incidents = $this->all(new IncidentQueryData(
                page: $page,
                status: $query->status,
            ));

            foreach ($incidents as $incident) {
                yield $incident;
            }

            $page = $incidents->currentPage + 1;
        } while ($incidents->currentPage < $incidents->lastPage);
    }

    /**
     * @param  callable(IncidentData): mixed  $callback
     */
    public function each(callable $callback, ?IncidentQueryData $query = null): void
    {
        foreach ($this->lazy($query) as $incident) {
            if ($callback($incident) === false) {
                return;
            }
        }
    }

    public function find(int $incidentId): IncidentData
    {
        $response = $this->client->send(new GetIncidentRequest($incidentId));

        /** @var array<string, mixed> $data */
        $data = $response->json('data', []);

        return IncidentData::fromArray($data);
    }

    public function create(CreateIncidentData $data): IncidentData
    {
        $response = $this->client->send(new CreateIncidentRequest($data));

        /** @var array<string, mixed> $payload */
        $payload = $response->json('data', []);

        return IncidentData::fromArray($payload);
    }

    public function close(int $incidentId): void
    {
        $this->client->send(new CloseIncidentRequest($incidentId));
    }

    public function addClosureDetails(int $incidentId, IncidentClosureData $data): void
    {
        $this->client->send(new SetIncidentClosureDetailsRequest($incidentId, $data));
    }

    public function posts(int $incidentId): IncidentPostResource
    {
        return new IncidentPostResource($this->client, $incidentId);
    }
}
