<?php

declare(strict_types=1);

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Encryption\Encrypter;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;
use VmeRetail\ServiceDeskify\Auth\TokenManager;
use VmeRetail\ServiceDeskify\Exceptions\AuthenticationException;
use VmeRetail\ServiceDeskify\Exceptions\NotFoundException;
use VmeRetail\ServiceDeskify\Exceptions\ServiceDeskifyRequestException;
use VmeRetail\ServiceDeskify\Exceptions\ValidationException;
use VmeRetail\ServiceDeskify\Facades\ServiceDeskify;
use VmeRetail\ServiceDeskify\Requests\Auth\PasswordGrantRequest;
use VmeRetail\ServiceDeskify\Requests\Auth\RefreshTokenRequest;
use VmeRetail\ServiceDeskify\Requests\Incidents\GetIncidentRequest;

it('authenticates with the unchanged password grant and reuses the cached token', function (): void {
    $mock = MockClient::global([
        MockResponse::make([
            'access_token' => 'access-one',
            'refresh_token' => 'refresh-one',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]),
        MockResponse::make(['data' => incidentPayload(41)]),
        MockResponse::make(['data' => incidentPayload(42)]),
    ]);

    expect(ServiceDeskify::incidents()->find(41)->id)->toBe(41)
        ->and(ServiceDeskify::incidents()->find(42)->id)->toBe(42);

    $mock->assertSentCount(1, PasswordGrantRequest::class);
    $mock->assertSentCount(2, GetIncidentRequest::class);
    $mock->assertSent(function ($request, Response $response): bool {
        if (! $request instanceof PasswordGrantRequest) {
            return false;
        }

        $pending = $response->getPendingRequest();
        $body = $pending->body()?->all();

        return $pending->getMethod() === Method::POST
            && $pending->getUrl() === 'https://helpdesk.example.test/oauth/token'
            && $pending->headers()->get('Content-Type') === 'application/x-www-form-urlencoded'
            && is_array($body)
            && $body['grant_type'] === 'password'
            && $body['client_id'] === '2'
            && $body['client_secret'] === 'secret-client'
            && $body['username'] === 'sdk@example.test'
            && $body['password'] === 'secret-password'
            && $body['scope'] === '*';
    });
    $mock->assertSent(function ($request, Response $response): bool {
        $pending = $response->getPendingRequest();

        return $request instanceof GetIncidentRequest
            && $pending->getMethod() === Method::GET
            && $pending->getUrl() === 'https://helpdesk.example.test/api/incidents/41'
            && $pending->headers()->get('Authorization') === 'Bearer access-one'
            && $pending->headers()->get('Accept') === 'application/json';
    });
});

it('stores oauth tokens encrypted in the configured laravel cache', function (): void {
    MockClient::global([
        oauthResponse('encrypted-access', 'encrypted-refresh'),
    ]);

    app(TokenManager::class)->token();

    $encrypted = app(CacheFactory::class)
        ->store('array')
        ->get(serviceDeskifyTokenCacheKey());

    expect($encrypted)->toBeString()
        ->not->toContain('encrypted-access')
        ->not->toContain('encrypted-refresh');

    $decrypted = app(Encrypter::class)->decrypt($encrypted);

    expect($decrypted)->toBeArray()
        ->and($decrypted['access_token'])->toBe('encrypted-access')
        ->and($decrypted['refresh_token'])->toBe('encrypted-refresh');
});

it('refreshes an expired token and retains a replacement refresh token', function (): void {
    $mock = MockClient::global([
        MockResponse::make([
            'access_token' => 'expiring-access',
            'refresh_token' => 'refresh-one',
            'token_type' => 'Bearer',
            'expires_in' => 30,
        ]),
        MockResponse::make([
            'access_token' => 'fresh-access',
            'refresh_token' => 'refresh-two',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]),
    ]);

    $tokens = app(TokenManager::class);

    expect($tokens->token()->accessToken)->toBe('expiring-access')
        ->and($tokens->token()->accessToken)->toBe('fresh-access');

    $mock->assertSentCount(1, PasswordGrantRequest::class);
    $mock->assertSentCount(1, RefreshTokenRequest::class);
    $mock->assertSent(function ($request, Response $response): bool {
        if (! $request instanceof RefreshTokenRequest) {
            return false;
        }

        $pending = $response->getPendingRequest();
        $body = $pending->body()?->all();

        return $pending->getMethod() === Method::POST
            && $pending->getUrl() === 'https://helpdesk.example.test/oauth/token'
            && $pending->headers()->get('Content-Type') === 'application/x-www-form-urlencoded'
            && is_array($body)
            && $body['grant_type'] === 'refresh_token'
            && $body['refresh_token'] === 'refresh-one';
    });
});

it('invalidates and reauthenticates once after an api 401', function (): void {
    $mock = MockClient::global([
        MockResponse::make([
            'access_token' => 'rejected-access',
            'refresh_token' => 'rejected-refresh',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]),
        MockResponse::make(['message' => 'Unauthenticated.'], 401),
        MockResponse::make([
            'access_token' => 'replacement-access',
            'refresh_token' => 'replacement-refresh',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]),
        MockResponse::make(['data' => incidentPayload(99)]),
    ]);

    expect(ServiceDeskify::incidents()->find(99)->id)->toBe(99);

    $mock->assertSentCount(2, PasswordGrantRequest::class);
    $mock->assertSentCount(2, GetIncidentRequest::class);
});

it('falls back to the password grant when refreshing a cached token fails', function (): void {
    $mock = MockClient::global([
        oauthResponse('expiring-access', 'invalid-refresh', expiresIn: 30),
        MockResponse::make(['error' => 'invalid_grant'], 400),
        oauthResponse('replacement-access', 'replacement-refresh'),
    ]);

    $tokens = app(TokenManager::class);

    expect($tokens->token()->accessToken)->toBe('expiring-access')
        ->and($tokens->token()->accessToken)->toBe('replacement-access');

    $mock->assertSentCount(1, RefreshTokenRequest::class);
    $mock->assertSentCount(2, PasswordGrantRequest::class);
});

it('times out cleanly when another process owns the oauth refresh lock', function (): void {
    $store = app(CacheFactory::class)->store('array')->getStore();

    expect($store)->toBeInstanceOf(LockProvider::class);

    $lock = $store->lock(serviceDeskifyTokenCacheKey().'.lock', 10);
    $lock->get();

    try {
        expect(fn () => app(TokenManager::class)->token())
            ->toThrow(AuthenticationException::class, 'OAuth token lock');
    } finally {
        $lock->release();
    }
});

it('maps validation failures into a typed exception', function (): void {
    MockClient::global([
        MockResponse::make([
            'access_token' => 'access',
            'refresh_token' => 'refresh',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]),
        MockResponse::make([
            'message' => 'The given data was invalid.',
            'errors' => ['title' => ['The title field is required.']],
        ], 422),
    ]);

    try {
        ServiceDeskify::incidents()->find(1);
        test()->fail('A validation exception was not thrown.');
    } catch (ValidationException $exception) {
        expect($exception->status)->toBe(422)
            ->and($exception->errors)->toHaveKey('title')
            ->and($exception->responseBody)->toContain('title');
    }
});

it('does not expose configured secrets in authentication errors', function (): void {
    MockClient::global([
        MockResponse::make([
            'error' => 'invalid_grant',
            'error_description' => 'secret-password and secret-client were rejected.',
        ], 400),
    ]);

    try {
        app(TokenManager::class)->token();
        test()->fail('An authentication exception was not thrown.');
    } catch (AuthenticationException $exception) {
        expect($exception->getMessage())
            ->toContain('[redacted]')
            ->not->toContain('secret-password')
            ->not->toContain('secret-client')
            ->and($exception->responseBody)
            ->not->toContain('secret-password')
            ->not->toContain('secret-client')
            ->and($exception->getPrevious())->toBeNull();
    }
});

it('maps invalid oauth responses into an authentication exception', function (string $body): void {
    MockClient::global([
        MockResponse::make($body, 200),
    ]);

    expect(fn () => app(TokenManager::class)->token())
        ->toThrow(AuthenticationException::class, 'invalid OAuth response');
})->with([
    'malformed json' => ['not-json'],
    'scalar json' => ['null'],
]);

it('maps scalar json api failures into the documented exception hierarchy', function (): void {
    MockClient::global([
        oauthResponse('first-access', 'first-refresh'),
        MockResponse::make('"Service unavailable"', 500),
    ]);

    expect(fn () => ServiceDeskify::incidents()->find(1))
        ->toThrow(ServiceDeskifyRequestException::class, 'Service unavailable');
});

it('maps api failures into the documented exception hierarchy', function (
    int $status,
    string $exceptionClass,
): void {
    $responses = [
        oauthResponse('first-access', 'first-refresh'),
        MockResponse::make(['message' => "Failure {$status}"], $status),
    ];

    if ($status === 401) {
        $responses[] = oauthResponse('retry-access', 'retry-refresh');
        $responses[] = MockResponse::make(['message' => 'Still unauthenticated'], 401);
    }

    MockClient::global($responses);

    expect(fn () => ServiceDeskify::incidents()->find(1))
        ->toThrow($exceptionClass);
})->with([
    'bad request' => [400, ServiceDeskifyRequestException::class],
    'unauthenticated after retry' => [401, AuthenticationException::class],
    'not found' => [404, NotFoundException::class],
    'validation failure' => [422, ValidationException::class],
    'server failure' => [500, ServiceDeskifyRequestException::class],
]);

function oauthResponse(
    string $accessToken,
    string $refreshToken,
    int $expiresIn = 3600,
): MockResponse {
    return MockResponse::make([
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'token_type' => 'Bearer',
        'expires_in' => $expiresIn,
    ]);
}

function serviceDeskifyTokenCacheKey(): string
{
    return 'servicedeskify.tests.oauth-token.'.hash('sha256', implode('|', [
        'https://helpdesk.example.test',
        'sdk@example.test',
        '2',
    ]));
}

/**
 * @return array<string, mixed>
 */
function incidentPayload(int $id): array
{
    return [
        'id' => $id,
        'title' => "Incident {$id}",
        'summary' => 'Details',
        'reporter' => null,
        'location' => [
            'name' => 'Store',
            'customer' => 'Customer',
            'store_number' => '722',
        ],
        'assignee' => null,
        'status' => 'Active',
        'workflow_state' => 'waiting_for_support',
        'created_date' => '2026-07-28 10:00:00',
        'priority' => 'Critical',
        'symptom' => null,
        'symptom_solution' => null,
        'cause' => null,
        'permanent_resolution' => null,
        'attachments' => [],
    ];
}
