<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\epayment\FactureController;
use App\Http\Controllers\epayment\PayementController;
use App\Http\Controllers\SiteController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

require __DIR__ . '/auth.php';

// Route pour les conditions d'utilisation (PDF)
Route::get('conditions', [SiteController::class, 'generateConditions'])->name('conditions');

// Routes e-paiement
Route::get('epayment/factures/{id}/pdf', [FactureController::class, 'generatePDF']);
Route::get('epayment/conditions/pdf', [SiteController::class, 'generateConditions']);

Route::post('/payment/process/{id}/{email}', [App\Http\Controllers\epayment\PaymentController::class, 'processPayment'])->name('payment.process');;
Route::get('/payment/success/{id}', [App\Http\Controllers\epayment\PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/failure/{id}', [App\Http\Controllers\epayment\PaymentController::class, 'failure'])->name('payment.failure');


Route::middleware('auth')->group(function () {
    // Route::get('/payment/success/{id}/{email}', [App\Http\Controllers\epayment\PaymentController::class, 'success']);
    // Route::get('/payment/failure/{id}', [App\Http\Controllers\epayment\PaymentController::class, 'failure']);
    Route::get('/receipt/{recuId}', [App\Http\Controllers\epayment\PaymentController::class, 'getReceipt']);
    Route::get('/receipt/{recuId}/download', [App\Http\Controllers\epayment\PaymentController::class, 'downloadReceipt'])->name('payment.downloadrecu');
    Route::post('/receipt/{recuId}/send-email', [App\Http\Controllers\epayment\PaymentController::class, 'sendReceiptByEmail'])->name('payment.sendmail');
    Route::get('/erreur-paiement/{id}', [App\Http\Controllers\epayment\PaymentController::class, 'errorPayment'])->name('payment.erreur');
});
