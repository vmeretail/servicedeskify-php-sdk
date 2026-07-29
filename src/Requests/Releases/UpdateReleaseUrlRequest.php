<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Requests\Releases;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

final class UpdateReleaseUrlRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        private readonly int $releaseId,
        private readonly string $url,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/api/releases/{$this->releaseId}";
    }

    /**
     * @return array{url: string}
     */
    protected function defaultBody(): array
    {
        return ['url' => $this->url];
    }
}
