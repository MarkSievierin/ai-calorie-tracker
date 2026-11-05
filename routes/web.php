<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Healthcheck without token: 200 only when the database answers.
Route::get('/up', function () {
    try {
        DB::select('select 1');

        return response()->json(['ok' => true]);
    } catch (Throwable) {
        return response()->json(['ok' => false], 503);
    }
})->withoutMiddleware(VerifyCsrfToken::class);

// The web app shell. It holds no data; everything comes from /api with the token cookie.
Route::view('/', 'app');
