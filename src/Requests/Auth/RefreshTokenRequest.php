<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Requests\Auth;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;
use VmeRetail\ServiceDeskify\Auth\Credentials;

final class RefreshTokenRequest extends Request implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly Credentials $credentials,
        private readonly string $refreshToken,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/oauth/token';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultBody(): array
    {
        return [
            'grant_type' => 'refresh_token',
            'client_id' => $this->credentials->clientId,
            'client_secret' => $this->credentials->clientSecret,
            'refresh_token' => $this->refreshToken,
            'scope' => '*',
        ];
    }
}
