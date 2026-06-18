<?php

use App\Http\Controllers\Admin\StatisticsController;
use Illuminate\Routing\Route;

Route::prefix('admin/statistics')
    ->middleware(['auth:sanctum', 'admin'])
    ->group(function () {
        Route::get('/', [
            StatisticsController::class,
            'index'
        ]);
    });