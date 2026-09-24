<?php

use App\Http\Controllers\Api\SsoController;
use Illuminate\Support\Facades\Route;

Route::post('/sso/token', [SsoController::class, 'issueToken'])
    ->middleware('sso.apikey')
    ->name('api.sso.token');
