<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\UploadedFile;
use Saloon\Data\MultipartValue;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Repositories\Body\JsonBodyRepository;
use Saloon\Repositories\Body\MultipartBodyRepository;
use Tests\Support\ShortWriteStreamWrapper;
use VmeRetail\ServiceDeskify\Data\AttachmentDownload;
use VmeRetail\ServiceDeskify\Data\CreateIncidentData;
use VmeRetail\ServiceDeskify\Data\CreateIncidentPostData;
use VmeRetail\ServiceDeskify\Data\CreateSoftwareAssetReleaseData;
use VmeRetail\ServiceDeskify\Data\DataMapper;
use VmeRetail\ServiceDeskify\Data\IncidentClosureData;
use VmeRetail\ServiceDeskify\Data\IncidentData;
use VmeRetail\ServiceDeskify\Data\IncidentPostData;
use VmeRetail\ServiceDeskify\Data\IncidentPostQueryData;
use VmeRetail\ServiceDeskify\Data\IncidentQueryData;
use VmeRetail\ServiceDeskify\Data\LocationData;
use VmeRetail\ServiceDeskify\Data\LocationQueryData;
use VmeRetail\ServiceDeskify\Data\ReleaseQueryData;
use VmeRetail\ServiceDeskify\Data\SoftwareAssetData;
use VmeRetail\ServiceDeskify\Data\SoftwareAssetSummaryData;
use VmeRetail\ServiceDeskify\Enums\IncidentPriority;
use VmeRetail\ServiceDeskify\Enums\IncidentStatus;
use VmeRetail\ServiceDeskify\Enums\PostType;
use VmeRetail\ServiceDeskify\Enums\SortDirection;
use VmeRetail\ServiceDeskify\Exceptions\ServiceDeskifyRequestException;
use VmeRetail\ServiceDeskify\Exceptions\UnexpectedResponseException;
use VmeRetail\ServiceDeskify\Facades\ServiceDeskify;
use VmeRetail\ServiceDeskify\Requests\IncidentPosts\CreateIncidentPostRequest;
use VmeRetail\ServiceDeskify\Requests\IncidentPosts\DownloadIncidentAttachmentRequest;
use VmeRetail\ServiceDeskify\Requests\IncidentPosts\ListIncidentPostsRequest;
use VmeRetail\ServiceDeskify\Requests\Incidents\CloseIncidentRequest;
use VmeRetail\ServiceDeskify\Requests\Incidents\CreateIncidentRequest;
use VmeRetail\ServiceDeskify\Requests\Incidents\GetIncidentRequest;
use VmeRetail\ServiceDeskify\Requests\Incidents\ListIncidentsRequest;
use VmeRetail\ServiceDeskify\Requests\Incidents\SetIncidentClosureDetailsRequest;
use VmeRetail\ServiceDeskify\Requests\Locations\ListLocationsRequest;
use VmeRetail\ServiceDeskify\Requests\Releases\ListReleasesRequest;
use VmeRetail\ServiceDeskify\Requests\Releases\UpdateReleaseUrlRequest;
use VmeRetail\ServiceDeskify\Requests\SoftwareAssets\CreateSoftwareAssetReleaseRequest;
use VmeRetail\ServiceDeskify\Requests\SoftwareAssets\GetSoftwareAssetRequest;
use VmeRetail\ServiceDeskify\Requests\SoftwareAssets\ListSoftwareAssetsRequest;

it('exposes the complete selected helpdesk api through resource groups', function (): void {
    $mock = MockClient::global([
        tokenResponse(),
        MockResponse::make(paginated([incidentPayload(10)])),
        MockResponse::make(paginated([postPayload(20)])),
        MockResponse::make(['data' => postPayload(21)], 201),
        MockResponse::make('', 200),
        MockResponse::make('', 204),
        MockResponse::make(rawPaginated([locationPayload(30)])),
        MockResponse::make([softwareAssetPayload(40)]),
        MockResponse::make(softwareAssetPayload(40, withRelease: true)),
        MockResponse::make([
            'software_asset_version_id' => 50,
            'master_software_asset_version_id' => 60,
        ], 201),
        MockResponse::make([releasePayload(50)]),
        MockResponse::make('', 204),
        MockResponse::make('pdf-binary', 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="evidence.pdf"',
        ]),
    ]);

    $incidents = ServiceDeskify::incidents()->all(new IncidentQueryData(
        page: 2,
        status: IncidentStatus::Open,
    ));
    $posts = ServiceDeskify::incidents()->posts('10')->all(new IncidentPostQueryData(
        page: 1,
        perPage: 25,
        sortDirection: SortDirection::Descending,
        since: CarbonImmutable::parse('2026-07-01 12:00:00'),
    ));
    $createdPost = ServiceDeskify::incidents()->posts('10')->create(new CreateIncidentPostData(
        details: 'This is an internal update.',
        type: PostType::Private,
    ));
    ServiceDeskify::incidents()->close('10');
    ServiceDeskify::incidents()->addClosureDetails('10', new IncidentClosureData(
        symptom: 'Till offline',
        symptomSolution: 'Restarted service',
        cause: 'Stopped service',
        permanentResolution: 'Added monitoring',
    ));
    $locations = ServiceDeskify::locations()->all(new LocationQueryData(
        store: '722',
        customer: 'Customer',
    ));
    $assets = ServiceDeskify::softwareAssets()->all();
    $asset = ServiceDeskify::softwareAssets()->find('40');
    $createdRelease = ServiceDeskify::softwareAssets()->createRelease(
        '40',
        new CreateSoftwareAssetReleaseData(
            version: '4.2.0',
            date: CarbonImmutable::parse('2026-07-28'),
        ),
    );
    $releases = ServiceDeskify::releases()->all(new ReleaseQueryData(
        CarbonImmutable::parse('2026-07-01'),
    ));
    ServiceDeskify::releases()->updateUrl('50', 'https://example.test/releases/4.2.0');
    $download = ServiceDeskify::incidents()->posts('10')->download('20', '70');

    expect($incidents->items[0]->id)->toBe(10)
        ->and($incidents->currentPage)->toBe(1)
        ->and($posts->items[0]->id)->toBe(20)
        ->and($incidents->items[0]->priority)->toBe(IncidentPriority::P1)
        ->and($createdPost->id)->toBe(21)
        ->and($createdPost->type)->toBe(PostType::Public)
        ->and($locations->items[0]->storeNumber)->toBe('722')
        ->and($assets[0])->toBeInstanceOf(SoftwareAssetSummaryData::class)
        ->and($assets[0]->name)->toBe('ServiceDeskify')
        ->and($assets[0]->currentReleaseId)->toBe(50)
        ->and($asset->currentRelease?->version)->toBe('4.2.0')
        ->and($createdRelease->softwareAssetVersionId)->toBe(50)
        ->and($releases[0]->version)->toBe('4.2.0')
        ->and($download->fileName)->toBe('evidence.pdf')
        ->and($download->contentType)->toBe('application/pdf')
        ->and($download->stream->getContents())->toBe('pdf-binary')
        ->and(count($incidents))->toBe(1)
        ->and(iterator_to_array($incidents))->toBe($incidents->items);

    $temporaryPath = tempnam(sys_get_temp_dir(), 'servicedeskify-download-');

    expect($temporaryPath)->toBeString();

    try {
        $download->saveTo($temporaryPath);
        expect(file_get_contents($temporaryPath))->toBe('pdf-binary');
    } finally {
        unlink($temporaryPath);
    }

    assertApiRequestSent(
        $mock,
        ListIncidentsRequest::class,
        Method::GET,
        '/api/incidents',
        static fn (Request $request, Response $response): bool => $response
            ->getPendingRequest()
            ->query()
            ->all() === ['page' => 2, 'status' => 'open'],
    );
    assertApiRequestSent(
        $mock,
        ListIncidentPostsRequest::class,
        Method::GET,
        '/api/incidents/10/posts',
        static fn (Request $request, Response $response): bool => $response
            ->getPendingRequest()
            ->query()
            ->all() === [
                'page' => 1,
                'per_page' => 25,
                'sort_direction' => 'desc',
                'since' => '2026-07-01T12:00:00+00:00',
            ],
    );
    assertApiRequestSent(
        $mock,
        CreateIncidentPostRequest::class,
        Method::POST,
        '/api/incidents/10/posts',
        static fn (Request $request, Response $response): bool => $response
            ->getPendingRequest()
            ->body()
            ?->all() === [
                'details' => 'This is an internal update.',
                'type' => 'private',
            ],
    );
    assertApiRequestSent($mock, CloseIncidentRequest::class, Method::POST, '/api/incidents/10/close');
    assertApiRequestSent(
        $mock,
        SetIncidentClosureDetailsRequest::class,
        Method::POST,
        '/api/incidents/10/closure-details',
        static fn (Request $request, Response $response): bool => $response
            ->getPendingRequest()
            ->body()
            ?->all() === [
                'symptom' => 'Till offline',
                'symptomSolution' => 'Restarted service',
                'cause' => 'Stopped service',
                'permanentResolution' => 'Added monitoring',
            ],
    );
    assertApiRequestSent(
        $mock,
        ListLocationsRequest::class,
        Method::GET,
        '/api/locations',
        static fn (Request $request, Response $response): bool => $response
            ->getPendingRequest()
            ->query()
            ->all() === [
                'page' => 1,
                'store' => '722',
                'customer' => 'Customer',
            ],
    );
    assertApiRequestSent($mock, ListSoftwareAssetsRequest::class, Method::GET, '/api/software-assets');
    assertApiRequestSent($mock, GetSoftwareAssetRequest::class, Method::GET, '/api/software-assets/40');
    assertApiRequestSent(
        $mock,
        CreateSoftwareAssetReleaseRequest::class,
        Method::POST,
        '/api/software-assets/40/releases',
        static fn (Request $request, Response $response): bool => $response
            ->getPendingRequest()
            ->body()
            ?->all() === [
                'version' => '4.2.0',
                'set_current_version' => true,
                'date' => '2026-07-28',
            ],
    );
    assertApiRequestSent(
        $mock,
        ListReleasesRequest::class,
        Method::GET,
        '/api/releases',
        static fn (Request $request, Response $response): bool => $response
            ->getPendingRequest()
            ->query()
            ->all() === ['date' => '2026-07-01'],
    );
    assertApiRequestSent(
        $mock,
        UpdateReleaseUrlRequest::class,
        Method::PUT,
        '/api/releases/50',
        static fn (Request $request, Response $response): bool => $response
            ->getPendingRequest()
            ->body()
            ?->all() === ['url' => 'https://example.test/releases/4.2.0'],
    );
    assertApiRequestSent(
        $mock,
        DownloadIncidentAttachmentRequest::class,
        Method::GET,
        '/api/incidents/10/posts/20/attachments/70',
    );
});

it('maps helpdesk priority labels', function (string $value, IncidentPriority $priority): void {
    expect(IncidentPriority::fromApiValue($value))->toBe($priority);
})->with([
    'critical' => ['Critical', IncidentPriority::P1],
    'high' => ['High', IncidentPriority::P2],
    'moderate' => ['Moderate', IncidentPriority::P3],
    'low' => ['Low', IncidentPriority::P4],
    'very low' => ['Very Low', IncidentPriority::P5],
]);

it('maps an unset helpdesk priority marker to null', function (): void {
    expect(IncidentPriority::fromApiValue('-'))->toBeNull();
});

it('defaults incident creation to moderate priority', function (): void {
    $data = new CreateIncidentData(
        title: 'Till offline',
        details: 'Till two cannot connect.',
        storeNumber: 722,
    );

    expect($data->priority)->toBe(IncidentPriority::P3)
        ->and($data->payload()['priority'])->toBe(3);
});

it('parses naive api timestamps as utc regardless of the application timezone', function (): void {
    config()->set('app.timezone', 'Europe/London');

    $date = DataMapper::date('2026-07-28 11:00:00');

    expect($date)->not->toBeNull()
        ->and($date?->getTimezone()->getName())->toBe('UTC')
        ->and($date?->toAtomString())->toBe('2026-07-28T11:00:00+00:00');
});

it('fails loudly when response identity fields are missing', function (
    callable $hydrate,
    string $message,
): void {
    try {
        $hydrate([]);
    } catch (ServiceDeskifyRequestException $exception) {
        expect($exception)->toBeInstanceOf(UnexpectedResponseException::class)
            ->and($exception->getMessage())->toContain($message);

        return;
    }

    $this->fail('An unexpected API response did not throw a package request exception.');
})->with([
    'incident' => [IncidentData::fromArray(...), 'incident response'],
    'incident post' => [IncidentPostData::fromArray(...), 'incident post response'],
    'software asset' => [SoftwareAssetData::fromArray(...), 'software asset response'],
    'software asset summary' => [SoftwareAssetSummaryData::fromArray(...), 'software asset summary response'],
]);

it('advances requested incident pages without current-page metadata and stops on an empty page', function (): void {
    $mock = MockClient::global([
        tokenResponse(),
        MockResponse::make(paginatedPage(
            [incidentPayload(91)],
            currentPage: 1,
            lastPage: 4,
            includeCurrentPage: false,
        )),
        MockResponse::make(paginatedPage(
            [],
            currentPage: 2,
            lastPage: 4,
            includeCurrentPage: false,
        )),
    ]);

    $incidents = iterator_to_array(ServiceDeskify::incidents()->lazy(
        new IncidentQueryData(status: IncidentStatus::Open),
    ));

    expect(array_map(
        static fn (IncidentData $incident): int => $incident->id,
        $incidents,
    ))->toBe([91]);

    $mock->assertSentCount(3);

    foreach ([1, 2] as $page) {
        assertApiRequestSent(
            $mock,
            ListIncidentsRequest::class,
            Method::GET,
            '/api/incidents',
            static fn (Request $request, Response $response): bool => $response
                ->getPendingRequest()
                ->query()
                ->all() === ['page' => $page, 'status' => 'open'],
        );
    }
});

it('walks every post and location page without dropping query fields', function (): void {
    $mock = MockClient::global([
        tokenResponse(),
        MockResponse::make(paginatedPage([postPayload(101)], currentPage: 1, lastPage: 2)),
        MockResponse::make(paginatedPage([postPayload(102)], currentPage: 2, lastPage: 2)),
        MockResponse::make(rawPaginatedPage([locationPayload(201)], currentPage: 1, lastPage: 2)),
        MockResponse::make(rawPaginatedPage([locationPayload(202)], currentPage: 2, lastPage: 2)),
    ]);

    $since = CarbonImmutable::parse('2026-07-01 12:00:00');
    $posts = iterator_to_array(ServiceDeskify::incidents()->posts('10')->lazy(
        new IncidentPostQueryData(
            perPage: 25,
            sortDirection: SortDirection::Descending,
            since: $since,
        ),
    ));
    $locations = iterator_to_array(ServiceDeskify::locations()->lazy(
        new LocationQueryData(
            store: '722',
            name: 'Town',
            customer: 'Customer',
        ),
    ));

    expect(array_map(
        static fn (IncidentPostData $post): int => $post->id,
        $posts,
    ))->toBe([101, 102])
        ->and(array_map(
            static fn (LocationData $location): int => $location->id,
            $locations,
        ))->toBe([201, 202]);

    foreach ([1, 2] as $page) {
        assertApiRequestSent(
            $mock,
            ListIncidentPostsRequest::class,
            Method::GET,
            '/api/incidents/10/posts',
            static fn (Request $request, Response $response): bool => $response
                ->getPendingRequest()
                ->query()
                ->all() === [
                    'page' => $page,
                    'per_page' => 25,
                    'sort_direction' => 'desc',
                    'since' => '2026-07-01T12:00:00+00:00',
                ],
        );
        assertApiRequestSent(
            $mock,
            ListLocationsRequest::class,
            Method::GET,
            '/api/locations',
            static fn (Request $request, Response $response): bool => $response
                ->getPendingRequest()
                ->query()
                ->all() === [
                    'page' => $page,
                    'store' => '722',
                    'name' => 'Town',
                    'customer' => 'Customer',
                ],
        );
    }
});

it('stops each pagination when the callback returns false', function (): void {
    $mock = MockClient::global([
        tokenResponse(),
        MockResponse::make(paginatedPage(
            [incidentPayload(301), incidentPayload(302)],
            currentPage: 1,
            lastPage: 2,
        )),
        MockResponse::make(paginatedPage([incidentPayload(303)], currentPage: 2, lastPage: 2)),
    ]);
    $seen = [];

    ServiceDeskify::incidents()->each(function (IncidentData $incident) use (&$seen): bool {
        $seen[] = $incident->id;

        return false;
    });

    expect($seen)->toBe([301]);
    $mock->assertSentCount(2);
});

it('accepts numeric string ids from laravel route and input values', function (): void {
    $mock = MockClient::global([
        tokenResponse(),
        MockResponse::make(['data' => incidentPayload(5)]),
    ]);

    expect(ServiceDeskify::incidents()->find('5')->id)->toBe(5);

    assertApiRequestSent(
        $mock,
        GetIncidentRequest::class,
        Method::GET,
        '/api/incidents/5',
    );
});

it('sends incident creation as json when it has no attachments', function (): void {
    $mock = MockClient::global([
        tokenResponse(),
        MockResponse::make(['data' => incidentPayload(80)], 201),
    ]);

    $incident = ServiceDeskify::incidents()->create(new CreateIncidentData(
        title: 'Till offline',
        details: 'Till two cannot connect.',
        storeNumber: 722,
        priority: IncidentPriority::P1,
    ));

    expect($incident->id)->toBe(80);

    $mock->assertSent(function ($request, Response $response): bool {
        if (! $request instanceof CreateIncidentRequest) {
            return false;
        }

        $body = $response->getPendingRequest()->body();
        $headers = $response->getPendingRequest()->headers();

        return $body instanceof JsonBodyRepository
            && $headers->get('Content-Type') === 'application/json'
            && $body->all() === [
                'title' => 'Till offline',
                'details' => 'Till two cannot connect.',
                'store_number' => 722,
                'priority' => 1,
            ];
    });
    assertApiRequestSent($mock, CreateIncidentRequest::class, Method::POST, '/api/incidents');
});

it('switches incident creation to multipart when attachments are present', function (): void {
    $mock = MockClient::global([
        tokenResponse(),
        MockResponse::make(['data' => incidentPayload(81)], 201),
    ]);

    ServiceDeskify::incidents()->create(new CreateIncidentData(
        title: 'Till offline',
        details: 'See the attached log.',
        locationId: 123,
        attachments: [
            UploadedFile::fake()->createWithContent('till.log', 'error details'),
        ],
    ));

    $mock->assertSent(function ($request, Response $response): bool {
        if (! $request instanceof CreateIncidentRequest) {
            return false;
        }

        $body = $response->getPendingRequest()->body();
        $contentType = $response->getPendingRequest()->headers()->get('Content-Type');
        $values = $body?->all();

        if (
            ! $body instanceof MultipartBodyRepository
            || ! is_array($values)
            || ! is_string($contentType)
            || ! str_starts_with($contentType, 'multipart/form-data; boundary=')
        ) {
            return false;
        }

        $parts = array_values(array_filter(
            $values,
            static fn (mixed $value): bool => $value instanceof MultipartValue,
        ));

        return collect($parts)->contains(
            static fn (MultipartValue $part): bool => $part->name === 'attachments[]'
                && $part->filename === 'till.log',
        );
    });
    assertApiRequestSent($mock, CreateIncidentRequest::class, Method::POST, '/api/incidents');
});

it('rewinds multipart attachments before a request is retried', function (): void {
    $request = new CreateIncidentRequest(new CreateIncidentData(
        title: 'Till offline',
        details: 'See the attached log.',
        attachments: [
            UploadedFile::fake()->createWithContent('till.log', 'error details'),
        ],
    ));

    $attachment = collect($request->body()->all())->first(
        static fn (MultipartValue $part): bool => $part->name === 'attachments[]',
    );

    expect($attachment)->toBeInstanceOf(MultipartValue::class)
        ->and(is_resource($attachment->value))->toBeTrue();

    fread($attachment->value, 5);

    expect(ftell($attachment->value))->toBe(5);

    $request->prepareForRetry();

    expect(ftell($attachment->value))->toBe(0);
});

it('strips path components from downloaded attachment filenames', function (
    string $contentDisposition,
    string $expectedFileName,
): void {
    MockClient::global([
        tokenResponse(),
        MockResponse::make('attachment', 200, [
            'Content-Disposition' => $contentDisposition,
        ]),
    ]);

    $download = ServiceDeskify::incidents()->posts(10)->download(20, 70);

    expect($download->fileName)->toBe($expectedFileName);
})->with([
    'encoded unix path' => [
        "attachment; filename*=UTF-8''..%2F..%2Fconfig.php",
        'config.php',
    ],
    'windows path' => [
        'attachment; filename="..\\..\\config.php"',
        'config.php',
    ],
    'directory only' => [
        "attachment; filename*=UTF-8''..%2F..%2F",
        'attachment-70',
    ],
]);

it('continues writing until a complete attachment chunk is saved', function (): void {
    $scheme = 'servicedeskify-short-write';
    $path = "{$scheme}://attachment";

    stream_wrapper_register($scheme, ShortWriteStreamWrapper::class);
    ShortWriteStreamWrapper::reset(maximumWriteSize: 2);

    try {
        $download = new AttachmentDownload(
            fileName: 'attachment.txt',
            contentType: 'text/plain',
            stream: Utils::streamFor('complete attachment'),
        );

        $download->saveTo($path);

        expect(ShortWriteStreamWrapper::contents($path))->toBe('complete attachment');
    } finally {
        stream_wrapper_unregister($scheme);
    }
});

it('fails when saving an attachment makes no write progress', function (): void {
    $scheme = 'servicedeskify-zero-write';
    $path = "{$scheme}://attachment";

    stream_wrapper_register($scheme, ShortWriteStreamWrapper::class);
    ShortWriteStreamWrapper::reset(maximumWriteSize: 0);

    try {
        $download = new AttachmentDownload(
            fileName: 'attachment.txt',
            contentType: 'text/plain',
            stream: Utils::streamFor('attachment'),
        );

        expect(fn () => $download->saveTo($path))
            ->toThrow(RuntimeException::class, 'Unable to write attachment');
    } finally {
        stream_wrapper_unregister($scheme);
    }
});

function tokenResponse(): MockResponse
{
    return MockResponse::make([
        'access_token' => 'resource-access',
        'refresh_token' => 'resource-refresh',
        'token_type' => 'Bearer',
        'expires_in' => 3600,
    ]);
}

/**
 * @param  class-string<Request>  $requestClass
 * @param  null|Closure(Request, Response): bool  $additionalAssertion
 */
function assertApiRequestSent(
    MockClient $mock,
    string $requestClass,
    Method $method,
    string $endpoint,
    ?Closure $additionalAssertion = null,
): void {
    $mock->assertSent(static function (Request $request, Response $response) use (
        $requestClass,
        $method,
        $endpoint,
        $additionalAssertion,
    ): bool {
        $pending = $response->getPendingRequest();

        return $request instanceof $requestClass
            && $pending->getMethod() === $method
            && $pending->getUrl() === 'https://helpdesk.example.test'.$endpoint
            && $pending->headers()->get('Accept') === 'application/json'
            && $pending->headers()->get('Authorization') === 'Bearer resource-access'
            && ($additionalAssertion === null || $additionalAssertion($request, $response));
    });
}

/**
 * @param  array<int, array<string, mixed>>  $data
 * @return array<string, mixed>
 */
function paginated(array $data): array
{
    return [
        'data' => $data,
        'links' => [
            'first' => 'https://helpdesk.example.test/api/resource?page=1',
            'last' => 'https://helpdesk.example.test/api/resource?page=1',
            'prev' => null,
            'next' => null,
        ],
        'meta' => [
            'current_page' => 1,
            'from' => 1,
            'last_page' => 1,
            'per_page' => 50,
            'to' => count($data),
            'total' => count($data),
        ],
    ];
}

/**
 * @param  array<int, array<string, mixed>>  $data
 * @return array<string, mixed>
 */
function paginatedPage(
    array $data,
    int $currentPage,
    int $lastPage,
    bool $includeCurrentPage = true,
): array {
    $payload = paginated($data);
    $payload['meta']['current_page'] = $currentPage;
    $payload['meta']['last_page'] = $lastPage;
    $payload['meta']['total'] = $lastPage * 50;
    $payload['links']['next'] = $currentPage < $lastPage
        ? 'https://helpdesk.example.test/api/incidents?page='.($currentPage + 1)
        : null;

    if (! $includeCurrentPage) {
        unset($payload['meta']['current_page']);
    }

    return $payload;
}

/**
 * @param  array<int, array<string, mixed>>  $data
 * @return array<string, mixed>
 */
function rawPaginated(array $data): array
{
    return [
        'current_page' => 1,
        'data' => $data,
        'first_page_url' => 'https://helpdesk.example.test/api/locations?page=1',
        'from' => 1,
        'last_page' => 1,
        'last_page_url' => 'https://helpdesk.example.test/api/locations?page=1',
        'next_page_url' => null,
        'per_page' => 15,
        'prev_page_url' => null,
        'to' => count($data),
        'total' => count($data),
    ];
}

/**
 * @param  array<int, array<string, mixed>>  $data
 * @return array<string, mixed>
 */
function rawPaginatedPage(array $data, int $currentPage, int $lastPage): array
{
    $payload = rawPaginated($data);
    $payload['current_page'] = $currentPage;
    $payload['last_page'] = $lastPage;
    $payload['total'] = $lastPage * 15;
    $payload['next_page_url'] = $currentPage < $lastPage
        ? 'https://helpdesk.example.test/api/locations?page='.($currentPage + 1)
        : null;

    return $payload;
}

/**
 * @return array<string, mixed>
 */
function postPayload(int $id): array
{
    return [
        'id' => $id,
        'detail' => 'We are investigating.',
        'created_date' => '2026-07-28 11:00:00',
        'created_by' => [
            'username' => 'Support Agent',
            'email' => 'agent@example.test',
            'avatar' => null,
        ],
        'type' => 'public',
        'attachments' => [],
    ];
}

/**
 * @return array<string, mixed>
 */
function locationPayload(int $id): array
{
    return [
        'id' => $id,
        'name' => 'Store 722',
        'location_number' => '722',
        'telephone' => '01234567890',
        'address' => 'High Street',
        'enabled' => true,
        'latitude' => '49.1',
        'longitude' => '-2.1',
        'createdAt' => '2020-01-01 00:00:00',
        'customer' => [
            'id' => 1,
            'name' => 'Customer',
            'type' => 'customer',
            'enabled' => true,
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function softwareAssetPayload(int $id, bool $withRelease = false): array
{
    return [
        'id' => $id,
        'name' => 'ServiceDeskify',
        'current_release_id' => 50,
        'currentRelease' => $withRelease ? releasePayload(50) : null,
        'closed_at' => null,
    ];
}

/**
 * @return array<string, mixed>
 */
function releasePayload(int $id): array
{
    return [
        'id' => $id,
        'version' => '4.2.0',
        'date' => '2026-07-28',
        'closed_at' => null,
        'planned' => false,
        'url' => null,
        'softwareAsset' => [
            'id' => 40,
            'name' => 'ServiceDeskify',
        ],
        'masterVersion' => [
            'id' => 60,
            'name' => '4.2.0',
            'closed_at' => null,
        ],
    ];
}
