<?php

namespace App\Http\Controllers\ReclamationClient;

use App\Http\Controllers\Controller;
use App\Models\ReclamationClient\BRecTickets;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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

    /**
     * Vérifie s'il existe déjà une réclamation avec les mêmes informations clés.
     * Si aucun doublon, insère les données dans t_rec_tickets et t_rec_info_general.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkDuplicate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'bticket_id' => 'required|integer',
                'user_id' => 'required|integer',
                'direction' => 'required|string',
                'status' => 'required|string',
                'description' => 'required|string',
                'info_general_data' => 'required|array',
                'info_general_data.*.info_general_id' => 'required|integer',
                'info_general_data.*.value' => 'required|string',
                'info_general_data.*.key_attribut' => 'required|boolean'
            ]);

            $bticketId = $request->input('bticket_id');
            $userId = $request->input('user_id');
            $direction = $request->input('direction');
            $status = $request->input('status');
            $description = $request->input('description');
            $infoGeneralData = $request->input('info_general_data');

            // Filtrer seulement les champs avec key_attribut = true
            $keyAttributes = collect($infoGeneralData)->filter(function ($item) {
                return $item['key_attribut'] === true;
            });

            // Si aucun attribut clé à vérifier, insérer directement
            if ($keyAttributes->isEmpty()) {
                return $this->insertTicketData($bticketId, $userId, $direction, $status, $description, $infoGeneralData);
            }

            // Construire la requête pour vérifier les doublons
            $query = DB::table('t_rec_info_general')
                ->where('tticket_id', $bticketId)
                ->where('key_attribut', true);

            // Ajouter les conditions pour chaque attribut clé
            $keyAttributes->each(function ($item) use (&$query) {
                $query->orWhere(function ($subQuery) use ($item) {
                    $subQuery->where('info_general_id', $item['info_general_id'])
                             ->where('value', $item['value']);
                });
            });

            // Compter le nombre d'attributs clés qui correspondent
            $matchingCount = $query->count();

            // Si tous les attributs clés correspondent, c'est un doublon
            $isDuplicate = $matchingCount === $keyAttributes->count();

            if ($isDuplicate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Il existe déjà une réclamation avec les mêmes informations clés.',
                    'duplicate_found' => true
                ], 200);
            }

            // Aucun doublon trouvé, insérer les données
            return $this->insertTicketData($bticketId, $userId, $direction, $status, $description, $infoGeneralData);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification des doublons',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Insère les données dans t_rec_tickets et t_rec_info_general
     *
     * @param int $bticketId
     * @param int $userId
     * @param string $direction
     * @param string $status
     * @param string $description
     * @param array $infoGeneralData
     * @return JsonResponse
     */
    private function insertTicketData($bticketId, $userId, $direction, $status, $description, $infoGeneralData): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Insérer dans t_rec_tickets
            $ticketId = DB::table('t_rec_tickets')->insertGetId([
                'bticket_id' => $bticketId,
                'user_id' => $userId,
                'direction' => $direction,
                'status' => $status,
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now(),
                'closed_at' => null
            ]);

            // Insérer dans t_rec_info_general
            foreach ($infoGeneralData as $infoData) {
                DB::table('t_rec_info_general')->insert([
                    'tticket_id' => $ticketId,
                    'info_general_id' => $infoData['info_general_id'],
                    'libelle' => $infoData['value'],
                    'value' => $infoData['value'],
                    'key_attribut' => $infoData['key_attribut'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Réclamation créée avec succès',
                'duplicate_found' => false,
                'ticket_id' => $ticketId
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
