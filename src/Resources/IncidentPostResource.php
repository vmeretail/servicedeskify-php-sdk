<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Resources;

use VmeRetail\ServiceDeskify\Data\AttachmentDownload;
use VmeRetail\ServiceDeskify\Data\CreateIncidentPostData;
use VmeRetail\ServiceDeskify\Data\IncidentPostData;
use VmeRetail\ServiceDeskify\Data\IncidentPostQueryData;
use VmeRetail\ServiceDeskify\Data\PaginatedData;
use VmeRetail\ServiceDeskify\Requests\IncidentPosts\CreateIncidentPostRequest;
use VmeRetail\ServiceDeskify\Requests\IncidentPosts\DownloadIncidentAttachmentRequest;
use VmeRetail\ServiceDeskify\Requests\IncidentPosts\ListIncidentPostsRequest;
use VmeRetail\ServiceDeskify\ServiceDeskifyClient;

final readonly class IncidentPostResource
{
    public function __construct(
        private ServiceDeskifyClient $client,
        private int $incidentId,
    ) {}

    /**
     * @return PaginatedData<IncidentPostData>
     */
    public function all(?IncidentPostQueryData $query = null): PaginatedData
    {
        $response = $this->client->send(new ListIncidentPostsRequest(
            $this->incidentId,
            $query ?? new IncidentPostQueryData,
        ));

        /** @var array<string, mixed> $payload */
        $payload = $response->json();

        return PaginatedData::fromArray($payload, IncidentPostData::fromArray(...));
    }

    public function create(CreateIncidentPostData $data): IncidentPostData
    {
        $response = $this->client->send(new CreateIncidentPostRequest($this->incidentId, $data));

        /** @var array<string, mixed> $payload */
        $payload = $response->json('data', []);

        return IncidentPostData::fromArray($payload);
    }

    public function download(int $postId, int $attachmentId): AttachmentDownload
    {
        $response = $this->client->send(new DownloadIncidentAttachmentRequest(
            $this->incidentId,
            $postId,
            $attachmentId,
        ));

        return new AttachmentDownload(
            fileName: $this->fileName($response->header('Content-Disposition'), $attachmentId),
            contentType: $this->headerValue($response->header('Content-Type')) ?? 'application/octet-stream',
            stream: $response->stream(),
        );
    }

    /**
     * @param  array<array-key, mixed>|string|null  $header
     */
    private function fileName(string|array|null $header, int $attachmentId): string
    {
        $value = $this->headerValue($header);

        if ($value !== null && preg_match("/filename\\*=UTF-8''([^;]+)/i", $value, $matches) === 1) {
            return rawurldecode($matches[1]);
        }

        if ($value !== null && preg_match('/filename=\"?([^\";]+)\"?/i', $value, $matches) === 1) {
            return trim($matches[1]);
        }

        return "attachment-{$attachmentId}";
    }

    /**
     * @param  array<array-key, mixed>|string|null  $header
     */
    private function headerValue(string|array|null $header): ?string
    {
        if (is_string($header)) {
            return $header;
        }

        if (is_array($header) && isset($header[0]) && is_string($header[0])) {
            return $header[0];
        }

        return null;
    }
}
