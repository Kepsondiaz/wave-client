<?php

return [
    /*
     * GraphQL endpoint for the Wave Business API.
     * Country-specific: ci = Côte d'Ivoire, sn = Senegal, etc.
     * Override with WAVE_API_URL if your country differs.
     */
    'api_url'  => env('WAVE_API_URL', 'https://ci.mmapp.wave.com/a/business_graphql'),

    /*
     * The public-facing dashboard origin sent as Origin/Referer headers.
     */
    'base_url' => env('WAVE_BASE_URL', 'https://business.wave.com'),

    'phone' => env('WAVE_PHONE'),
    'pin'   => env('WAVE_PIN'),     // 4-digit PIN

    /*
     * A stable UUID that identifies this "device" to the Wave API.
     * Generate once (e.g. with Str::uuid()) and set in .env.
     */
    'device_id' => env('WAVE_DEVICE_ID'),

    'session' => [
        'store' => env('WAVE_SESSION_STORE', 'file'),
        'key'   => env('WAVE_SESSION_KEY', 'wave-client:session'),
        'ttl'   => (int) env('WAVE_SESSION_TTL', 604800), // 7 days — refresh via wave:keepalive
    ],

    'http' => [
        'timeout'    => (int) env('WAVE_HTTP_TIMEOUT', 30),
        'user_agent' => env(
            'WAVE_USER_AGENT',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36'
        ),
    ],
];
