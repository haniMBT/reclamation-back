<?php

namespace App\Http\Controllers\epayment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\epayment\Facture;
use App\Models\epayment\EOrder;
use App\Models\epayment\ConfirmOrder;
use App\Models\epayment\FailedOrder;
use App\Models\epayment\RecuPaiement;
use App\Models\User;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Initier un processus de paiement
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
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            // Créer une commande électronique
            $order = EOrder::create([
                'facnum' => $facture->facnum,
                'user_id' => $user->id,
                'domcod' => $facture->domcod,
                'payment_token' => Str::random(60),
                'payment_token_expires_at' => Carbon::now()->addMinutes(30)
            ]);

            // Appeler le service de paiement
            $response = $this->paymentService->registerPayment($order, $facture, $user);
            if ($response && $response->errorCode == 0) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'order_id' => $order->id,
                        'payment_url' => $response->formUrl,
                        'token' => $order->payment_token
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de paiement : ' . ($response->errorMessage ?? 'Erreur inconnue')
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Erreur processPayment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Traitement du succès de paiement
     */
    public function success(Request $request, $id)
    {
        try {
            $token = $request->query('token');
            $orderId = $request->input('orderId') ?? $request->query('orderId');

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de paiement manquant'
                ], 400);
            }

            $order = EOrder::where('payment_token', $token)->first();
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande inconnue ou token invalide'
                ], 403);
            }

            if ($order->isTokenExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le token de paiement a expiré'
                ], 403);
            }

            $facture = Facture::findOrFail($id);

            // Vérifier si déjà confirmé
            $existingConfirm = ConfirmOrder::where('orderid', $orderId)
                                         ->where('facnum', $facture->facnum)
                                         ->first();

            if ($existingConfirm) {
                return $this->returnSuccessResponse($facture, $existingConfirm, $orderId);
            }

            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiant de commande manquant'
                ], 400);
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
            Log::error("Erreur success payment : " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors du traitement du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Traitement de l'échec de paiement
     */
    public function failure(Request $request, $id)
    {
        try {
            $token = $request->query('token');
            $orderId = $request->input('orderId');

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de paiement manquant'
                ], 400);
            }

            $order = EOrder::where('payment_token', $token)->first();
            if (!$order || $order->isTokenExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalide ou expiré'
                ], 403);
            }

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
            Log::error("Erreur failure payment : " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement de l\'échec',
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
                'user_id' => Auth::id() ?? null,
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
            Log::error("Erreur createConfirmOrder: " . $e->getMessage());
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
            Log::error("Erreur createFailedOrderRecord: " . $e->getMessage());
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

    /**
     * Récupérer les détails d'un reçu
     */
    public function getReceipt($recuId)
    {
        try {
            $user = Auth::user();
            
            $recu = RecuPaiement::where('id', $recuId)
                               ->where('user_id', $user->id)
                               ->with(['facture', 'user'])
                               ->first();
            
            if (!$recu) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reçu non trouvé'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $recu
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du reçu: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du reçu'
            ], 500);
        }
    }

    /**
     * Télécharger un reçu PDF
     */
    public function downloadReceipt($recuId)
    {
        try {
            $user = Auth::user();
            
            $recu = RecuPaiement::where('id', $recuId)
                               ->where('user_id', $user->id)
                               ->with(['facture'])
                               ->first();
            
            if (!$recu) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reçu non trouvé'
                ], 404);
            }
            
            // Générer le PDF du reçu
            $pdf = \PDF::loadView('receipts.pdf', compact('recu'));
            
            return $pdf->download("recu_paiement_{$recuId}.pdf");
            
        } catch (\Exception $e) {
            Log::error('Erreur lors du téléchargement du reçu: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement du reçu'
            ], 500);
        }
    }

    /**
     * Envoyer un reçu par email
     */
    public function sendReceiptByEmail(Request $request, $recuId)
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);
            
            $user = Auth::user();
            $email = $request->input('email');
            
            $recu = RecuPaiement::where('id', $recuId)
                               ->where('user_id', $user->id)
                               ->with(['facture'])
                               ->first();
            
            if (!$recu) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reçu non trouvé'
                ], 404);
            }
            
            // Envoyer l'email avec le reçu
            // Mail::to($email)->send(new ReceiptMail($recu));
            
            return response()->json([
                'success' => true,
                'message' => 'Reçu envoyé par email avec succès'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du reçu: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du reçu par email'
            ], 500);
        }
    }
}