<?php

return [
    'app_id' => env('STATUS_PROBE_APP_ID'),
    'service_name' => env('STATUS_PROBE_SERVICE_NAME', env('APP_NAME', 'Laravel Application')),
    'service_slug' => env('STATUS_PROBE_SERVICE_SLUG'),
    'health_path' => env('STATUS_PROBE_HEALTH_PATH', 'status/health'),
    'metadata_path' => env('STATUS_PROBE_METADATA_PATH', 'status/metadata'),
    'middleware' => ['api'],
    'auth' => [
        'mode' => 'bearer',
        'token' => env('STATUS_PROBE_TOKEN'),
    ],
    'monitor' => [
        'url' => env('STATUS_MONITOR_URL'),
        'token' => env('STATUS_MONITOR_TOKEN'),
    ],
    'contributors' => [
        'app' => true,
        'db' => true,
        'cache' => true,
        'queue' => false,
        'scheduler' => false,
    ],
];
