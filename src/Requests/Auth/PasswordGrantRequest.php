<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Requests\Auth;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;
use VmeRetail\ServiceDeskify\Auth\Credentials;

final class PasswordGrantRequest extends Request implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(private readonly Credentials $credentials) {}

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
            'grant_type' => 'password',
            'client_id' => $this->credentials->clientId,
            'client_secret' => $this->credentials->clientSecret,
            'username' => $this->credentials->username,
            'password' => $this->credentials->password,
            'scope' => '*',
        ];
    }
}
