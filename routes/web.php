<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\bdt\FicheAlerteController;
use App\Http\Controllers\bdt\FichePathologieController;
use App\Http\Controllers\GestionRisques\PlanController;
use App\Http\Controllers\assistance_technique\DemandeAssistanceController;
use App\Http\Controllers\assistance_technique\DemandeEclaircissementController;
use App\Http\Controllers\assistance_technique\DemandeAutorisationController;
use App\Http\Controllers\bdt\PublicationFichesController;
use App\Http\Controllers\Essais\EssaiController;
use App\Http\Controllers\Essais\ReceptionEnregistrementController;
use App\Http\Controllers\archive\ArchiveController;
use App\Http\Controllers\epayment\FactureController;
use App\Http\Controllers\Essais\InterventionController;
use App\Http\Controllers\RelationClient\RelationClientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

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

Route::get('epayment/factures/{id}/pdf', [FactureController::class, 'generatePDF']);
