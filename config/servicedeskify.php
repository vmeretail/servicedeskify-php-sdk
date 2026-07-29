<?php

declare(strict_types=1);

return [
    'url' => env('SERVICEDESKIFY_URL', 'https://helpdesk.vme.co'),

    'username' => env('SERVICEDESKIFY_USERNAME'),
    'password' => env('SERVICEDESKIFY_PASSWORD'),
    'client_id' => env('SERVICEDESKIFY_CLIENT_ID'),
    'client_secret' => env('SERVICEDESKIFY_CLIENT_SECRET'),

    'cache' => [
        'store' => env('SERVICEDESKIFY_CACHE_STORE'),
        'key' => env('SERVICEDESKIFY_CACHE_KEY', 'servicedeskify.oauth-token'),
        'ttl' => (int) env('SERVICEDESKIFY_CACHE_TTL', 2_592_000),
        'expiry_leeway' => (int) env('SERVICEDESKIFY_TOKEN_EXPIRY_LEEWAY', 60),
        'lock_seconds' => (int) env('SERVICEDESKIFY_TOKEN_LOCK_SECONDS', 10),
        'lock_wait_seconds' => (int) env('SERVICEDESKIFY_TOKEN_LOCK_WAIT_SECONDS', 5),
    ],
];
