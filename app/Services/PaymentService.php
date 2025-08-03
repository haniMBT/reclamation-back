<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    /**
     * Enregistrer un paiement pour un utilisateur authentifié
     */
    public function registerPayment($order, $facture, $user)
    {
        $data = [
            'currency' => config('payment.currency'),
            'amount' => (int)($facture->facttc * 100),  // Convertir en centimes
            'language' => 'fr',
            'orderNumber' => $order->id,
            'userName' => config('payment.username'),
            'password' => config('payment.password'),
            'returnUrl' => config('app.frontend_url') . '/epayment/payment/success/' . $facture->id . '?token=' . $order->payment_token . '&orderId=' . $order->id,
            'failUrl' => config('app.frontend_url') . '/epayment/payment/failure/' . $facture->id . '?token=' . $order->payment_token . '&orderId=' . $order->id,
            'jsonParams' => json_encode([
                "force_terminal_id" => config('payment.terminal_id'),
                "udf1" => "EPAL" . $facture->facnum,
                "udf5" => "ggsf85s42524s5uhgsf"
            ])
        ];

        Log::info('Payment registration data:', $data);

        return $this->makePaymentRequest(config('payment.register_url'), $data);
    }
    
    /**
     * Enregistrer un paiement pour un invité
     */
    public function registerGuestPayment($order, $facture)
    {
        $username = config('payment.username');
        $password = config('payment.password');

        $url = 'https://cib.satim.dz/payment/rest/register.do';
        
        $data = [
            'currency' => '012',
            'amount' => $facture->facttc * 100,
            'language' => 'fr',
            'orderNumber' => $order->id,
            'userName' => $username,
            'password' => $password,
            'returnUrl' => config('app.frontend_url') . '/epayment/guest/payment/success/' . $facture->id,
            'failUrl' => config('app.frontend_url') . '/epayment/guest/payment/failure/' . $facture->id,
            'jsonParams' => json_encode([
                "force_terminal_id" => "E004000135",
                "udf1" => "GUEST" . $facture->facnum,
                "udf5" => "ggsf85s42524s5uhgsf"
            ])
        ];

        return $this->makePaymentRequest($url, $data);
    }

    /**
     * Confirmer un paiement
     */
    public function confirmPayment($orderId)
    {
        $username = config('payment.username');
        $password = config('payment.password');
        $language = 'fr';
        
        $url = "https://cib.satim.dz/payment/rest/confirmOrder.do?language=$language&orderId=$orderId&password=$password&userName=$username";

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($result === false || $httpCode !== 200) {
                Log::error("Erreur confirmation paiement - Code HTTP : $httpCode");
                return null;
            }

            return json_decode($result);
        } catch (\Exception $e) {
            Log::error("Exception lors de la confirmation paiement : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Effectuer une requête de paiement générique
     */
    private function makePaymentRequest($url, $data)
    {
        try {
            Log::info('Making payment request to: ' . $url);
            Log::info('Request data:', $data);
            
            $response = Http::timeout(config('payment.timeout', 30))
                           ->asForm()
                           ->post($url, $data);
            
            Log::info('Payment response status: ' . $response->status());
            Log::info('Payment response body: ' . $response->body());
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                // Transformer la réponse pour correspondre au format attendu
                return (object) [
                    'errorCode' => $responseData['errorCode'] ?? 0,
                    'errorMessage' => $responseData['errorMessage'] ?? '',
                    'formUrl' => $responseData['formUrl'] ?? '',
                    'orderId' => $responseData['orderId'] ?? null
                ];
            } else {
                Log::error('Payment API Error: ' . $response->body());
                return (object) [
                    'errorCode' => 1, 
                    'errorMessage' => 'Erreur de communication avec le service de paiement'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Payment Service Exception: ' . $e->getMessage());
            return (object) [
                'errorCode' => 1, 
                'errorMessage' => 'Erreur de connexion au service de paiement: ' . $e->getMessage()
            ];
        }
    }
}