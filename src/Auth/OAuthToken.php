<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Auth;

final readonly class OAuthToken
{
    public function __construct(
        public string $accessToken,
        public ?string $refreshToken,
        public string $tokenType,
        public int $expiresAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromTokenResponse(
        array $data,
        ?int $now = null,
        ?string $existingRefreshToken = null,
    ): self {
        $refreshToken = $data['refresh_token'] ?? null;

        return new self(
            accessToken: (string) ($data['access_token'] ?? ''),
            refreshToken: is_string($refreshToken) && $refreshToken !== ''
                ? $refreshToken
                : $existingRefreshToken,
            tokenType: (string) ($data['token_type'] ?? 'Bearer'),
            expiresAt: ($now ?? time()) + max(0, (int) ($data['expires_in'] ?? 0)),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            accessToken: (string) ($data['access_token'] ?? ''),
            refreshToken: isset($data['refresh_token']) ? (string) $data['refresh_token'] : null,
            tokenType: (string) ($data['token_type'] ?? 'Bearer'),
            expiresAt: (int) ($data['expires_at'] ?? 0),
        );
    }

    /**
     * @return array{access_token: string, refresh_token: ?string, token_type: string, expires_at: int}
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'token_type' => $this->tokenType,
            'expires_at' => $this->expiresAt,
        ];
    }

    public function isUsable(int $leeway = 60, ?int $now = null): bool
    {
        return $this->accessToken !== '' && $this->expiresAt > ($now ?? time()) + $leeway;
    }
}
