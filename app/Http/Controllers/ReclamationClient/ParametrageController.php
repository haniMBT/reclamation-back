<?php

namespace App\Http\Controllers\ReclamationClient;

use App\Http\Controllers\Controller;
use App\Models\Direction;
use App\Models\ReclamationClient\BRecTickets;
use App\Models\ReclamationClient\BRecInfoGeneral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class ParametrageController extends Controller
{
    /**
     * Récupérer tous les tickets et directions
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Récupérer tous les tickets
            $tickets = BRecTickets::all();

            // Récupérer toutes les directions (basé sur MainController)
            $directions = Direction::groupby('DIRECTION')
                ->select('DIRECTION')
                ->get();

            $data = [
                'tickets' => $tickets,
                'directions' => $directions
            ];

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des données',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enregistrer un nouveau ticket avec ses infos générales
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validation des données
            $validator = Validator::make($request->all(), [
                'libelle' => 'required|string|max:255',
                'documentAfornir' => 'nullable|string',
                'direction' => 'required|string|exists:direction,DIRECTION',
                'infos_generales' => 'nullable|array',
                'infos_generales.*.libelle' => 'required|string',
                'infos_generales.*.key_attribut' => 'required|boolean',
            ], [
                'libelle.required' => 'Le libellé est obligatoire',
                'libelle.string' => 'Le libellé doit être une chaîne de caractères',
                'libelle.max' => 'Le libellé ne peut pas dépasser 255 caractères',
                'documentAfornir.string' => 'Le document à fournir doit être une chaîne de caractères',
                'direction.required' => 'La direction est obligatoire',
                'direction.string' => 'La direction doit être une chaîne de caractères',
                'direction.exists' => 'La direction sélectionnée n\'existe pas',
                'infos_generales.array' => 'Les informations générales doivent être un tableau',
                'infos_generales.*.libelle.required' => 'Le libellé de l\'information générale est obligatoire',
                'infos_generales.*.libelle.string' => 'Le libellé de l\'information générale doit être une chaîne de caractères',
                'infos_generales.*.key_attribut.required' => 'L\'attribut clé est obligatoire',
                'infos_generales.*.key_attribut.boolean' => 'L\'attribut clé doit être un booléen',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Erreur de validation',
                    'messages' => $validator->errors()
                ], 422);
            }

            // Utiliser une transaction pour garantir l'intégrité des données
            return DB::transaction(function () use ($request) {
                // Créer le nouveau ticket
                $ticket = BRecTickets::create([
                    'libelle' => $request->libelle,
                    'documentAfornir' => $request->documentAfornir,
                    'direction' => $request->direction
                ]);

                // Enregistrer les infos générales si elles sont fournies
                if ($request->has('infos_generales') && is_array($request->infos_generales)) {
                    foreach ($request->infos_generales as $info) {
                        // Notez que nous utilisons key_attirubut (avec la faute de frappe) car c'est le nom du champ dans le modèle
                        BRecInfoGeneral::create([
                            'bticket_id' => $ticket->id,
                            'libelle' => $info['libelle'],
                            'key_attirubut' => $info['key_attribut']
                        ]);
                    }
                }

                // Charger la relation infosGenerales pour la retourner dans la réponse
                $ticket->load('infosGenerales');

                return response()->json([
                    'message' => 'Ticket créé avec succès',
                    'ticket' => $ticket
                ], 201);
            });

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la création du ticket',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
