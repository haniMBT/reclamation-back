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
use PDF;

use App\Post;

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
    public function payerFacture(Request $request, $id)
    {
        $facture = Facture::find($id);
        return view('payement.payer', compact('facture'));
    }

    // Premier Processus de Paiement
    public function processPayment(Request $request, $id, $email)
    {
        $request->validate([
            // 'g-recaptcha-response' => 'required|captcha',
            'terms' => 'accepted'
        ]);
        $facture = Facture::where('id', $id)->first();
        if (!$facture) {
            return redirect()->back()->with('error', 'Facture introuvable.');
        }
        $user = User::where('Email', $email)->first();
        if (!$user) {
            return redirect()->back()->with('error', 'Utilisateur non authentifié.');
        }

        $order = new EOrder();
        $order->facnum = $facture->facnum;
        $order->user_id = $user->id;
        $order->domcod = $facture->domcod;
        $order->payment_token = Str::random(60);
        $order->payment_token_expires_at = Carbon::now()->addMinutes(30);
        $order->save();

        // Appeler le PaymentService pour enregistrer le paiement
        $response = $this->paymentService->registerPayment($order, $facture, $user);

        // Gestion robuste du résultat pour éviter l'accès à une propriété sur null
        if (is_object($response) && isset($response->errorCode) && $response->errorCode == 0 && !empty($response->formUrl)) {
            // Redirection vers l'URL de paiement en cas de succès
            return redirect()->away($response->formUrl);
        }

        // En cas d'échec ou réponse nulle, journaliser et rediriger vers l'échec
        Log::error('Erreur lors de registerPayment', [
            'facture_id' => $facture->id,
            'order_id' => $order->id,
            'response' => $response,
        ]);

        $message = is_object($response)
            ? ($response->errorMessage ?? 'Erreur de paiement inconnue.')
            : "Erreur lors de l'initialisation du paiement.";

        return redirect()->route('payment.failure', $facture->id)->with('error', 'Erreur de paiement : ' . $message);
    }

    // Succès de paiement (indépendant du paramètre token)
    public function success(Request $request, $id)
    {
        Log::info('Request data:', $request->all());

        // Charger la facture dès le début pour accès à facnum
        $facture = Facture::findOrFail($id);

        // Rendre le token optionnel et utiliser des mécanismes de repli pour déterminer l'utilisateur
        $token = $request->query('token');
        $order = null;
        $user = null;

        if ($token) {
            $order = EOrder::where('payment_token', $token)->first();
            if ($order) {
                // Vérification de l'expiration
                if ($order->payment_token_expires_at && Carbon::now()->greaterThan($order->payment_token_expires_at)) {
                    abort(403, 'Le token de paiement a expiré.');
                }
                // Déterminer l'utilisateur via l'ordre
                $user = User::find($order->user_id);
            }
        }

        // Repli 1: via paramètre user_id si présent dans l'URL de retour
        if (!$user) {
            $userId = $request->input('user_id') ?? $request->query('user_id');
            if ($userId) {
                $user = User::find($userId);
            }
        }

        // Repli 2: dernier EOrder pour la facture en question
        if (!$user) {
            $lastEorder = EOrder::where('facnum', $facture->facnum)->orderBy('created_at', 'desc')->first();
            if ($lastEorder) {
                $user = User::find($lastEorder->user_id);
            }
        }

        // Authentifier si utilisateur déterminé, sinon continuer en invité
        if ($user) {
            Auth::login($user);
        }

        // Vérification de la présence de orderId
        $orderId = $request->input('orderId') ?? $request->query('orderId') ?? $request->input('order_id');
        Log::info('Requête reçue pour success : ', $request->all());
        Log::info('Méthode HTTP : ' . $request->method());
        if (!$orderId) {
            return view('payement.error', [
                'facture' => $facture,
                'message' => 'Identifiant de commande manquant.'
            ]);
        }

        // Vérifier si déjà confirmé
        $verif_confirm = ConfirmOrder::where('orderid', $orderId)
            ->where('facnum', $facture->facnum)
            ->first();

        if ($verif_confirm) {
            $dateValable = Carbon::parse($verif_confirm->created_at)->addHour()->format('d-m-Y H:i:s');
            $this->generatePDF($facture, null, $dateValable);
            $obj = $verif_confirm;
            $order = $verif_confirm;
            return view('payement.success', compact('facture', 'dateValable', 'obj', 'orderId', 'order'))
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

        // Requête à l’API (production)
        $language = 'fr';
        $url = "https://cib.satim.dz/payment/rest/confirmOrder.do?language=$language&orderId=$orderId&password=$password&userName=$username";

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

        if (($obj->params->respCode ?? null) == "00" && ($obj->OrderStatus ?? null) == "2") {
            // Vérification du paiement
            if (isset($obj->ErrorCode) && $obj->ErrorCode == 0) {
                $this->createConfirmOrder($obj, $facture, $orderId);
                $facture->status = 1;
                $facture->save();
            }
            $order = ConfirmOrder::where('facnum', $facture->facnum)->first();
            $dateValable = Carbon::parse($order?->created_at)->addHour()?->format('d-m-Y H:i:s');
            $this->generatePDF($facture, $obj, $dateValable);
            if ($order) {
                $this->sendusermail($order->id);
            }

            return view('payement.success', compact('facture', 'obj', 'orderId', 'dateValable', 'order'))
                ->with('info', 'Le reçu de paiement vous a été envoyé par mail !');
        } else {
            return view('payement.failure', compact('facture', 'obj'))->with('error', 'Échec du paiement.');
        }
    }

    // Erreur de paiement (générique)
    public function errorPayment(Request $request)
    {
        return view('payement.error');
    }

    /*private function isPaymentSuccessful($obj)
    {
        return isset($obj->params->respCode, $obj->OrderStatus, $obj->ErrorCode)
            && $obj->params->respCode == "00"
            && $obj->ErrorCode == "0"
            && $obj->OrderStatus == "2";
    }*/

    // Échec de paiement (indépendant du paramètre token)
    public function failure(Request $request, $id)
    {
        Log::info('Request data:', $request->all());

        // Token optionnel, utilisation de mécanismes de repli pour déterminer l'utilisateur
        $token = $request->query('token');
        $order = null;
        $user = null;

        if ($token) {
            $order = EOrder::where('payment_token', $token)->first();
            if ($order) {
                if ($order->payment_token_expires_at && Carbon::now()->greaterThan($order->payment_token_expires_at)) {
                    abort(403, 'Le token de paiement a expiré.');
                }
                $user = User::find($order->user_id);
            }
        }

        if (!$user) {
            $userId = $request->input('user_id') ?? $request->query('user_id');
            if ($userId) {
                $user = User::find($userId);
            }
        }

        if ($user) {
            Auth::login($user);
        }

        // Recherche de la facture
        try {
            $facture = Facture::findOrFail($id);
        } catch (\Exception $e) {
            Log::error("Facture introuvable pour l'ID : $id");
            return view('payement.error', [
                'facture' => isset($facture) ? $facture : null,
                'message' => "Facture introuvable pour l'ID : $id"
            ]);
        }

        // Récupérer orderId
        $orderId = $request->input('orderId') ?? $request->query('orderId') ?? $request->input('order_id');
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
        $language = 'fr';

        // Construction de l'URL (production)
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
            return view('payement.error', ['message' => "Erreur lors de la communication avec le serveur de paiement."]);
        }

        // Vérification du code HTTP
        if ($httpCode !== 200) {
            Log::error("Erreur API - Code HTTP : $httpCode - Réponse : $result");
            return view('payement.error', ['message' => "Erreur lors de la récupération des informations de paiement."]);
        }

        // Décodage de la réponse JSON
        $obj = json_decode($result);
        if (!is_object($obj)) {
            Log::error("Réponse JSON invalide : $result");
            return view('payement.error', ['message' => "Réponse invalide du serveur de paiement."]);
        }

        $message = 'Une erreur inconnue est survenue.';
        $orderstatus = '';

        if (($obj->ErrorCode ?? null) != 0 && ($obj->ErrorCode ?? null) != 3) {
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
                $message = $obj->params->respCode_desc;
            } elseif (isset($obj->actionCodeDescription)) {
                $message = $obj->actionCodeDescription;
            }
        }

        // Enregistrement de l'échec du paiement
        $this->createFailedOrderRecord($obj, $facture->facnum, $orderId);

        return view('payement.echec1', compact('facture', 'orderstatus', 'message'));
    }

    // Création d'un enregistrement d'échec
    private function createFailedOrderRecord($obj, $facnum, $orderId)
    {
        try {
            // Trouver l'EOrder local le plus récent pour cette facture
            $eorder = EOrder::where('facnum', $facnum)
                ->orderBy('created_at', 'desc')
                ->first();

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
                // Utiliser l'id local de EOrder pour éviter le dépassement numérique
                'OrderNumber' => $eorder?->id,
                'Pan' => $obj->Pan ?? null,
                'Ip' => $obj->Ip ?? request()->ip(),
                'SvfeResponse' => $obj->SvfeResponse ?? null,
                'Amount' => $obj->Amount ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'enregistrement FailedOrder: " . $e->getMessage());
        }
    }

    private function createConfirmOrder($obj, $facture, $orderId)
    {
        Log::info('SATIM confirm response: ' . json_encode($obj));

        // Trouver l'EOrder local le plus récent pour cette facture (et domaine)
        $eorder = EOrder::where('facnum', $facture->facnum)
            ->where('domcod', $facture->domcod)
            ->orderBy('created_at', 'desc')
            ->first();

        ConfirmOrder::create([
            'facnum' => $facture->facnum,
            'facrfe' => $facture->facrfe ?? null,
            'trscod' => $facture->trscod ?? null,
            'domcod' => $facture->domcod ?? null,
            'orderid' => $orderId,
            'user_id' => Auth::user()->id ?? null,
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
            // Utiliser l'id local de EOrder pour éviter le dépassement numérique et garder la relation locale
            'OrderNumber' => $eorder?->id,
            'Pan' => $obj->Pan ?? null,
            'Ip' => $obj->Ip ?? request()->ip(),
            'SvfeResponse' => $obj->SvfeResponse ?? null,
            'Amount' => $obj->Amount ?? null,
        ]);
    }

    private function generatePDF($facture, $obj, $dateValable)
    {
        $order = ConfirmOrder::where('facnum', $facture->facnum)->first();

        // Génère le PDF depuis la vue Blade
        $pdf = PDF::loadView('payement.recu', compact('facture', 'order', 'dateValable'));

        // Nom de fichier
        $fileName = $facture->domcod . '_' . $facture->facnum . '.pdf';
        $storagePath = 'download/' . $fileName;

        // Crée le dossier s'il n'existe pas
        Storage::makeDirectory('download');

        // Sauvegarde le fichier dans storage/app/download/
        Storage::put($storagePath, $pdf->output());

        // Log de succès
        if (Storage::exists($storagePath)) {
            Log::info("✅ PDF généré avec succès : $storagePath");
        } else {
            Log::error("❌ Échec de création du PDF : $storagePath");
        }

        // Retourne le PDF dans le navigateur
        return $pdf->stream('download.pdf');
    }

    public function printRecu(Request $request, $id)
    {
        $order = ConfirmOrder::where('id', $id)->first();
        $eorder = EOrder::where('id', $order->OrderNumber)->first();

        // Idem pour la facture
        $facture = $eorder ? Facture::where('facnum', $eorder->facnum)
            ->where('domcod', $eorder->domcod)
            ->first()
            : null;

        $date = date('Y-m-d H:i:s', strtotime('+1 hour', strtotime($order->created_at)));
        $dateValable = date_format(date_create($date), 'd-m-Y H:i:s');
        $pdf = PDF::loadView('payement.recu', compact('facture', 'order', 'dateValable'));
        return $pdf->stream();
    }

    public function downloadrecu(Request $request, $id)
    {
        $order = ConfirmOrder::where('id', $id)->first();
        $eorder = EOrder::where('id', $order->OrderNumber)->first();

        // Idem pour la facture
        $facture = $eorder ? Facture::where('facnum', $eorder->facnum)
            ->where('domcod', $eorder->domcod)
            ->first()
            : null;

        $date = date('Y-m-d H:i:s', strtotime('+1 hour', strtotime($order->created_at)));
        $dateValable = date_format(date_create($date), 'd-m-Y H:i:s');
        $pdf = PDF::loadView('payement.recu', compact('facture', 'order', 'dateValable'));
        $pdf_name = "epal_recu_paiement_n_" . $order->id . '-' . $order->facnum . ".pdf";
        return $pdf->download($pdf_name);
    }

    public function downloadrecuByFacture(Request $request, $id)
    {
        $facture = Facture::find($id);
        $order = ConfirmOrder::where('facnum', $facture->facnum)
            ->orderBy('created_at', 'desc')
            ->first();
        $eorder = EOrder::where('id', $order->OrderNumber)->first();

        // Idem pour la facture
        $facture = $eorder ? Facture::where('facnum', $eorder->facnum)
            ->where('domcod', $eorder->domcod)
            ->first()
            : null;

        $date = date('Y-m-d H:i:s', strtotime('+1 hour', strtotime($order->created_at)));
        $dateValable = date_format(date_create($date), 'd-m-Y H:i:s');
        $pdf = PDF::loadView('payement.recu', compact('facture', 'order', 'dateValable'));
        $pdf_name = "epal_recu_paiement_n_" . $order->id . '-' . $order->facnum . ".pdf";
        return $pdf->download($pdf_name);
    }

    public function sendusermail($id)
    {
        if (Auth::user()) {
            Mail::to(Auth::user()->Email)->send(new MailOrder(Auth::user(), $id));
        }
        Mail::to("caissier@portalger.com.dz")->send(new MailOrder(Auth::user(), $id));
    }

    public function sendmail(Request $request, $id)
    {
        if ($request->email) {
            $order = EOrder::where('id', $id)->first();
            $user = $order ? User::where('id', $order->user_id)->first() : null;
            if ($user) {
                Mail::to($request->email)->send(new MailOrder($user, $id));
                return Redirect::back()->with('success', 'Email Envoyé');
            }
        }
        return Redirect::back()->with('error', 'Email invalide ou utilisateur introuvable');
    }

    public function consult(Request $request, $id)
    {
        $obj = ConfirmOrder::where('id', $id)->first();
        $order = $obj;
        $eorder = EOrder::where('id', $obj->OrderNumber)->first();
        $orderId = $eorder->id;
        // Idem pour la facture
        $facture = $eorder ? Facture::where('facnum', $eorder->facnum)
            ->where('domcod', $eorder->domcod)
            ->first()
            : null;
        $date = date('Y-m-d H:i:s', strtotime('+1 hour', strtotime($obj->created_at)));
        $dateValable = date_format(date_create($date), 'd-m-Y H:i:s');

        return view('payement.success', compact('facture', 'obj', 'orderId', 'dateValable', 'order'))
            ->with('info', 'Le reçu de paiement vous a été envoyé par mail !');
    }
}
