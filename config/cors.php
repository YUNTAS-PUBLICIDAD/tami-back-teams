<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'v1/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['https://tamimaquinarias.com','http://localhost:4321'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => true,
];
