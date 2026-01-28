<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'v1/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*','http://localhost:4321'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
