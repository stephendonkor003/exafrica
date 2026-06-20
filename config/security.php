<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP Security Headers
    |--------------------------------------------------------------------------
    */

    'headers' => [
        'content_security_policy' => env('SECURITY_CSP', implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "img-src 'self' data: blob: https:",
            "media-src 'self' blob:",
            "font-src 'self' data: https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com",
            "script-src 'self' 'unsafe-inline' https://translate.google.com https://*.google.com https://*.googleapis.com https://*.gstatic.com",
            "connect-src 'self' https://translate.google.com https://*.google.com https://*.googleapis.com",
            "frame-src 'self' https://translate.google.com https://*.google.com",
        ])),

        'hsts' => [
            'enabled' => (bool) env('SECURITY_HSTS_ENABLED', true),
            'value' => env('SECURITY_HSTS', 'max-age=31536000; includeSubDomains; preload'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Token Lifetimes
    |--------------------------------------------------------------------------
    */

    'api_tokens' => [
        'expiration_minutes' => (int) env('API_TOKEN_EXPIRATION_MINUTES', 1440),
        'backoffice_expiration_minutes' => (int) env('BACKOFFICE_TOKEN_EXPIRATION_MINUTES', 120),
    ],

];
