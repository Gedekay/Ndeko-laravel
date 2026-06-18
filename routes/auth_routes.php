<?php

use App\Http\Controllers\AuthController;
use Illuminate\Routing\Route;

Route::prefix('auth')->group(function () {

    Route::post('/register', [
        AuthController::class,
        'register'
    ]);

    Route::post('/login', [
        AuthController::class,
        'login'
    ]);

    Route::middleware('auth:sanctum')
        ->group(function () {

            Route::get('/me', [
                AuthController::class,
                'me'
            ]);

            Route::post('/logout', [
                AuthController::class,
                'logout'
            ]);

            Route::post('/logout-all', [
                AuthController::class,
                'logoutAll'
            ]);

            Route::post('/refresh', [
                AuthController::class,
                'refresh'
            ]);
        });
});