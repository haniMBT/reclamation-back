<?php

use App\Http\Controllers\ReclamationClient\ReclamationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Réclamation Routes
|--------------------------------------------------------------------------
|
| Routes pour la gestion des réclamations clients
|
*/

// Routes protégées pour les réclamations
Route::middleware(['auth:sanctum', 'check.token.expiration'])->group(function () {
    Route::post('/reclamation', [ReclamationController::class, 'store'])->name('reclamation.store');
});
