<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Auth;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use JsonException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Request;
use Saloon\Http\Response;
use VmeRetail\ServiceDeskify\Connectors\ServiceDeskifyConnector;
use VmeRetail\ServiceDeskify\Exceptions\AuthenticationException;
use VmeRetail\ServiceDeskify\Exceptions\ResponseExceptionMapper;
use VmeRetail\ServiceDeskify\Requests\Auth\PasswordGrantRequest;
use VmeRetail\ServiceDeskify\Requests\Auth\RefreshTokenRequest;

final class TokenManager
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly Credentials $credentials,
        private readonly CacheFactory $cache,
        private readonly Encrypter $encrypter,
        private readonly ?string $cacheStore = null,
        private readonly string $cacheKey = 'servicedeskify.oauth-token',
        private readonly int $cacheTtl = 2_592_000,
        private readonly int $expiryLeeway = 60,
        private readonly int $lockSeconds = 10,
        private readonly int $lockWaitSeconds = 5,
    ) {}

    public function token(bool $forcePasswordGrant = false): OAuthToken
    {
        $cached = $this->load();

        if (! $forcePasswordGrant && $cached?->isUsable($this->expiryLeeway)) {
            return $cached;
        }

        return $this->synchronised(function () use ($forcePasswordGrant): OAuthToken {
            $cached = $this->load();

            if (! $forcePasswordGrant && $cached?->isUsable($this->expiryLeeway)) {
                return $cached;
            }

            if (! $forcePasswordGrant && $cached?->refreshToken !== null) {
                try {
                    return $this->store($this->requestToken(
                        new RefreshTokenRequest($this->credentials, $cached->refreshToken),
                        existingRefreshToken: $cached->refreshToken,
                    ));
                } catch (AuthenticationException) {
                    $this->forget();
                }
            }

            return $this->store($this->requestToken(
                new PasswordGrantRequest($this->credentials),
            ));
        });
    }

    public function forget(): void
    {
        $this->repository()->forget($this->resolvedCacheKey());
    }

    private function requestToken(
        Request $request,
        ?string $existingRefreshToken = null,
    ): OAuthToken {
        $this->credentials->ensureComplete();

        try {
            $response = (new ServiceDeskifyConnector($this->baseUrl))->send($request);
        } catch (FatalRequestException|RequestException $exception) {
            throw new AuthenticationException(
                message: 'ServiceDeskify authentication could not be completed.',
                previous: $exception,
            );
        }

        if ($response->failed()) {
            $mapped = ResponseExceptionMapper::fromResponse($response);
            $errors = $mapped->errors;

            array_walk_recursive($errors, function (mixed &$value): void {
                if (is_string($value)) {
                    $value = $this->redact($value);
                }
            });

            throw new AuthenticationException(
                message: $this->redact($mapped->getMessage()),
                status: $mapped->status,
                responseBody: $mapped->responseBody === null
                    ? null
                    : $this->redactOAuthResponse($mapped->responseBody),
                errors: $errors,
            );
        }

        $data = $this->responseData($response);
        $token = OAuthToken::fromTokenResponse(
            $data,
            existingRefreshToken: $existingRefreshToken,
        );

        if ($token->accessToken === '' || $token->expiresAt <= time()) {
            throw new AuthenticationException(
                message: 'ServiceDeskify returned an invalid OAuth token response.',
                status: $response->status(),
                responseBody: $this->redactOAuthResponse($response->body()),
            );
        }

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    private function responseData(Response $response): array
    {
        try {
            $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new AuthenticationException(
                message: 'ServiceDeskify returned an invalid OAuth response.',
                status: $response->status(),
                responseBody: $this->redactOAuthResponse($response->body()),
                previous: $exception,
            );
        }

        if (! is_array($data)) {
            throw new AuthenticationException(
                message: 'ServiceDeskify returned an invalid OAuth response.',
                status: $response->status(),
                responseBody: $this->redactOAuthResponse($response->body()),
            );
        }

        return $data;
    }

    private function redact(string $value): string
    {
        foreach ([$this->credentials->password, $this->credentials->clientSecret] as $secret) {
            if ($secret !== '') {
                $value = str_replace($secret, '[redacted]', $value);
            }
        }

        return $value;
    }

    private function redactOAuthResponse(string $body): string
    {
        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->redact($body);
        }

        if (! is_array($data)) {
            return $this->redact($body);
        }

        $data = $this->redactTokenFields($data);

        try {
            return $this->redact(json_encode(
                $data,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ));
        } catch (JsonException) {
            return 'ServiceDeskify returned an OAuth response containing redacted credentials.';
        }
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function redactTokenFields(array $data): array
    {
        foreach ($data as $key => $value) {
            $normalizedKey = is_string($key)
                ? strtolower(str_replace(['_', '-'], '', $key))
                : '';

            if (in_array($normalizedKey, ['accesstoken', 'refreshtoken', 'idtoken'], true)) {
                unset($data[$key]);

                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->redactTokenFields($value);
            }
        }

        return $data;
    }

    private function load(): ?OAuthToken
    {
        $encrypted = $this->repository()->get($this->resolvedCacheKey());

        if (! is_string($encrypted) || $encrypted === '') {
            return null;
        }

        try {
            $data = $this->encrypter->decrypt($encrypted);

            return is_array($data) ? OAuthToken::fromArray($data) : null;
        } catch (DecryptException) {
            $this->forget();

            return null;
        }
    }

    private function store(OAuthToken $token): OAuthToken
    {
        $encrypted = $this->encrypter->encrypt($token->toArray());
        $this->repository()->put(
            $this->resolvedCacheKey(),
            $encrypted,
            max($this->cacheTtl, $token->expiresAt - time()),
        );

        return $token;
    }

    /**
     * @template TValue
     *
     * @param  callable(): TValue  $callback
     * @return TValue
     */
    private function synchronised(callable $callback): mixed
    {
        $store = $this->repository()->getStore();

        if (! $store instanceof LockProvider) {
            throw new AuthenticationException(
                'The configured ServiceDeskify cache store does not support atomic locks.',
            );
        }

        try {
            return $store
                ->lock($this->resolvedCacheKey().'.lock', $this->lockSeconds)
                ->block($this->lockWaitSeconds, $callback);
        } catch (LockTimeoutException $exception) {
            throw new AuthenticationException(
                message: 'Timed out while waiting for the ServiceDeskify OAuth token lock.',
                previous: $exception,
            );
        }
    }

    private function repository(): Repository
    {
        return $this->cache->store($this->cacheStore);
    }

    private function resolvedCacheKey(): string
    {
        return $this->cacheKey.'.'.hash('sha256', implode('|', [
            rtrim($this->baseUrl, '/'),
            $this->credentials->username,
            $this->credentials->clientId,
        ]));
    }
}
