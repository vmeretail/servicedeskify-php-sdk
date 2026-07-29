<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\ServiceProvider;
use VmeRetail\ServiceDeskify\Auth\Credentials;
use VmeRetail\ServiceDeskify\Auth\TokenManager;

final class ServiceDeskifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/servicedeskify.php',
            'servicedeskify',
        );

        $this->app->singleton(TokenManager::class, function ($app): TokenManager {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('servicedeskify', []);
            /** @var array<string, mixed> $cache */
            $cache = is_array($config['cache'] ?? null) ? $config['cache'] : [];

            return new TokenManager(
                baseUrl: (string) ($config['url'] ?? ''),
                credentials: new Credentials(
                    username: (string) ($config['username'] ?? ''),
                    password: (string) ($config['password'] ?? ''),
                    clientId: (string) ($config['client_id'] ?? ''),
                    clientSecret: (string) ($config['client_secret'] ?? ''),
                ),
                cache: $app->make(CacheFactory::class),
                encrypter: $app->make(Encrypter::class),
                cacheStore: isset($cache['store']) && is_string($cache['store'])
                    ? $cache['store']
                    : null,
                cacheKey: (string) ($cache['key'] ?? 'servicedeskify.oauth-token'),
                cacheTtl: (int) ($cache['ttl'] ?? 2_592_000),
                expiryLeeway: (int) ($cache['expiry_leeway'] ?? 60),
                lockSeconds: (int) ($cache['lock_seconds'] ?? 10),
                lockWaitSeconds: (int) ($cache['lock_wait_seconds'] ?? 5),
            );
        });

        $this->app->singleton(ServiceDeskifyClient::class, fn ($app): ServiceDeskifyClient => new ServiceDeskifyClient(
            baseUrl: (string) $app['config']->get('servicedeskify.url'),
            tokens: $app->make(TokenManager::class),
        ));

        $this->app->singleton(
            ServiceDeskifyManager::class,
            fn ($app): ServiceDeskifyManager => new ServiceDeskifyManager(
                $app->make(ServiceDeskifyClient::class),
            ),
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/servicedeskify.php' => config_path('servicedeskify.php'),
        ], 'servicedeskify-config');
    }
}
