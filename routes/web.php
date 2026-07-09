<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// TODO: route dashboard, login, CRUD node/server (setelah auth dipasang)
