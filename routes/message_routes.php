<?php

use App\Http\Controllers\Client\MessageController;
use Illuminate\Routing\Route;

Route::prefix('messages')->group(function () {

    Route::post('/', [MessageController::class, 'send']);

    Route::get('/conversation/{conversation}',
        [MessageController::class, 'index']);

    Route::get('/{message}',
        [MessageController::class, 'show']);

    Route::put('/{message}',
        [MessageController::class, 'update']);

    Route::delete('/{message}',
        [MessageController::class, 'destroy']);

    Route::get('/{message}/download',
        [MessageController::class, 'download']);
});