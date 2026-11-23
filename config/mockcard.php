<?php

/**
 * MockCard configuration (publish with: php artisan vendor:publish --tag=mockcard-config).
 *
 * Author: Arixeva Labs <hello@arixevalabs.com>
 */

return [
    // API key for chaos runs (a CI-scoped key is enough).
    'api_key' => env('MOCKCARD_API_KEY'),
    // The project chaos runs belong to (prj_…).
    'project_id' => env('MOCKCARD_PROJECT_ID'),
    // Production probe secret, used by the mockcard.probe middleware.
    'probe_secret' => env('MOCKCARD_PROBE_SECRET'),
    'base_url' => env('MOCKCARD_BASE_URL', 'https://www.mockcard.io'),
];
