<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Saloon\Http\Faking\MockClient;
use VmeRetail\ServiceDeskify\ServiceDeskifyServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [ServiceDeskifyServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('s', 32)));
        $app['config']->set('cache.default', 'array');
        $app['config']->set('servicedeskify', [
            'url' => 'https://helpdesk.example.test',
            'username' => 'sdk@example.test',
            'password' => 'secret-password',
            'client_id' => '2',
            'client_secret' => 'secret-client',
            'cache' => [
                'store' => 'array',
                'key' => 'servicedeskify.tests.oauth-token',
                'ttl' => 3600,
                'expiry_leeway' => 60,
                'lock_seconds' => 10,
                'lock_wait_seconds' => 1,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        MockClient::destroyGlobal();

        parent::tearDown();
    }
}
