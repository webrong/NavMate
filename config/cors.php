<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],

    'allowed_origins' => [env('APP_URL', 'http://localhost:8000')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['X-Requested-With', 'Content-Type', 'Accept', 'X-CSRF-TOKEN'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
