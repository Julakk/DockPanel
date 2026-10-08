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
    'version' => '0.17.3',

    /*
    | Bikin database + user MySQL beneran di Database Host. Matikan (false)
    | kalau mau cuma nyatet database tanpa menyentuh host.
    */
    'provision_databases' => env('DB_PROVISION', true),

    // URL phpMyAdmin (opsional); kalau diisi, tab Databases nampilin tautannya.
    'phpmyadmin_url' => env('PHPMYADMIN_URL'),

    // Secret bersama dengan skrip signon phpMyAdmin (/api/pma/redeem). Kosong = SSO mati.
    'phpmyadmin_signon_secret' => env('PMA_SIGNON_SECRET'),
];
