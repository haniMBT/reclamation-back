<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use App\Models\epayment\Facture;
use App\Models\epayment\ConfirmOrder;
use App\Models\epayment\EOrder;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MailOrder extends Mailable
{
    use Queueable, SerializesModels;
    public $orderdata;
    public $eorderdata;
    public $facturedata;
    public $userdata;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user,$id)
    {
       
        $this->userdata=$user;
        
        $this->orderdata=ConfirmOrder::where ('id',$id)
                ->first();
        $this->eorderdata=EOrder::where ('id',$this->orderdata->OrderNumber)
                ->first();
    
                // Idem pour la facture
        $this->facturedata = $this->eorderdata ? Facture::where('facnum', $this->eorderdata->facnum)
                                  ->where('domcod', $this->eorderdata->domcod)
                                  ->first()
                       : null;
                   
    }

    /**
     * Build the message.
     *
     * @return $this
     */

    public function build()
{
    $subject = "Reçu de Paiement de la facture EPAL N° " . $this->facturedata->facrfe . " du "
        . date_format(date_create($this->facturedata->facdat), "d-m-Y");

    $fileName = $this->facturedata->domcod . '_' . $this->facturedata->facnum . '.pdf';
    $filePath = 'download/' . $fileName; // Chemin relatif à storage/app

    if (\Storage::exists($filePath)) {
        return $this->subject($subject)
                    ->view('emails.ordermail')
                    ->attachFromStorage($filePath);
    } else {
        \Log::error("❌ PDF non trouvé pour attachement : $filePath");
        return $this->subject($subject)
                    ->view('emails.ordermail'); // mail sans pièce jointe
    }
}
}
