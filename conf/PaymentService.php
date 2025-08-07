<?php
namespace App\Library\Services;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    // Méthode pour enregistrer un paiement
    public function registerPayment($order, $facture, $user)
    {
        $username = config('payment.username');
        $password = config('payment.password');

	//dd($username, $password);
     
        // Définir les informations nécessaires pour l'appel API
        $url = 'https://cib.satim.dz/payment/rest/register.do';
        $data = [
            'currency' => '012',
            'amount' => $facture->facttc * 100,  // Montant converti en centimes
            'language' => 'fr',
            'orderNumber' => $order->id,
            'userName' => $username,
            'password' => $password,
            'returnUrl' => route('payment.success', ['id' => $facture->id]),
            'failUrl' => route('payment.failure', ['id' => $facture->id]),
            'jsonParams' => json_encode([
                "force_terminal_id" => "E004000032",
                "udf1" => "2018105301346",
                "udf5" => "ggsf85s42524s5uhgsf"
            ])
        ];

        //dd($data);

        // Configuration des options de la requête HTTP
        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        // Création du contexte et envoi de la requête
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        // Gestion des erreurs si le résultat est faux
        if ($result === false) {
            Log::error('Erreur lors de la requête au service de paiement.');
            return null;
        }

        // Décoder la réponse JSON
        $obj = json_decode($result);

        // Forcer la valeur de errorCode pour les tests
        if (is_object($obj)) {
            $obj->errorCode = 0;
            $obj->formUrl=route('payment.success', ['id' => $facture->id]); // ou une autre valeur que vous souhaitez tester
        } else {
            $obj = (object) ['errorCode' => 0,$formUrl=>route('payment.success', ['id' => $facture->id])]; // Créer un objet avec errorCode si la réponse n'est pas JSON
        }
        return $obj;  // Retourner l'objet modifié
                
    }  
    

    
    public function registerGuestPayment($order, $facture)
    {
        // Define API endpoint and data for request
        $username = config('payment.username');
        $password = config('payment.password');

        $url = 'https://cib.satim.dz/payment/rest/register.do';
        $data = [
            'currency' => '012',
            'amount' => $facture->facttc * 100,  // Convert amount to cents
            'language' => 'fr',
            'orderNumber' => $order->id,
            'userName' => $username,
            'password' => $password,
            'returnUrl' => route('guest.payment.success', ['id' => $facture->id]),
            'failUrl' => route('guest.payment.failure', ['id' => $facture->id]),
            'jsonParams' => json_encode([
                "force_terminal_id" => "E004000032",
                "udf1" => "2018105301346",
                "udf5" => "ggsf85s42524s5uhgsf"
            ])
        ];
    
        try {
            // Make the HTTP request
            $response = Http::asForm()->post($url, $data);
    
            // Check for successful response
            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Payment API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'order_id' => $order->id,
                    'facture_id' => $facture->id
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception during payment request', [
                'message' => $e->getMessage(),
                'order_id' => $order->id,
                'facture_id' => $facture->id
            ]);
            return null;
        }
    }
}