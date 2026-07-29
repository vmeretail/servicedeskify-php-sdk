<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Connectors;

use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;

final class ServiceDeskifyConnector extends Connector
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $accessToken = null,
        private readonly string $tokenType = 'Bearer',
    ) {
        $this->headers()->add('Accept', 'application/json');
    }

    public function resolveBaseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }

    protected function defaultAuth(): ?Authenticator
    {
        return $this->accessToken === null
            ? null
            : new TokenAuthenticator($this->accessToken, $this->tokenType);
    }
}
