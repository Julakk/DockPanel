<?php

return [
    'name' => env('APP_NAME', 'DockPanel'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'Asia/Jakarta',
    'locale' => env('APP_LOCALE', 'id'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => 'id_ID',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'maintenance' => [
        'driver' => 'file',
    ],

    /*
    |--------------------------------------------------------------------------
    | Versi Panel
    |--------------------------------------------------------------------------
    | Update manual tiap rilis baru (samain sama CHANGELOG.md).
    | Dipakai di footer dan halaman Overview.
    */
    'version' => '0.8.0',
];
