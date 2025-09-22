<?php

use App\Http\Controllers\ReclamationClient\ReclamationController;
use App\Http\Controllers\ReclamationClient\NatureController;
use App\Http\Controllers\ReclamationClient\ParametrageController;
use App\Http\Controllers\ReclamationClient\TicketController;
use App\Http\Controllers\ReclamationClient\TypeController;
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

    // Routes pour la gestion des natures et sous-natures
    Route::get('/nature', [NatureController::class, 'index'])->name('nature.index');
    Route::post('/nature', [NatureController::class, 'store'])->name('nature.store');
    Route::put('/nature/{id}', [NatureController::class, 'update'])->name('nature.update');
    Route::delete('/nature/{id}', [NatureController::class, 'destroy'])->name('nature.destroy');

    // Routes pour le paramétrage
    Route::get('rec/parametrage', [ParametrageController::class, 'index'])->name('parametrage.index');
Route::post('rec/parametrage', [ParametrageController::class, 'store'])->name('parametrage.store');
Route::put('rec/parametrage/{id}', [ParametrageController::class, 'update'])->name('parametrage.update');
Route::delete('rec/parametrage/{id}', [ParametrageController::class, 'destroy'])->name('parametrage.destroy');

    // Routes pour les tickets
    Route::get('rec/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('rec/tickets/indexAll', [TicketController::class, 'indexAll'])->name('tickets.indexAll');
    Route::post('rec/tickets/check-duplicate', [TicketController::class, 'checkDuplicate'])->name('tickets.checkDuplicate');
    Route::get('rec/tickets/{ticketId}/complete-data', [TicketController::class, 'getCompleteTicketData'])->name('tickets.getCompleteData');
    Route::post('rec/tickets/complete', [TicketController::class, 'completeTicket'])->name('tickets.complete');
     Route::post('rec/tickets/save-complete', [TicketController::class, 'saveComplete'])->name('tickets.saveComplete');

    // Routes pour les types et détails (API centralisée)
    Route::put('rec/ticket/{ticketId}/types', [TypeController::class, 'storeOrUpdateGlobal'])->name('type.storeOrUpdateGlobal');
    Route::put('rec/type/global/{ticketId}', [TypeController::class, 'storeOrUpdateGlobal'])->name('type.storeOrUpdateGlobalAlias');
});
