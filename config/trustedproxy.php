<?php

return [
    'proxies' => array_values(array_filter(array_map(
        static fn (string $proxy): string => trim($proxy),
        explode(',', (string) env('TRUSTED_PROXIES', ''))
    ))),

    'cloudflare' => [
        'enabled' => (bool) env('TRUST_CLOUDFLARE_HEADERS', false),
    ],
];
