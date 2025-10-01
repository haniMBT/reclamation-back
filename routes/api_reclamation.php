<?php

use App\Http\Controllers\ReclamationClient\ReclamationController;
use App\Http\Controllers\ReclamationClient\NatureController;
use App\Http\Controllers\ReclamationClient\ParametrageController;
use App\Http\Controllers\ReclamationClient\TicketController;
use App\Http\Controllers\ReclamationClient\TypeController;
use App\Http\Controllers\ReclamationClient\MessageController;
use App\Http\Controllers\DirectionController;
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
    Route::get('rec/tickets/files/{fileId}/download', [TicketController::class, 'downloadFile'])->name('tickets.files.download');
    Route::get('rec/tickets/indexAll', [TicketController::class, 'indexAll'])->name('tickets.indexAll');
    Route::post('rec/tickets/check-duplicate', [TicketController::class, 'checkDuplicate'])->name('tickets.checkDuplicate');
    Route::get('rec/tickets/{ticketId}/complete-data', [TicketController::class, 'getCompleteTicketData'])->name('tickets.getCompleteData');
    Route::post('rec/tickets/complete', [TicketController::class, 'completeTicket'])->name('tickets.complete');
    Route::post('rec/tickets/save-complete', [TicketController::class, 'saveComplete'])->name('tickets.saveComplete');
    Route::post('rec/tickets/validate', [TicketController::class, 'validateTicket'])->name('tickets.validate');
    
    // Routes pour l'édition des tickets créés
    Route::get('rec/tickets/{id}/edit', [TicketController::class, 'getTicketForEdit'])->name('tickets.getForEdit');
    Route::put('rec/tickets/{id}', [TicketController::class, 'updateTicket'])->name('tickets.update');
    Route::post('rec/tickets/{id}', [TicketController::class, 'updateTicket'])->name('tickets.update.post');
    Route::delete('rec/tickets/{id}', [TicketController::class, 'destroy'])->name('tickets.destroy');

    // Routes pour les types et détails (API centralisée)
    Route::put('rec/ticket/{ticketId}/types', [TypeController::class, 'storeOrUpdateGlobal'])->name('type.storeOrUpdateGlobal');
    Route::put('rec/type/global/{ticketId}', [TypeController::class, 'storeOrUpdateGlobalAlias'])->name('type.storeOrUpdateGlobalAlias');

    // Routes pour les directions
    Route::get('rec/directions', [DirectionController::class, 'index'])->name('directions.index');
    Route::get('rec/directions/{id}', [DirectionController::class, 'show'])->name('directions.show');
    Route::post('rec/directions', [DirectionController::class, 'store'])->name('directions.store');
    Route::put('rec/directions/{id}', [DirectionController::class, 'update'])->name('directions.update');
    Route::delete('rec/directions/{id}', [DirectionController::class, 'destroy'])->name('directions.destroy');

    // Routes pour les messages
    Route::get('rec/tickets/{ticketId}/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('rec/tickets/{ticketId}/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::get('rec/messages/{id}', [MessageController::class, 'show'])->name('messages.show');
    Route::put('rec/messages/{id}/mark-as-read', [MessageController::class, 'markAsRead'])->name('messages.markAsRead');
    Route::delete('rec/tickets/{ticketId}/messages/{id}', [MessageController::class, 'destroy'])->name('messages.destroy');
    Route::get('rec/messages/attachments/{id}/download', [MessageController::class, 'downloadAttachment'])->name('messages.downloadAttachment');
});
