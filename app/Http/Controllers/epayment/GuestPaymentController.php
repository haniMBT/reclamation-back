<?php

namespace App\Http\Controllers\epayment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use App\Models\epayment\Facture;
use App\Models\epayment\Detfacture;
use App\Models\epayment\EOrder;
use App\Models\epayment\ConfirmOrder;
use App\Models\epayment\FailedOrder;
use App\Services\PaymentService;

class GuestPaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Recherche de facture pour invité
     */
    public function searchFacture(Request $request)
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
     * Initier un paiement en tant qu'invité
     */
    public function processPayment(Request $request, $id)
    {
        $request->validate([
            'terms' => 'required|accepted'
        ]);

        try {
            $facture = Facture::find($id);
            if (!$facture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facture introuvable'
                ], 404);
            }

            // Créer une commande électronique pour invité
            $order = EOrder::create([
                'facnum' => $facture->facnum,
                'user_id' => null, // Pas d'utilisateur pour les invités
                'domcod' => $facture->domcod
            ]);

            // Appeler le service de paiement pour invité
            $response = $this->paymentService->registerGuestPayment($order, $facture);

            if ($response && isset($response['errorCode']) && $response['errorCode'] == 0) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'order_id' => $order->id,
                        'payment_url' => $response['formUrl']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de paiement : ' . ($response['errorMessage'] ?? 'Erreur inconnue')
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Erreur guest processPayment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Succès de paiement pour invité
     */
    public function success(Request $request, $id)
    {
        try {
            $orderId = $request->input('orderId') ?? $request->query('orderId');
            $facture = Facture::findOrFail($id);

            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiant de commande manquant'
                ], 400);
            }

            // Vérifier si déjà confirmé
            $existingConfirm = ConfirmOrder::where('orderid', $orderId)
                                         ->where('facnum', $facture->facnum)
                                         ->first();

            if ($existingConfirm) {
                return $this->returnSuccessResponse($facture, $existingConfirm, $orderId);
            }

            // Confirmer le paiement via l'API
            $obj = $this->paymentService->confirmPayment($orderId);

            if (!$obj || !isset($obj->ErrorCode)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Réponse invalide de l\'API de paiement'
                ], 400);
            }

            // Vérifier si le paiement a réussi
            if ($obj->params->respCode == "00" && $obj->OrderStatus == "2") {
                if (isset($obj->ErrorCode) && $obj->ErrorCode == 0) {
                    $confirmOrder = $this->createConfirmOrder($obj, $facture, $orderId);
                    
                    $facture->status = 1;
                    $facture->save();

                    return $this->returnSuccessResponse($facture, $confirmOrder, $orderId, $obj);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Échec du paiement',
                    'data' => $obj
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error("Erreur guest success payment : " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors du traitement du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Échec de paiement pour invité
     */
    public function failure(Request $request, $id)
    {
        try {
            $orderId = $request->input('orderId');
            $facture = Facture::findOrFail($id);

            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiant de commande manquant'
                ], 400);
            }

            // Récupérer les informations d'échec
            $obj = $this->paymentService->confirmPayment($orderId);

            if ($obj) {
                $this->createFailedOrderRecord($obj, $facture->facnum, $orderId);
                
                $message = $this->getFailureMessage($obj);
                
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'data' => [
                        'facture' => $facture,
                        'error_code' => $obj->ErrorCode ?? null,
                        'order_status' => $obj->OrderStatus ?? null
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Échec du paiement - informations non disponibles'
            ], 400);

        } catch (\Exception $e) {
            Log::error("Erreur guest failure payment : " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement de l\'échec',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir un reçu pour invité
     */
    public function getReceipt($id)
    {
        try {
            $order = ConfirmOrder::where('recuId', $id)->first();
            
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reçu non trouvé'
                ], 404);
            }

            $eorder = EOrder::find($order->OrderNumber);
            $facture = null;

            if ($eorder) {
                $facture = Facture::where('facnum', $eorder->facnum)
                                 ->where('domcod', $eorder->domcod)
                                 ->first();
            }

            $dateValable = Carbon::parse($order->created_at)
                                ->addHour()
                                ->format('d-m-Y H:i:s');

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'facture' => $facture,
                    'date_valable' => $dateValable
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du reçu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un enregistrement de commande confirmée
     */
    private function createConfirmOrder($obj, $facture, $orderId)
    {
        try {
            return ConfirmOrder::create([
                'facnum' => $facture->facnum,
                'facrfe' => $facture->facrfe ?? null,
                'trscod' => $facture->trscod ?? null,
                'domcod' => $facture->domcod ?? null,
                'orderid' => $orderId,
                'user_id' => null, // Pas d'utilisateur pour les invités
                'expiration' => $obj->expiration ?? null,
                'cardholderName' => $obj->cardholderName ?? null,
                'depositAmount' => $obj->depositAmount ?? null,
                'currency' => $obj->currency ?? null,
                'approvalCode' => $obj->approvalCode ?? null,
                'authCode' => $obj->authCode ?? null,
                'actionCode' => $obj->actionCode ?? null,
                'actionCodeDescription' => $obj->actionCodeDescription ?? null,
                'ErrorCode' => $obj->ErrorCode ?? null,
                'ErrorMessage' => $obj->ErrorMessage ?? null,
                'OrderStatus' => $obj->OrderStatus ?? null,
                'OrderNumber' => $obj->OrderNumber ?? null,
                'Pan' => $obj->Pan ?? null,
                'Ip' => $obj->Ip ?? request()->ip(),
                'SvfeResponse' => $obj->SvfeResponse ?? null,
                'Amount' => $obj->Amount ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur guest createConfirmOrder: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Créer un enregistrement de commande échouée
     */
    private function createFailedOrderRecord($obj, $facnum, $orderId)
    {
        try {
            FailedOrder::create([
                'facnum' => $facnum,
                'orderid' => $orderId,
                'expiration' => $obj->expiration ?? null,
                'cardholderName' => $obj->cardholderName ?? null,
                'depositAmount' => $obj->depositAmount ?? null,
                'currency' => $obj->currency ?? null,
                'approvalCode' => $obj->approvalCode ?? null,
                'authCode' => $obj->authCode ?? null,
                'actionCode' => $obj->actionCode ?? null,
                'actionCodeDescription' => $obj->actionCodeDescription ?? null,
                'ErrorCode' => $obj->ErrorCode ?? null,
                'ErrorMessage' => $obj->ErrorMessage ?? null,
                'OrderStatus' => $obj->OrderStatus ?? null,
                'OrderNumber' => $obj->OrderNumber ?? null,
                'Pan' => $obj->Pan ?? null,
                'Ip' => $obj->Ip ?? request()->ip(),
                'SvfeResponse' => $obj->SvfeResponse ?? null,
                'Amount' => $obj->Amount ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur guest createFailedOrderRecord: " . $e->getMessage());
        }
    }

    /**
     * Retourner une réponse de succès formatée
     */
    private function returnSuccessResponse($facture, $order, $orderId, $obj = null)
    {
        $dateValable = Carbon::parse($order->created_at)->addHour()->format('d-m-Y H:i:s');
        
        return response()->json([
            'success' => true,
            'message' => 'Paiement traité avec succès',
            'data' => [
                'facture' => $facture,
                'order' => $order,
                'order_id' => $orderId,
                'date_valable' => $dateValable,
                'payment_details' => $obj
            ]
        ]);
    }

    /**
     * Obtenir le message d'erreur approprié
     */
    private function getFailureMessage($obj)
    {
        if ($obj->ErrorCode != 0 && $obj->ErrorCode != 3) {
            switch ($obj->ErrorCode) {
                case 7: return "ERREUR SYSTEM, Veuillez contacter l'administrateur du site";
                case 6: return "ERREUR N° ORDRE, Veuillez contacter l'administrateur du site";
                case 5:
                case 3: return "ACCES REFUSE, Veuillez contacter l'administrateur du site";
                case 2: return "TRANSACTION DEJA VALIDEE, Veuillez contacter l'administrateur du site";
                case 1: return "N° ORDRE DEJA ATTRIBUE, Veuillez contacter l'administrateur du site";
                default: return "Erreur non gérée (code: {$obj->ErrorCode}).";
            }
        }

        if (isset($obj->params->respCode_desc)) {
            return $obj->params->respCode_desc;
        }

        if (isset($obj->actionCodeDescription)) {
            return $obj->actionCodeDescription;
        }

        return 'Une erreur inconnue est survenue.';
    }
}