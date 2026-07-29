<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Resources;

use VmeRetail\ServiceDeskify\Data\ReleaseData;
use VmeRetail\ServiceDeskify\Data\ReleaseQueryData;
use VmeRetail\ServiceDeskify\Requests\Releases\ListReleasesRequest;
use VmeRetail\ServiceDeskify\Requests\Releases\UpdateReleaseUrlRequest;
use VmeRetail\ServiceDeskify\ServiceDeskifyClient;

final readonly class ReleaseResource
{
    public function __construct(private ServiceDeskifyClient $client) {}

    /**
     * @return array<int, ReleaseData>
     */
    public function all(?ReleaseQueryData $query = null): array
    {
        $response = $this->client->send(new ListReleasesRequest(
            $query ?? new ReleaseQueryData,
        ));
        $payload = $response->json();

        return array_values(array_map(
            static fn (array $release): ReleaseData => ReleaseData::fromArray($release),
            array_filter($payload, 'is_array'),
        ));
    }

    public function updateUrl(int|string $releaseId, string $url): void
    {
        $this->client->send(new UpdateReleaseUrlRequest((int) $releaseId, $url));
    }
}
