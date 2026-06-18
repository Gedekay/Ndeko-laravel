<?php

use App\Http\Controllers\Client\GroupController;
use Illuminate\Support\Facades\Route;

Route::prefix('groups')->group(function () {

    Route::post('/', [GroupController::class, 'store']);

    Route::get('{group}', [GroupController::class, 'show']);

    Route::put('{group}', [GroupController::class, 'update']);

    Route::delete('{group}', [GroupController::class, 'destroy']);

    Route::get('{group}/members', [GroupController::class, 'members']);

    Route::post('{group}/members', [GroupController::class, 'addMember']);

    Route::delete('{group}/members/{user}', [GroupController::class, 'removeMember']);

    Route::post('{group}/leave', [GroupController::class, 'leave']);
});