<?php

use App\Http\Controllers\Admin\ConversationController;
use Illuminate\Routing\Route;

Route::prefix('conversations')
    ->middleware('auth:sanctum')
    ->group(function () {

        Route::get('/', [ConversationController::class, 'index']);

        Route::get('/search-users', [ConversationController::class, 'searchUsers']);

        Route::post('/start', [ConversationController::class, 'startConversation']);

        Route::get('/{id}', [ConversationController::class, 'show']);

        Route::post('/{id}/leave', [ConversationController::class, 'leave']);

        Route::delete('/{id}', [ConversationController::class, 'destroy']);
    });