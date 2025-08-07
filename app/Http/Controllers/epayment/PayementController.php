<?php

namespace App\Http\Controllers\epayment;

use Illuminate\Http\Request;
use App\Mail\MailOrder;
use App\Models\epayment\Facture;
use App\Models\epayment\Detfacture;
use App\Models\epayment\ConfirmOrder;
use App\Models\epayment\Recu_paiement;
use App\Models\epayment\EOrder;
use App\Models\User;
use App\Models\epayment\FailedOrder;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\epayment\Controller;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Post;
use PDF;
use Carbon\Carbon;

class PayementController extends Controller
{    
    
    protected $paymentService;

    // Injection du PaymentService via le constructeur
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    // Affichage Interface de paiement
    public function payerFacture(Request $request,$id)
    {
        $facture = Facture::find($id);
        //dd($facture);
        return view('payement.payer', compact('facture'));
    }

    //Premier Processus de Paiement
    public function processPayment (Request $request,$id, $email)
    {
        $request->validate([
            // 'g-recaptcha-response' => 'required|captcha',
            'terms' =>'accepted'
        ]);
        Log::info('abdouuu');
        $facture = Facture::where('id', $id)->first();
       //dd($facture);
        if (!$facture) {
            return redirect()->back()->with('error', 'Facture introuvable.');
        }
        $user = User::where('Email', $email)->first();
        if (!$user) {
            return redirect()->back()->with('error', 'Utilisateur non authentifié.');
        }
        
        $order = new EOrder();
        $order->facnum = $facture->facnum;
        $order->user_id=$user->id;
        $order->domcod=$facture->domcod;
        $order->payment_token = Str::random(60);
        $order->payment_token_expires_at = Carbon::now()->addMinutes(30);
        $order->save();

        //dd($order);
        // Appeler le PaymentService pour enregistrer le paiement
        $response = $this->paymentService->registerPayment($order, $facture, $user);

        if ($response && $response->errorCode == 0) {
            // Redirection vers l'URL de paiement en cas de succès
            $orderID=$order->id;
            return redirect()->away($response->formUrl);
        } else {
            return redirect()->route('payment.failure',$facture->id)->with('error', 'Erreur de paiement : ' . $response->errorMessage);
        }
    }

    //Succès de paiement
    public function success(Request $request, $id)
    {
        $token = $request->query('token');
        if (!$token) {
            return redirect()->route('payement.error')->with('error', 'Token de paiement manquant.');
        }

        $order = EOrder::where('payment_token', $token)->first();
        if (!$order) {
            abort(403, 'Commande inconnue ou token invalide.');
        }

        // Vérification de l'expiration
        if ($order->payment_token_expires_at && Carbon::now()->greaterThan($order->payment_token_expires_at)) {
            abort(403, 'Le token de paiement a expiré.');
        }

        $user = User::find($order->user_id);

        if (!$user) {
            abort(403, 'Utilisateur associé à la commande introuvable.');
        }

        // Authentifier l'utilisateur
        Auth::login($user);

        // (Optionnel) Supprimer le token pour éviter réutilisation
        /*$order->payment_token = null;
        $order->payment_token_expires_at = null;
        $order->save();*/

        // Vérification de la présence de orderId
        $orderId = $request->input('orderId') ?? $request->query('orderId') ?? $request->input('order_id');
        // Récupération de la facture
        //dd($orderId);
        //verifier si le paiement a été effectué
        
        
        Log::info('Requête reçue pour success : ', $request->all());

        // Vérification de la méthode HTTP
        Log::info('Méthode HTTP : ' . $request->method());
        
        $facture = Facture::findOrFail($id);
        if (!$orderId) {
            return view('payement.error', [
                'facture' => $facture,
                'message' => 'Identifiant de commande manquant.'
            ]);
        }

        $facture = Facture::findOrFail($id);

        // 🛡️ Vérification si déjà payé
        /*if ($facture->status == 1) {
            $dateValable = Carbon::parse($facture->created_at)->addHour()->format('d-m-Y H:i:s');
        
            // Génère le PDF si besoin
            $pdf = $this->generatePDF($facture, null, $dateValable);
        
            return view('payement.success', compact('facture', 'dateValable','orderId','order'))
                    ->with('info', 'Votre facture a déjà été payée. Le reçu de paiement est disponible.');
        }*/

        $verif_confirm = ConfirmOrder::where('orderid', $orderId)
                ->where('facnum', $facture->facnum)
                ->first();

        if ($verif_confirm) {
            $dateValable = Carbon::parse($verif_confirm->created_at)->addHour()->format('d-m-Y H:i:s');
            $filename = $facture->domcod . '_' . $facture->facnum . '.pdf';
            $filePath = 'download/' . $filename;
            $this->generatePDF($facture, null, $dateValable);
            /*if (!Storage::exists($filePath)) {
                
            }*/
            $obj=$verif_confirm;
            $order=$verif_confirm;
            return view('payement.success', compact('facture', 'dateValable','obj','orderId','order'))
                ->with('info', 'Votre paiement a déjà été validé. Voici votre reçu.');
        }
        // Récupération des credentials API
        $username = config('payment.username');
        $password = config('payment.password');

        if (!$username || !$password) {
            return view('payement.error', [
                'facture' => $facture,
                'message' => 'Problème de configuration du paiement.'
            ]);
            
        }

        // Requête à l’API
        $language = 'fr';
        //TEST
        //$url = "https://test.satim.dz/payment/rest/confirmOrder.do?language=$language&orderId=$orderId&password=$password&userName=$username";
        
        //PRODUCTION
        $url = "https://cib.satim.dz/payment/rest/confirmOrder.do?language=$language&orderId=$orderId&password=$password&userName=$username";

        try {
            // Utilisation de cURL au lieu de file_get_contents
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($ch);
            curl_close($ch);

            $obj = json_decode($result);

            if (!$obj || !isset($obj->ErrorCode)) {
                return view('payement.error', [
                    'facture' => $facture,
                    'message' => 'Réponse invalide de l’API de paiement.'
                ]);
                
            }
            If ($obj->params->respCode=="00" and $obj->OrderStatus=="2"){
            // Vérification du paiement
                if (isset($obj->ErrorCode) && $obj->ErrorCode == 0) {
                    //dd('test');
                    $this->createConfirmOrder($obj, $facture, $orderId);
                    //$this->createRecuPaiement($facture->facnum, $orderId);
                    //dd($facture);
                    $facture->status = 1;
                    $facture->save();
                    
                }
                $order=ConfirmOrder::where ('facnum',$facture->facnum)
                ->first();
                $dateValable = Carbon::parse($order->created_at)->addHour()->format('d-m-Y H:i:s');
                $pdf = $this->generatePDF($facture, $obj, $dateValable);
                $this->sendusermail($order->recuId);

                return view('payement.success', compact('facture', 'obj', 'orderId', 'dateValable','order'))
                    ->with('info', 'Le reçu de paiement vous a été envoyé par mail !');
            
            }
            else{
                return view('paiement.failure', compact('facture', 'obj'))->with('error', 'Échec du paiement.');
            }

        } catch (\Exception $e) {
            Log::error("Erreur de paiement : " . $e->getMessage());
            return view('payement.error', [
                'facture' => $facture,
                'message' => 'Une erreur est survenue lors du traitement du paiement.'
            ]);
        }
    }
    //création du confirm order
    private function createConfirmOrder($obj, $facture, $orderId)
    {   
        try {
            ConfirmOrder::create([
                'facnum' => $facture->facnum,
                'facrfe'=>$facture->facrfe ?? null,
                'trscod'=>$facture->trscod ?? null,
                'domcod'=>$facture->domcod ?? null,
                'orderid' => $orderId,
                'user_id'=>Auth::user()->id ?? null,
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
            // Affiche l'erreur à l'écran (utile en dev, pas en prod)
            //dd("Erreur lors de l'enregistrement de la commande confirmée : " . $e->getMessage());
        
            // OU en production, logguer l'erreur sans l'afficher
            Log::error("Erreur lors de l'enregistrement ConfirmOrder: " . $e->getMessage());
        
            // Et rediriger avec un message d'erreur
            // return redirect()->back()->with('error', 'Une erreur est survenue lors de l\'enregistrement du paiement.');
        }
       
    }

    private function generatePDF($facture, $obj, $dateValable)
    {
        $order = ConfirmOrder::where('facnum', $facture->facnum)->first();

        $pdf = PDF::loadView('payement.recu', compact('facture', 'order', 'dateValable'));

        $fileName = $facture->domcod . '_' . $facture->facnum . '.pdf';
        $storagePath = 'download/' . $fileName;

        // Assure-toi que le dossier existe
        \Storage::makeDirectory('download');

        // Sauvegarde dans storage/app/download/
        \Storage::put($storagePath, $pdf->output());

        // Log pour debug
        if (\Storage::exists($storagePath)) {
            \Log::info("✅ PDF généré avec succès : $storagePath");
        } else {
            \Log::error("❌ Échec de création du PDF : $storagePath");
        }

        // Optionnel : retour PDF dans navigateur
        return $pdf->stream('download.pdf');
    }


    //erreur de paiement
    public function errorPayment(Request $request)
    {
        //dd($id);
        // Gérer les erreurs génériques
        return view('payement.error');
    }


    /*private function isPaymentSuccessful($obj)
    {
        return isset($obj->params->respCode, $obj->OrderStatus, $obj->ErrorCode)
            && $obj->params->respCode == "00"
            && $obj->ErrorCode == "0"
            && $obj->OrderStatus == "2";
    }*/

    // Echec de paiement
    public function failure(Request $request, $id)
    {
        $token = $request->query('token');
        if (!$token) {
            return redirect()->route('payment.error')->with('error', 'Token de paiement manquant.');
        }

        $order = EOrder::where('payment_token', $token)->first();

        if (!$order) {
            abort(403, 'Commande inconnue ou token invalide.');
        }

        // Vérification de l'expiration
        if ($order->payment_token_expires_at && Carbon::now()->greaterThan($order->payment_token_expires_at)) {
            abort(403, 'Le token de paiement a expiré.');
        }

        $user = User::find($order->user_id);

        if (!$user) {
            abort(403, 'Utilisateur associé à la commande introuvable.');
        }

        // Authentifier l'utilisateur
        Auth::login($user);

        // (Optionnel) Supprimer le token pour éviter réutilisation
        /*$order->payment_token = null;
        $order->payment_token_expires_at = null;
        $order->save();*/
        // Vérification des données reçues

        Log::info("Données reçues pour échec de paiement :", $request->all());
        // Recherche de la facture
        try {
            $facture = Facture::findOrFail($id);
        } catch (\Exception $e) {
            Log::error("Facture introuvable pour l'ID : $id");
            return view('payement.error', [
                'facture' => $facture,
                'message' => "Facture introuvable pour l'ID : $id"
            ]);
        }
        $orderId = $request->input('orderId');
    
        if (!$orderId) {
            Log::error("orderId est null ou absent dans la requête.");
            return view('payement.error', [
                'facture' => $facture,
                'message' => "L'identifiant de la commande est introuvable."
            ]);
        
        }
    
        // Récupération des identifiants de paiement
        $username = config('payment.username');
        $password = config('payment.password');
        $currency = '012';
        $language = 'fr';
    
        // Construction de l'URL
        //TEST
        //$url = "https://test.satim.dz/payment/rest/confirmOrder.do?language=$language&orderId=$orderId&password=$password&userName=$username";
        //PRODUCTION
        $url = "https://cib.satim.dz/payment/rest/confirmOrder.do?language=$language&orderId=$orderId&password=$password&userName=$username";
    
        // Initialisation de cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Désactiver la vérification SSL en développement
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
        // Exécution de la requête
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
    
        // Vérification des erreurs cURL
        if ($result === false) {
            Log::error("Erreur cURL lors de la requête : $curlError");
            return view('error', ['message' => "Erreur lors de la communication avec le serveur de paiement."]);
        }
    
        // Vérification du code HTTP
        if ($httpCode !== 200) {
            Log::error("Erreur API - Code HTTP : $httpCode - Réponse : $result");
            return view('error', ['message' => "Erreur lors de la récupération des informations de paiement."]);
        }
    
        // Décodage de la réponse JSON
        $obj = json_decode($result);
        //dd($obj);
        if (!is_object($obj)) {
            Log::error("Réponse JSON invalide : $result");
            return view('error', ['message' => "Réponse invalide du serveur de paiement."]);
        }

        $message = 'Une erreur inconnue est survenue.';
        $orderstatus = '';
        $respCode_desc = '';
        $SvfeResponse = '';

            if ($obj->ErrorCode != 0 && $obj->ErrorCode != 3) {
                switch ($obj->ErrorCode) {
                    case 7:
                        $message = "ERREUR SYSTEM, Veuillez contacter l'administrateur du site";
                        break;
                    case 6:
                        $message = "ERREUR N° ORDRE, Veuillez contacter l'administrateur du site";
                        break;
                    case 5:
                    case 3:
                        $message = "ACCES REFUSE, Veuillez contacter l'administrateur du site";
                        break;
                    case 2:
                        $message = "TRANSACTION DEJA VALIDEE, Veuillez contacter l'administrateur du site";
                        break;
                    case 1:
                        $message = "N° ORDRE DEJA ATTRIBUE, Veuillez contacter l'administrateur du site";
                        break;
                    default:
                        $message = "Erreur non gérée (code: {$obj->ErrorCode}).";
                        break;
                }
            } else {
                $orderstatus = $obj->OrderStatus ?? '';

                if (isset($obj->params->respCode_desc)) {
                    $respCode_desc = $obj->params->respCode_desc;
                    $message = $respCode_desc;
                } elseif (isset($obj->actionCodeDescription)) {
                    $message = $obj->actionCodeDescription;
                }
            

                $SvfeResponse = $obj->SvfeResponse ?? '';
            }
           
        // Enregistrement de l'échec du paiement
        $this->createFailedOrderRecord($obj, $facture->facnum, $orderId);
    
        return view('payement.echec1', compact('facture','orderstatus', 'message'));
    }

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
            // Affiche l'erreur à l'écran (utile en dev, pas en prod)
            //dd("Erreur lors de l'enregistrement de la commande confirmée : " . $e->getMessage());
        
            // OU en production, logguer l'erreur sans l'afficher
            Log::error("Erreur lors de l'enregistrement FailedOrder: " . $e->getMessage());
        
            // Et rediriger avec un message d'erreur
            //return redirect()->back()->with('error', 'Une erreur est survenue lors de l\'enregistrement du paiement.');
        }
        
    }

    public function printRecu(Request $request, $recuid)
    {
        $order=ConfirmOrder::where ('recuId',$recuid)
                ->first();
        $eorder=EOrder::where ('id',$order->OrderNumber)
                ->first();
    
                // Idem pour la facture
        $facture = $eorder ? Facture::where('facnum', $eorder->facnum)
                                  ->where('domcod', $eorder->domcod)
                                  ->first()
                       : null;

        $date = date('Y-m-d H:i:s',strtotime('+1 hour',strtotime($order->created_at)));
        $dateValable=date_format(date_create($date),'d-m-Y H:i:s');
        //$pdf = PDF::loadView('payement.recu',compact('facture','order','recu','dateValable'));
        $pdf = PDF::loadView('payement.recu',compact('facture','order','dateValable'));
        //$pdf->save('/my_stored_file.pdf');
        return $pdf->stream();
    }

    public function downloadrecu (Request $request, $recuid)
    
    {
        $order=ConfirmOrder::where ('recuId',$recuid)
                ->first();
        $eorder=EOrder::where ('id',$order->OrderNumber)
                ->first();
    
                // Idem pour la facture
        $facture = $eorder ? Facture::where('facnum', $eorder->facnum)
                                  ->where('domcod', $eorder->domcod)
                                  ->first()
                       : null;

        $date = date('Y-m-d H:i:s',strtotime('+1 hour',strtotime($order->created_at)));
        $dateValable=date_format(date_create($date),'d-m-Y H:i:s');
        //$pdf = PDF::loadView('payement.recu',compact('facture','order','recu','dateValable'));
        $pdf = PDF::loadView('payement.recu',compact('facture','order','dateValable'));
        $pdf_name = "epal_recu_paiement_n_".$order->recuId.'-'.$order->facnum.".pdf";
        //On le télécharge
        return $pdf->download($pdf_name);

    }


    public function sendusermail($recuid)
    {  
        Mail::to(Auth::user()->email)->send( new MailOrder(Auth::user(),$recuid));
	Mail::to("caissier@portalger.com.dz")->send( new MailOrder(Auth::user(),$recuid));

    }

    public function sendmail (Request $request, $recuid){


        if ($request->email)
        {
            Mail::to($request->email)->send( new MailOrder(Auth::user(),$recuid));
            return Redirect::back()->with('success', 'Email Envoyé');
        } 
    }

    public function consult (Request $request, $recuid){
        $obj=ConfirmOrder::where ('recuId',$recuid)
                ->first();
                $order=$obj;
        $eorder=EOrder::where ('id',$obj->OrderNumber)
                ->first();
                $orderId=$eorder->id;
                // Idem pour la facture
        $facture = $eorder ? Facture::where('facnum', $eorder->facnum)
                                  ->where('domcod', $eorder->domcod)
                                  ->first()
                       : null;
        $date = date('Y-m-d H:i:s',strtotime('+1 hour',strtotime($obj->created_at)));
        $dateValable=date_format(date_create($date),'d-m-Y H:i:s');
      
        return view('payement.success', compact('facture', 'obj', 'orderId', 'dateValable','order'))
        ->with('info', 'Le reçu de paiement vous a été envoyé par mail !');
    }


}
