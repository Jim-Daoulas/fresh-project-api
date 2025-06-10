<?php

return [
    'paths' => ['*'], // Όλα τα paths
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'https://storied-salamander-061141.netlify.app',
        'http://localhost:5173',  // Vite dev server
        'http://localhost:3000',  
        'http://127.0.0.1:5173',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];