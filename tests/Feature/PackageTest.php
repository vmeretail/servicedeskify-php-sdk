<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use VmeRetail\ServiceDeskify\Facades\ServiceDeskify;
use VmeRetail\ServiceDeskify\Resources\IncidentResource;
use VmeRetail\ServiceDeskify\Resources\LocationResource;
use VmeRetail\ServiceDeskify\Resources\ReleaseResource;
use VmeRetail\ServiceDeskify\Resources\SoftwareAssetResource;
use VmeRetail\ServiceDeskify\ServiceDeskifyClient;
use VmeRetail\ServiceDeskify\ServiceDeskifyManager;
use VmeRetail\ServiceDeskify\ServiceDeskifyServiceProvider;

it('registers its manager facade and resource groups', function (): void {
    expect(app(ServiceDeskifyManager::class))
        ->toBeInstanceOf(ServiceDeskifyManager::class)
        ->and(ServiceDeskify::incidents())->toBeInstanceOf(IncidentResource::class)
        ->and(ServiceDeskify::locations())->toBeInstanceOf(LocationResource::class)
        ->and(ServiceDeskify::softwareAssets())->toBeInstanceOf(SoftwareAssetResource::class)
        ->and(ServiceDeskify::releases())->toBeInstanceOf(ReleaseResource::class)
        ->and(config('servicedeskify.url'))->toBe('https://helpdesk.example.test');
});

it('advertises package discovery and the publishable configuration', function (): void {
    /** @var array<string, mixed> $composer */
    $composer = json_decode(
        (string) file_get_contents(dirname(__DIR__, 2).'/composer.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $publishable = ServiceProvider::pathsToPublish(
        ServiceDeskifyServiceProvider::class,
        'servicedeskify-config',
    );
    $source = array_key_first($publishable);

    expect($composer['extra']['laravel']['providers'])
        ->toContain(ServiceDeskifyServiceProvider::class)
        ->and($source)->toBeString()
        ->and(realpath($source))->toBe(realpath(dirname(__DIR__, 2).'/config/servicedeskify.php'))
        ->and($publishable[$source])->toBe(config_path('servicedeskify.php'));
});

it('allows the facade manager to be substituted through the container', function (): void {
    $replacement = new ServiceDeskifyManager(app(ServiceDeskifyClient::class));

    app()->instance(ServiceDeskifyManager::class, $replacement);
    ServiceDeskify::clearResolvedInstance(ServiceDeskifyManager::class);

    expect(ServiceDeskify::getFacadeRoot())->toBe($replacement);
});
