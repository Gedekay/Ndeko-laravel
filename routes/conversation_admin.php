<?php

use App\Http\Controllers\Admin\ConversationController;
use Illuminate\Routing\Route;

Route::prefix('admin/conversations')
    ->middleware([
        'auth:sanctum',
        'admin'
    ])
    ->group(function () {

        Route::get('/',
            [ConversationController::class, 'index']);

        Route::get('/{id}',
            [ConversationController::class, 'show']);

        Route::get('/{id}/messages',
            [ConversationController::class, 'messages']);

        Route::get('/{id}/members',
            [ConversationController::class, 'members']);

        Route::get('/{id}/statistics',
            [ConversationController::class, 'statistics']);

        Route::delete('/{id}',
            [ConversationController::class, 'destroy']);
    });