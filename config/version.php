<?php

return [
    'github_repo' => env('GITHUB_REPO', ''), // ex: your-org/cofeesaas
    'github_token' => env('GITHUB_TOKEN', ''), // optional
    'cache_minutes' => (int) env('GITHUB_RELEASE_CACHE_MINUTES', 15),
    'verify_ssl' => env('GITHUB_VERIFY_SSL', null), // null = auto (production: true, dev: false)
];