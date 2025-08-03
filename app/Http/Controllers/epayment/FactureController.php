<?php

namespace App\Http\Controllers\epayment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\epayment\Facture;
use App\Models\epayment\Detfacture;
use App\Models\epayment\ConfirmOrder;
use App\Http\Controllers\epayment\FacturePDFController;

class FactureController extends Controller
{
    /**
     * Liste des factures pour l'utilisateur connecté
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $dfccod = $user->dfccod;
            $query = Facture::where('trscod', $dfccod);

            // Filtres optionnels
            if ($request->has('status')) {
                if ($request->status === 'paid') {
                    $query->paid();
                } elseif ($request->status === 'unpaid') {
                    $query->unpaid();
                }
            }

            if ($request->has('date_from')) {
                $query->where('facdat', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('facdat', '<=', $request->date_to);
            }

            $factures = $query->orderBy('facdat', 'desc')
                ->paginate($request->get('per_page', 15));
            return response()->json([
                'success' => true,
                'data' => $factures
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des factures',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Détails d'une facture spécifique
     */
    public function show($id)
    {
        try {
            $user = Auth::user();

            $facture = Facture::where('id', $id)
                ->where('trscod', $user->dfccod)
                ->first();

            if (!$facture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facture non trouvée'
                ], 404);
            }

            // Récupérer les détails de la facture
            $detailFactures = Detfacture::where('facnum', $facture->facnum)->get();

            // Récupérer les informations de paiement si elles existent
            $paymentInfo = ConfirmOrder::where('facnum', $facture->facnum)->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'facture' => $facture,
                    'details' => $detailFactures,
                    'payment_info' => $paymentInfo
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recherche de factures (pour les invités)
     */
    public function search(Request $request)
    {
        $request->validate([
            'facnum' => 'required|string',
            'domcod' => 'required|string'
        ]);

        try {
            $facture = Facture::where('facnum', $request->facnum)
                ->where('domcod', $request->domcod)
                ->first();

            if (!$facture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facture non trouvée'
                ], 404);
            }

            // Récupérer les détails
            $detailFactures = Detfacture::where('facnum', $facture->facnum)->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'facture' => $facture,
                    'details' => $detailFactures
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des factures pour le dashboard
     */
    public function stats()
    {
        try {
            $user = Auth::user();
            $dfccod = $user->dfccod;
            $stats = [
                'total_factures' => Facture::where('trscod', $dfccod)->count(),
                'factures_payees' => Facture::where('trscod', $dfccod)->paid()->count(),
                'factures_impayees' => Facture::where('trscod', $dfccod)->unpaid()->count(),
                'montant_total' => Facture::where('trscod', $dfccod)->sum('facttc'),
                'montant_paye' => Facture::where('trscod', $dfccod)->paid()->sum('facttc'),
                'montant_impaye' => Facture::where('trscod', $dfccod)->unpaid()->sum('facttc')
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le PDF d'une facture
     */
    public function generatePDF($id)
    {
        try {
            // Vérifier que la facture existe
            $facture = Facture::find($id);

            if (!$facture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facture non trouvée'
                ], 404);
            }

            // Utiliser la classe existante de génération PDF
            $pdfController = new FacturePDFController();
            return $pdfController->generate($id);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération PDF: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF'
            ], 500);
        }
    }
}
