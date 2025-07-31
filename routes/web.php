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

Route::get('corpus/show/{id_document}', [App\Http\Controllers\Controllers_corpus\DocumentController::class, 'show']);
Route::get('docref/processus/show/{id_processus}', [App\Http\Controllers\Controllers_docref\ProcessusController::class, 'show']);
Route::get('docref/procedure/show/{id_procedure}', [App\Http\Controllers\Controllers_docref\ProcedureController::class, 'show']);
Route::get('docref/instruction/show/{id_procedure}', [App\Http\Controllers\Controllers_docref\InstructionController::class, 'show']);
Route::get('gestion_risque/plans/imprimerPlan/{destinataire}/{code_affaire}/{code_site}/{ic_version}', [App\Http\Controllers\GestionRisques\PlanController::class, 'imprimerPlan']);
Route::get('gestion_risque/plans/imprimerPlanPrevention/{code_affaire}/{code_site}/{ic_version}', [App\Http\Controllers\GestionRisques\ActionController::class, 'imprimerPlanPrevention']);
Route::get('imprimer_fiche_pathologie/{id}/{Matricule?}', [FichePathologieController::class, 'impression'])->name('imprimer_fiche_pathologie');
Route::get('imprimer_fiche_alerte/{id}/{Matricule?}', [FicheAlerteController::class, 'impression'])->name('imprimer_fiche_alerte');
Route::get('imprimer_demande_assisstance/{id}/{Matricule}', [DemandeAssistanceController::class, 'impression'])->name('imprimer_demande_assisstance');
Route::get('imprimer_demande_eclaircissement/{id}/{Matricule}', [DemandeEclaircissementController::class, 'impression'])->name('imprimer_demande_eclaircissement');
Route::get('imprimer_demande_autorisation/{id}/{Matricule}', [DemandeAutorisationController::class, 'impression'])->name('imprimer_demande_autorisation');
Route::get('/imprimer_accuse_reception/{numcommande}/{structure}/{user}', [ReceptionEnregistrementController::class, 'impression'])->name('imprimer_accusé_reception');
Route::get('/fiches_bdt', [PublicationFichesController::class, 'publication_fiches'])->name('publication_fiches');
Route::get('/essais/essaisPV/print/{NumPvEssai}/{Matricule}', [EssaiController::class, 'print'])->name('essais.print');
Route::get('/relation-client/AccuseBonCommande/{code}', [RelationClientController::class, 'AccuseBonCommande'])->name('AccuseBonCommande');
Route::get('/essais/pv_intervention/print/{pe_id}/{Matricule}', [InterventionController::class, 'print']);
Route::get('/essais/pv_interventionAciers/print/{pe_id}/{Matricule}', [InterventionController::class, 'printAciers']);


//récuperation des document rcet depuis F:\
Route::get('rcet/{filename}', function ($filename) {
    // Récupérer le chemin absolu défini dans l'environnementttttttt
    $rcetPath = env('rcet_PATH');

    // Vérifier si la variable est bien définie
    if (!$rcetPath) {
        return abort(500, "Le chemin des archives n'est pas configuré.");
    }
    // Construire le chemin complet en gérant les séparateurs
    $path = rtrim($rcetPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    // Vérifier si le fichier existe avant de l'envoyer
    if (file_exists($path)) {
        return response()->file($path);
    }

    return abort(404, 'Fichier non trouvé');
});
//récuperation des document archive depuis F:\
Route::get('archives/{filename}', function ($filename) {
    // Récupérer le chemin absolu défini dans l'environnementttttttt
    $archivesPath = env('ARCHIVES_PATH');

    // Vérifier si la variable est bien définie
    if (!$archivesPath) {
        return abort(500, "Le chemin des archives n'est pas configuré.");
    }
    // Construire le chemin complet en gérant les séparateurs
    $path = rtrim($archivesPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    // Vérifier si le fichier existe avant de l'envoyer
    if (file_exists($path)) {
        return response()->file($path);
    }

    return abort(404, 'Fichier non trouvé');
});

//récuperation des document livrable depuis C:\
Route::get('livrables/{filename}', function ($filename) {
    $archivesPath = env('LIVRABLE_PATH');
    // Vérifier si la variable est bien définie
    if (!$archivesPath) {
        return abort(500, "Le chemin des archives n'est pas configuré.");
    }
    // Construire le chemin complet en gérant les séparateurs
    $path = rtrim($archivesPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    // Vérifier si le fichier existe avant de l'envoyer
    if (file_exists($path)) {
        return response()->file($path);
    }
    return abort(404, 'Fichier non trouvé');
});

//récuperation des document BC depuis F:\
Route::get('bordereaux/{filename}', function ($filename) {
    // Récupérer le chemin absolu défini dans l'environnementttttttt
    $BCPath = env('BC_PATH');

    // Vérifier si la variable est bien définie
    if (!$BCPath) {
        return abort(500, "Le chemin des BC n'est pas configuré.");
    }
    // Construire le chemin complet en gérant les séparateurs
    $path = rtrim($BCPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    // Vérifier si le fichier existe avant de l'envoyer
    if (file_exists($path)) {
        return response()->file($path);
    }

    return abort(404, 'Fichier non trouvé');
});

//récuperation des document BCRDEX depuis F:\
Route::get('documents/BRDs/{filename}', function ($filename) {
    // Récupérer le chemin absolu défini dans l'environnementttttttt
    $BCRDEXPath = env('BCRDEX_PATH');

    // Vérifier si la variable est bien définie
    if (!$BCRDEXPath) {
        return abort(500, "Le chemin des BC n'est pas configuré.");
    }
    // Construire le chemin complet en gérant les séparateurs
   // $path = rtrim($BCRDEXPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    // Vérifier si le fichier existe avant de l'envoyer
    if (Storage::disk('network_bcRDEX')->exists($filename)) {
        $path=Storage::disk('network_bcRDEX')->path($filename);
        return response()->file($path);
    }

    return abort(404, 'Fichier non trouvé');
});

