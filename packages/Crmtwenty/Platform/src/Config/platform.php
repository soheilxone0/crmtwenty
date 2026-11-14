<?php

return [
    /*
     * URL path of the platform administration panel.
     */
    'path' => env('PLATFORM_PATH', 'platform'),

    /*
     * Run pending database migrations automatically (per company) after a new version is
     * uploaded. Shared hosting has no shell for `php artisan migrate`.
     */
    'auto_update' => (bool) env('PLATFORM_AUTO_UPDATE', true),
];
