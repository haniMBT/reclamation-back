<?php

namespace App\Http\Controllers\Proforma;

use App\Http\Controllers\Controller;
use App\Models\Proforma\Historique;
use Illuminate\Http\Request;
use App\Services\ProformaTarifCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ProformaController extends Controller
{
    protected $tarifCalculator;

    public function __construct(ProformaTarifCalculator $tarifCalculator)
    {
        $this->tarifCalculator = $tarifCalculator;
    }

    /**
     * Page d'accueil du module proforma
     */
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Module Proforma API ready',
            'user' => Auth::user()
        ]);
    }

    /**
     * Recherche d'informations sur un BL et conteneur
     */
    public function search(Request $request)
    {
        try {
            $request->validate([
                'bl' => 'required|string',
                'conteneur' => 'required|string',
            ]);

            $connaissement = DB::table("connaissements")
                ->where('cnsbld', $request->bl)
                ->first();
                
            if (empty($connaissement)) {
                return response()->json([
                    'status' => 'error',
                    'errors' => [
                        'bl' => ['Veuillez saisir un "BL" correct.']
                    ]
                ], 422);
            }

            $conteneurs = DB::table('dbconteneurs')
                ->where('cnsnum', $connaissement->cnsnum)
                ->get();
                
            //Vérifier si le conteneur existe dans le bl
            $conteneurs = DB::table('dbconteneurs')
                ->where('dctcod', $request->conteneur)
                ->where('cnsnum', $connaissement->cnsnum)
                ->get();

            if ($conteneurs->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => [
                        'conteneur' => ['Veuillez saisir un "N° conteneur" correct.']
                    ]
                ], 422);
            }

            $cnsnum = $conteneurs->first()->cnsnum ?? null;

            if ($connaissement->cnsnum != $cnsnum) {
                return response()->json([
                    'status' => 'error',
                    'errors' => [
                        'both' => ['Les deux champs ne correspondent à aucune escale.']
                    ]
                ], 422);
            }

            $connaissement = DB::table("connaissements")
                ->where('cnsnum', $cnsnum)
                ->first();

            //Récuperer l'ensemble des conteneurs du BL
            $conteneurs = DB::table('dbconteneurs')
                ->where('cnsnum', $connaissement->cnsnum)
                ->get();

            $conteneursP20Count = $conteneurs->where('tstcod', '20P')->count();
            $conteneursP40Count = $conteneurs->where('tstcod', '40P')->count();

            $mannum = $connaissement->mannum;

            $escale = DB::table("escales")
                ->where('escnum', substr($mannum, 0, 8))
                ->first();

            $searchResult = [
                'escale' => $escale->escnum,
                'navire' => $escale->navnom,
                'date' => $escale->escdac,
                'c20p' => $conteneursP20Count,
                'c40p' => $conteneursP40Count,
                'bl' => $request->bl,
                'conteneur' => $request->conteneur
            ];

            return response()->json([
                'status' => 'success',
                'data' => $searchResult
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la recherche: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcul de la facture proforma
     */
    public function calculate(Request $request)
    {
        try {
            $request->validate([
                'bl' => 'required|string',
                'conteneur' => 'required|string',
                // 'escale' => 'required|string',
                'navire' => 'required|string',
                'date' => 'required|date',
                'c20p' => 'required|integer|min:0',
                'c40p' => 'required|integer|min:0',
                'nbc20PV' => 'required|integer|min:0',
                'nbc40PV' => 'required|integer|min:0',
                'maxNbc20P' => 'required|integer|min:0',
                'maxNbc40P' => 'required|integer|min:0',
                'dateDebut' => 'required|date',
                'dateFin' => 'required|date|after_or_equal:dateDebut',
                'scan' => 'boolean',
                'visite' => 'boolean'
            ]);

            $errors = [];

            if ($request->nbc20PV > $request->maxNbc20P || $request->nbc20PV < 0) {
                $errors['nbc20PV'] = $request->maxNbc20P == 0
                    ? ['Il y a 0 conteneur 20p']
                    : ['Le nombre de conteneurs 20p doit être entre 0 et ' . $request->maxNbc20P];
            }

            if ($request->nbc40PV > $request->maxNbc40P || $request->nbc40PV < 0) {
                $errors['nbc40PV'] = $request->maxNbc40P == 0
                    ? ['Il y a 0 conteneur 40p']
                    : ['Le nombre de conteneurs 40p doit être entre 0 et ' . $request->maxNbc40P];
            }

            if (!empty($errors)) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $errors
                ], 422);
            }

            $scan = $request->boolean('scan');
            $visite = $request->boolean('visite');

            $dateDebut = $request->dateDebut;
            $dateFin = $request->dateFin;

            $nbc20P = (int) $request->nbc20PV;
            $nbc40P = (int) $request->nbc40PV;

            $facture = $this->tarifCalculator->calculerFacture($dateDebut, $dateFin, $nbc20P, $nbc40P, $scan);
            
            // Ajouter les frais d'impression
            $facture['totalHT'] += 100;
            $tva = $facture['totalHT'] * 19 / 100;
            $ttc = $facture['totalHT'] + $tva;
            $facture['tva'] = $tva;
            $facture['ttc'] = $ttc;

            // Sauvegarder dans l'historique
            $historique = Historique::create([
                'cnsbld' => $request->bl,
                'dctcod' => $request->conteneur,
                'scan' => $scan,
                'date_fin' => $dateFin,
                'ttc' => $ttc,
                'user_id' => Auth::id()
            ]);

            $searchResult = [
                'bl' => $request->bl,
                'conteneur' => $request->conteneur,
                'escale' => $request->escale,
                'navire' => $request->navire,
                'date' => $request->date,
                'c20p' => $request->c20p,
                'c40p' => $request->c40p,
                // Champs saisis/utilisés pour le calcul
                'nbc20PV' => $nbc20P,
                'nbc40PV' => $nbc40P,
                'scan' => $scan,
                'visite' => $visite,
                'dateFin' => $dateFin,
                // Résultat de calcul et historique
                'facture' => $facture,
                'historique' => $historique
            ];

            return response()->json([
                'status' => 'success',
                'data' => $searchResult
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du calcul: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer l'historique des calculs de l'utilisateur
     */
    public function history()
    {
        try {
            $historiques = Historique::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->paginate(100);

            return response()->json([
                'status' => 'success',
                'data' => $historiques
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération de l\'historique: ' . $e->getMessage()
            ], 500);
        }
    }
}