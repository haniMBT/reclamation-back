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
    Route::get('/reclamations', [ReclamationController::class, 'index'])->name('reclamation.index');
    Route::get('/reclamations/all', [ReclamationController::class, 'indexAll'])->name('reclamation.indexAll');
    Route::post('/reclamation', [ReclamationController::class, 'store'])->name('reclamation.store');
    Route::put('/reclamation/{id}', [ReclamationController::class, 'update']); // API update
    Route::get('/reclamation/{id}', [ReclamationController::class, 'show'])->name('reclamation.show');
    Route::get('/reclamation/{reclamationId}/fichier/{fichierId}/download', [ReclamationController::class, 'downloadFile'])->name('reclamation.download');
    Route::get('/reclamation/{reclamationId}/fichier/{fichierId}/delete', [ReclamationController::class, 'deleteFile'])->name('reclamation.delete');
});
