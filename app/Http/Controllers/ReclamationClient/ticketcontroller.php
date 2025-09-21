<?php

namespace App\Http\Controllers\ReclamationClient;

use App\Http\Controllers\Controller;
use App\Models\ReclamationClient\BRecTickets;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    /**
     * Récupère tous les tickets avec leurs informations générales.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // Récupérer tous les tickets avec leurs informations générales
            $tickets = BRecTickets::with('infosGenerales')
                ->orderBy('libelle', 'asc')
                ->get();

            // Formater les données pour le frontend
            $formattedTickets = $tickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'libelle' => $ticket->libelle,
                    'documentAfornir' => $ticket->documentAfornir,
                    'direction' => $ticket->direction,
                    'created_at' => $ticket->created_at,
                    'updated_at' => $ticket->updated_at,
                    'infos_generales' => $ticket->infosGenerales->map(function ($info) {
                        return [
                            'id' => $info->id,
                            'libelle' => $info->libelle,
                            'key_attirubut' => $info->key_attirubut,
                        ];
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Tickets récupérés avec succès',
                'data' => $formattedTickets
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des tickets',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}