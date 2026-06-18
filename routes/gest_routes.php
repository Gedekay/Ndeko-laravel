<?php

use App\Http\Controllers\Admin\UserController;
use Illuminate\Routing\Route;

Route::prefix('admin/users')
    ->middleware(['auth:sanctum', 'admin'])
    ->group(function () {

        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);

        Route::post('/{id}/block', [UserController::class, 'block']);
        Route::post('/{id}/unblock', [UserController::class, 'unblock']);

        Route::delete('/{id}', [UserController::class, 'destroy']);
    });