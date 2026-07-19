<?php

use Illuminate\Support\Facades\Route;

// Este projeto e uma API. A rota web serve apenas como health-check simples.
Route::get('/', function () {
    return response()->json([
        'app' => config('app.name'),
        'status' => 'ok',
        'docs' => '/api/documentation (ver README.md)',
    ]);
});
