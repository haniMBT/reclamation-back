<?php

namespace App\Http\Controllers\ReclamationClient;

use App\Http\Controllers\Controller;
use App\Models\Direction;
use App\Models\ReclamationClient\BRecTickets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
     * Enregistrer un nouveau ticket
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
                'direction' => 'required|string|exists:direction,DIRECTION'
            ], [
                'libelle.required' => 'Le libellé est obligatoire',
                'libelle.string' => 'Le libellé doit être une chaîne de caractères',
                'libelle.max' => 'Le libellé ne peut pas dépasser 255 caractères',
                'documentAfornir.string' => 'Le document à fournir doit être une chaîne de caractères',
                'direction.required' => 'La direction est obligatoire',
                'direction.string' => 'La direction doit être une chaîne de caractères',
                'direction.exists' => 'La direction sélectionnée n\'existe pas'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Erreur de validation',
                    'messages' => $validator->errors()
                ], 422);
            }

            // Créer le nouveau ticket
            $ticket = BRecTickets::create([
                'libelle' => $request->libelle,
                'documentAfornir' => $request->documentAfornir,
                'direction' => $request->direction
            ]);

            return response()->json([
                'message' => 'Ticket créé avec succès',
                'ticket' => $ticket
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la création du ticket',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
