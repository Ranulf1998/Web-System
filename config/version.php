<?php

return [
    'github_repo' => env('GITHUB_REPO', ''), // ex: your-org/cofeesaas
    'github_token' => env('GITHUB_TOKEN', ''), // optional
    'cache_minutes' => (int) env('GITHUB_RELEASE_CACHE_MINUTES', 15),
    'cache_store' => env('GITHUB_RELEASE_CACHE_STORE', 'file'),
    'verify_ssl' => env('GITHUB_VERIFY_SSL', null), // null = auto (production: true, dev: false)
    'webhook_secret' => env('GITHUB_WEBHOOK_SECRET', ''),
    'updater_enabled' => env('UPDATER_ENABLED', false),
    'tenant_can_trigger_updater' => env('TENANT_CAN_TRIGGER_UPDATER', false),
    'updater_branch' => env('UPDATER_BRANCH', 'main'),
    'updater_timeout_seconds' => (int) env('UPDATER_TIMEOUT_SECONDS', 1800),
    'updater_lock_seconds' => (int) env('UPDATER_LOCK_SECONDS', 3600),
];