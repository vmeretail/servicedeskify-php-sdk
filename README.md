# ServiceDeskify Laravel SDK

A Laravel package for ServiceDeskify, built on [Saloon](https://docs.saloon.dev/).

The package handles OAuth password-grant authentication, encrypted token caching,
token refresh and one automatic re-authentication after an API `401`.

## Requirements

- PHP 8.2 or newer
- Laravel 11, 12 or 13

Laravel 11 is supported for applications that still require it, although its
upstream security-fix window ended in March 2026. Prefer Laravel 12 or 13 for
new applications.

## Installation

```bash
composer require vmeretail/servicedeskify-php-sdk
```

The package service provider is discovered automatically. Publish the configuration
file if you need to customise caching or token timing:

```bash
php artisan vendor:publish --tag=servicedeskify-config
```

Configure the credentials supplied by VME Retail:

```dotenv
SERVICEDESKIFY_URL=https://helpdesk.vme.co
SERVICEDESKIFY_USERNAME=
SERVICEDESKIFY_PASSWORD=
SERVICEDESKIFY_CLIENT_ID=
SERVICEDESKIFY_CLIENT_SECRET=
```

For the ServiceDeskify test environment, set
`SERVICEDESKIFY_URL=https://helpdesk-test.vme.co`.

The configured Laravel cache store is used by default. A different store may be
selected with `SERVICEDESKIFY_CACHE_STORE`. Advanced token-cache settings are
also configurable. The selected store must support Laravel atomic locks.

```dotenv
SERVICEDESKIFY_CACHE_STORE=
SERVICEDESKIFY_CACHE_KEY=servicedeskify.oauth-token
SERVICEDESKIFY_CACHE_TTL=2592000
SERVICEDESKIFY_TOKEN_EXPIRY_LEEWAY=60
SERVICEDESKIFY_TOKEN_LOCK_SECONDS=10
SERVICEDESKIFY_TOKEN_LOCK_WAIT_SECONDS=5
```

## Incidents

Import the package facade and typed input objects:

```php
use VmeRetail\ServiceDeskify\Data\CreateIncidentData;
use VmeRetail\ServiceDeskify\Enums\IncidentPriority;
use VmeRetail\ServiceDeskify\Facades\ServiceDeskify;

$incident = ServiceDeskify::incidents()->create(new CreateIncidentData(
    title: 'Till 2 is not rebooting',
    details: 'Till 2 remains offline after a restart.',
    storeNumber: 722,
    priority: IncidentPriority::P1,
));
```

Incidents can be listed, retrieved, closed and given closure details:

```php
use VmeRetail\ServiceDeskify\Data\IncidentClosureData;
use VmeRetail\ServiceDeskify\Data\IncidentQueryData;
use VmeRetail\ServiceDeskify\Enums\IncidentStatus;

$openIncidents = ServiceDeskify::incidents()->all(new IncidentQueryData(
    status: IncidentStatus::Open,
));

$incident = ServiceDeskify::incidents()->find(123);

ServiceDeskify::incidents()->addClosureDetails(123, new IncidentClosureData(
    symptom: 'Till could not connect',
    symptomSolution: 'Restarted the service',
    cause: 'The service had stopped',
    permanentResolution: 'Monitoring has been added',
));

ServiceDeskify::incidents()->close(123);
```

Paginated methods return `PaginatedData` with typed `items` and the usual page,
total, link and range values. A page can also be iterated directly or passed to
`count()`.

To process every incident without hand-rolling the service's fixed 50-item page
loop, use `lazy()` or `each()`:

```php
foreach (ServiceDeskify::incidents()->lazy() as $incident) {
    // Process one incident at a time.
}
```

## Replies and attachments

```php
use Illuminate\Http\UploadedFile;
use VmeRetail\ServiceDeskify\Data\CreateIncidentPostData;

$reply = ServiceDeskify::incidents()->posts(123)->create(
    new CreateIncidentPostData(
        details: 'The requested log is attached.',
        attachments: [
            new UploadedFile('/tmp/till.log', 'till.log'),
        ],
    ),
);
```

Attachments may be Laravel `UploadedFile` instances, `SplFileInfo` instances or
readable filesystem paths. Requests without attachments are JSON; requests with
attachments are multipart.

Download an attachment using its incident, post and attachment IDs:

```php
$download = ServiceDeskify::incidents()
    ->posts(123)
    ->download(postId: 456, attachmentId: 789);

$download->saveTo(storage_path('app/'.$download->fileName));
```

## Locations, software assets and releases

```php
use Carbon\CarbonImmutable;
use VmeRetail\ServiceDeskify\Data\CreateSoftwareAssetReleaseData;
use VmeRetail\ServiceDeskify\Data\LocationQueryData;
use VmeRetail\ServiceDeskify\Data\ReleaseQueryData;

$locations = ServiceDeskify::locations()->all(new LocationQueryData(
    store: '722',
    customer: 'Customer Name',
));

$assets = ServiceDeskify::softwareAssets()->all();
$asset = ServiceDeskify::softwareAssets()->find(10);

$created = ServiceDeskify::softwareAssets()->createRelease(
    10,
    new CreateSoftwareAssetReleaseData(
        version: '4.2.0',
        date: CarbonImmutable::parse('2026-07-28'),
    ),
);

$releases = ServiceDeskify::releases()->all(new ReleaseQueryData(
    fromDate: CarbonImmutable::parse('2026-07-01'),
));

ServiceDeskify::releases()->updateUrl(
    releaseId: 20,
    url: 'https://example.test/releases/4.2.0',
);
```

Software-asset lists return summary DTOs containing the ID, name and current
release ID. `find()` returns the full software-asset DTO. Creating a release
requires an agent account, and posting a version that already exists for that
software asset results in a `ValidationException`.

## Errors

Failed responses are mapped to package exceptions:

- `AuthenticationException` for `401` and `403`
- `NotFoundException` for `404`
- `ValidationException` for `422`
- `ServiceDeskifyRequestException` for other API and transport failures

Exceptions expose the HTTP status, response body and validation errors where
available. Credentials are never included in exception messages.

## Testing consuming applications

The package uses Saloon core and does not require Saloon's Laravel plugin. Use
Saloon's global `MockClient` and provide an OAuth response before API responses:

```php
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use VmeRetail\ServiceDeskify\Facades\ServiceDeskify;

$mock = MockClient::global([
    MockResponse::make([
        'access_token' => 'test-token',
        'refresh_token' => 'test-refresh-token',
        'token_type' => 'Bearer',
        'expires_in' => 3600,
    ]),
    MockResponse::make([
        'data' => [
            'id' => 123,
            'title' => 'Test incident',
            'summary' => 'Test details',
            'attachments' => [],
        ],
    ]),
]);

$incident = ServiceDeskify::incidents()->find(123);

$mock->assertSentCount(2);
MockClient::destroyGlobal();
```

Destroy the global mock in each test's teardown to prevent it leaking into other
tests.

## Migrating from v2

Version 3 is a complete rewrite. The following v2 classes have been removed:

- `PublicClient` and `AuthenticatedClientSingleton`
- `AbstractApiResource`
- `Incidents`, `IncidentPosts`, `IncidentClose` and `IncidentClosureDetails`
- `Locations`, `UserLocations` and `SoftwareAssets`
- `ServiceDeskifyResponseObject` and `ServiceDeskifyConstants`

Applications no longer authenticate manually or construct resource classes.
Configure credentials in Laravel, use the `ServiceDeskify` facade, pass typed
input objects and consume typed result objects.

The obsolete `users/locations` resource is not included because the current
ServiceDeskify service does not expose that route.
