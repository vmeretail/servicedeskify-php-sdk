<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Resources;

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
