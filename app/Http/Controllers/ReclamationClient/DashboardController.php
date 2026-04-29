<?php

namespace App\Http\Controllers\ReclamationClient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Services\ReclamationClient\DashboardService;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * API unique pour le tableau de bord (Timeline)
     * - Retourne la liste des tickets (items) avec les dates clés
     * - Retourne la liste des tickets de base (base_tickets)
     *
     * Filtres supportés: date_from, date_to, bticket_id, statuses, source
     */
    public function timelineData(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $filters = $request->all();

            $data = $this->dashboardService->getTimelineData($user, $filters);

            return response()->json([
                'success' => true,
                'message' => 'Données du dashboard récupérées avec succès',
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données du dashboard',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
