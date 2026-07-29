<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Requests\IncidentPosts;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class DownloadIncidentAttachmentRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $incidentId,
        private readonly int $postId,
        private readonly int $attachmentId,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/api/incidents/{$this->incidentId}/posts/{$this->postId}/attachments/{$this->attachmentId}";
    }
}
