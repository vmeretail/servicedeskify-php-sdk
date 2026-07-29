<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Auth;

use VmeRetail\ServiceDeskify\Exceptions\AuthenticationException;

final readonly class Credentials
{
    public function __construct(
        public string $username,
        #[\SensitiveParameter]
        public string $password,
        public string $clientId,
        #[\SensitiveParameter]
        public string $clientSecret,
    ) {}

    public function ensureComplete(): void
    {
        if (
            $this->username === ''
            || $this->password === ''
            || $this->clientId === ''
            || $this->clientSecret === ''
        ) {
            throw new AuthenticationException(
                'ServiceDeskify OAuth credentials are not fully configured.',
            );
        }
    }
}
