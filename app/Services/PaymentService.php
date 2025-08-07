<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    // Méthode pour enregistrer un paiement
    public function registerPayment($order, $facture, $user)
    {
        $username = config('payment.username');
        $password = config('payment.password');
        //TEST
        //$url = 'https://test.satim.dz/payment/rest/register.do?';
        //PRODUCTION
        $url = 'https://cib.satim.dz/payment/rest/register.do';
        
        $data = [
            'currency' => '012',
            'amount' => $facture->facttc * 100,  // Convertir en centimes
            'language' => 'fr',
            'orderNumber' => $order->id,
            'userName' => $username,
            'password' => $password,
            'returnUrl' => route('payment.success', ['id' => $facture->id,'user_id' => $user->id,'token' => $order->payment_token]),
            'failUrl' => route('payment.failure', ['id' => $facture->id,'user_id' => $user->id,'token' => $order->payment_token]),
            'jsonParams' => json_encode([
                //"force_terminal_id" => "E004000032", E010901509
                "force_terminal_id" => "E004000135",
                "udf1" => "EPAL".$facture->facnum,
                "udf5" => "ggsf85s42524s5uhgsf"
            ])
        ];
        //dd($data);
        // Initialiser cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Désactiver la vérification SSL (à éviter en production)
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
    
        // Exécuter la requête
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
    
        // Vérification des erreurs cURL
        if ($result === false) {
            Log::error("Erreur cURL lors de la requête à $url : $curlError");
            return null;
        }
    
        // Vérifier le code HTTP
        if ($httpCode !== 200) {
            Log::error("Erreur API paiement - Code HTTP : $httpCode - Réponse : $result");
            return null;
        }
    
        // Décoder la réponse JSON
        $obj = json_decode($result);
        if (!is_object($obj)) {
            Log::error("Réponse JSON invalide de l'API paiement : $result");
            return null;
        }
    
        // Forcer un test si nécessaire (facultatif)
        // $obj->errorCode = 0;
        // $obj->formUrl = route('payment.success', ['id' => $facture->id]);
        //dd($obj);
        return $obj;
    }
    


}