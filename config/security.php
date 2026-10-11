<?php

return [

    /*
    | Mode Content-Security-Policy: "report-only" (default, cuma nyatet
    | pelanggaran ke log), "enforce" (beneran memblokir), atau "off".
    | Ubah lewat CSP_MODE di .env, lalu jalanin: php artisan config:clear
    */
    'csp_mode' => env('CSP_MODE', 'report-only'),

];
