<?php

// Turing captcha configuration. Publish with:
//   php artisan vendor:publish --tag=turing-config
return [
    // HMAC secret for signing tokens. REQUIRED - the manager throws if empty.
    'secret' => env('TURING_SECRET'),

    // Pepper for hashing answers. Optional: derived from the secret when unset.
    'pepper' => env('TURING_PEPPER'),

    // Default challenge type when none is requested.
    'default' => 'math',

    // Per-type config passed through to each Core challenge type.
    'types' => [
        'math' => ['expire' => 120],
        'text' => ['expire' => 120, 'length' => 5],
        'pow'  => ['algorithm' => 'PBKDF2-SHA256', 'cost' => 5000, 'maxcounter' => 10000, 'expire' => 120],
    ],

    // Single-use store: 'cache' (Laravel cache, default) or 'null' (stateless).
    'store' => 'cache',

    // Hidden form field carrying the packed {t, a} response.
    'field' => 'turing_token',

    // Challenge endpoint.
    'route' => [
        'enabled'    => true,
        'uri'        => '/turing/challenge',
        'middleware' => ['throttle:60,1'],
        'type_param' => 'type',
    ],
];
