<?php

use App\Http\Controllers\MainController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\Proforma\ProformaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Routes publiques (sans authentification)
Route::post('/login', [MainController::class, 'login']);

// Mot de passe oublié
Route::post('/forgot-password', [UserProfileController::class, 'forgotPassword']);
Route::post('/reset-password', [UserProfileController::class, 'resetPassword']);

// Routes protégées (avec authentification)
Route::middleware(['auth:sanctum'])->group(function () {
    // Informations utilisateur
    Route::get('/user', [MainController::class, 'user']);
    Route::post('/logout', [MainController::class, 'logout']);
    
    // Gestion du profil
    Route::get('/profile', [UserProfileController::class, 'getProfile']);
    Route::post('/profile/update', [UserProfileController::class, 'updateProfile']);
    Route::post('/profile/upload-photo', [UserProfileController::class, 'uploadPhoto']);
    Route::delete('/profile/delete-photo', [UserProfileController::class, 'deletePhoto']);
    
    // Gestion des mots de passe
    Route::post('/profile/change-password', [UserProfileController::class, 'changePassword']);
    Route::post('/profile/request-password-change-code', [UserProfileController::class, 'requestPasswordChangeCode']);
    Route::post('/profile/change-password-with-code', [UserProfileController::class, 'changePasswordWithCode']);
    
    // Validation en temps réel
    Route::post('/profile/validate-current-password', [UserProfileController::class, 'validateCurrentPassword']);
    Route::post('/profile/validate-email', [UserProfileController::class, 'validateEmail']);
    
    // Route pour récupérer les privilèges (compatibilité avec l'existant)
    Route::post('/allPrivileges', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'privileges' => $request->user()->privileges ?? []
        ]);
    });

      // Routes du module e-paiement pour utilisateurs authentifiés
    Route::prefix('epayment')->group(function () {
        // Gestion des factures
        Route::get('/factures', [App\Http\Controllers\epayment\FactureController::class, 'index']);
        Route::get('/factures/{id}', [App\Http\Controllers\epayment\FactureController::class, 'show']);
        Route::get('/facturesStats', [App\Http\Controllers\epayment\FactureController::class, 'stats']);
        
        // Gestion des paiements
        // Route::post('/payment/process/{id}', [App\Http\Controllers\epayment\PaymentController::class, 'processPayment']);
        // Route::get('/payment/success/{id}', [App\Http\Controllers\epayment\PaymentController::class, 'success']);
        // Route::get('/payment/failure/{id}', [App\Http\Controllers\epayment\PaymentController::class, 'failure']);
        // Route::get('/receipt/{recuId}', [App\Http\Controllers\epayment\PaymentController::class, 'getReceipt']);
    });

    // Routes du module proforma pour utilisateurs authentifiés
    Route::prefix('proforma')->group(function () {
        Route::get('/', [ProformaController::class, 'index']);
        Route::post('/search', [ProformaController::class, 'search']);
        Route::post('/calculate', [ProformaController::class, 'calculate']);
        Route::get('/history', [ProformaController::class, 'history']);
    });

    require __DIR__ . '/api_reclamation.php';
});