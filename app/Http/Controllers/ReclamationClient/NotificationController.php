<?php

namespace App\Http\Controllers\ReclamationClient;

use App\Http\Controllers\Controller;
use App\Models\ReclamationClient\TRecNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Récupère toutes les notifications de l'utilisateur connecté (en tant que récepteur).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $userId = Auth::id();

            $notifications = TRecNotification::where('id_recepteur', $userId)
                ->with(['sender', 'recepteur']) // Charger les relations
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $notifications,
                'total' => $notifications->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marque une notification spécifique comme lue.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function markAsRead(int $id): JsonResponse
    {
        try {
            $userId = Auth::id();

            $notification = TRecNotification::where('id', $id)
                ->where('id_recepteur', $userId)
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification non trouvée'
                ], 404);
            }

            $notification->update([
                'is_read' => true,
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification marquée comme lue',
                'data' => $notification->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marque toutes les notifications non lues de l'utilisateur connecté comme lues.
     *
     * @return JsonResponse
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $userId = Auth::id();

            $updatedCount = TRecNotification::where('id_recepteur', $userId)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Toutes les notifications ont été marquées comme lues',
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
