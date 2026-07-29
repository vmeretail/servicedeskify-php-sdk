<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Requests\Concerns;

use Illuminate\Http\UploadedFile;
use Saloon\Contracts\Body\BodyRepository;
use Saloon\Contracts\Body\HasBody;
use Saloon\Data\MultipartValue;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Repositories\Body\JsonBodyRepository;
use Saloon\Repositories\Body\MultipartBodyRepository;
use SplFileInfo;
use VmeRetail\ServiceDeskify\Contracts\PreparesForRetry;

abstract class PayloadRequest extends Request implements HasBody, PreparesForRetry
{
    private BodyRepository $payloadBody;

    /**
     * @param  array<string, bool|int|string>  $payload
     * @param  array<int, UploadedFile|SplFileInfo|string>  $attachments
     */
    protected function setPayload(array $payload, array $attachments = []): void
    {
        if ($attachments === []) {
            $this->payloadBody = new JsonBodyRepository($payload);

            return;
        }

        $values = [];
        foreach ($payload as $name => $value) {
            $values[] = new MultipartValue($name, is_bool($value) ? (int) $value : $value);
        }

        $this->payloadBody = new MultipartBodyRepository([
            ...$values,
            ...AttachmentNormalizer::multipartValues($attachments),
        ]);
    }

    public function body(): BodyRepository
    {
        return $this->payloadBody;
    }

    public function prepareForRetry(): void
    {
        if (! $this->payloadBody instanceof MultipartBodyRepository) {
            return;
        }

        foreach ($this->payloadBody->all() as $part) {
            if (! is_resource($part->value)) {
                continue;
            }

            $metadata = stream_get_meta_data($part->value);

            if ($metadata['seekable']) {
                rewind($part->value);
            }
        }
    }

    public function boot(PendingRequest $pendingRequest): void
    {
        if ($this->payloadBody instanceof MultipartBodyRepository) {
            $pendingRequest->headers()->add(
                'Content-Type',
                'multipart/form-data; boundary='.$this->payloadBody->getBoundary(),
            );

            return;
        }

        $pendingRequest->headers()->add('Content-Type', 'application/json');
    }
}
