<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Resources;

use Generator;
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

    /**
     * Lazily retrieve posts from the requested page through to the final page.
     *
     * @return Generator<int, IncidentPostData>
     */
    public function lazy(?IncidentPostQueryData $query = null): Generator
    {
        $query ??= new IncidentPostQueryData;
        $page = max(1, $query->page);

        do {
            $posts = $this->all($query->withPage($page));

            if ($posts->items === []) {
                return;
            }

            foreach ($posts as $post) {
                yield $post;
            }

            $page++;
        } while ($page <= $posts->lastPage);
    }

    /**
     * Iterate lazily, stopping early when the callback returns false.
     *
     * @param  callable(IncidentPostData): mixed  $callback
     */
    public function each(callable $callback, ?IncidentPostQueryData $query = null): void
    {
        foreach ($this->lazy($query) as $post) {
            if ($callback($post) === false) {
                return;
            }
        }
    }

    public function create(CreateIncidentPostData $data): IncidentPostData
    {
        $response = $this->client->send(new CreateIncidentPostRequest($this->incidentId, $data));

        /** @var array<string, mixed> $payload */
        $payload = $response->json('data', []);

        return IncidentPostData::fromArray($payload);
    }

    public function download(int|string $postId, int|string $attachmentId): AttachmentDownload
    {
        $response = $this->client->send(new DownloadIncidentAttachmentRequest(
            $this->incidentId,
            (int) $postId,
            (int) $attachmentId,
        ));

        return new AttachmentDownload(
            fileName: $this->fileName($response->header('Content-Disposition'), (int) $attachmentId),
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
            return $this->sanitizeFileName(rawurldecode($matches[1]), $attachmentId);
        }

        if ($value !== null && preg_match('/filename=\"?([^\";]+)\"?/i', $value, $matches) === 1) {
            return $this->sanitizeFileName($matches[1], $attachmentId);
        }

        return "attachment-{$attachmentId}";
    }

    private function sanitizeFileName(string $fileName, int $attachmentId): string
    {
        $fileName = str_replace('\\', '/', $fileName);
        $fileName = basename($fileName);
        $fileName = preg_replace('/[\x00-\x1F\x7F]/', '', $fileName) ?? '';
        $fileName = trim($fileName);

        return $fileName === '' || $fileName === '.' || $fileName === '..'
            ? "attachment-{$attachmentId}"
            : $fileName;
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
