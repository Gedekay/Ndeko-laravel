<?php

use App\Http\Controllers\Client\ProfileController;
use Illuminate\Routing\Route;

Route::middleware('auth:sanctum')
    ->prefix('profile')
    ->group(function () {

        // Mon profil
        Route::get('/me', [
            ProfileController::class,
            'me'
        ]);

        // Modifier profil
        Route::put('/', [
            ProfileController::class,
            'update'
        ]);

        // Changer mot de passe
        Route::post('/change-password', [
            ProfileController::class,
            'changePassword'
        ]);

        // Supprimer photo de profil
        Route::delete('/photo', [
            ProfileController::class,
            'removePhoto'
        ]);

        // Déconnexion
        Route::post('/logout', [
            ProfileController::class,
            'logout'
        ]);

        // Mise à jour présence / dernière activité
        Route::post('/heartbeat', [
            ProfileController::class,
            'heartbeat'
        ]);
    });