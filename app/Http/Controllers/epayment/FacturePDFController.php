<?php

namespace App\Http\Controllers\epayment;
use App\Services\ChiffresEnLettres;
use App\Models\epayment\Facture;
use App\Models\epayment\Detfacture;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Codedge\Fpdf\Fpdf\Fpdf;
use URL;

class FacturePDFController extends Controller
{
    //
    private $fpdf;

    public function __construct()
    {
         
    }

    public function generate($id)
    {
        
        $f=Facture::where ('id',$id)
        ->first();  
        $fdetail=Detfacture::where('facnum',$f->facnum)
        ->get();
        
        $this->fpdf = new Fpdf;
        $this->fpdf->AddPage(); 
        
        // Logo
        $image1 = public_path()."/img/header-epal.png";
        
        //$this->fpdf->Image(URL::to('dist/img/img055.png'),5,3,200);
        //$this->fpdf->Image('dist/img/img055.png');
        $this->fpdf->Image($image1,5,3,200);
        $this->fpdf->Ln(40);


        $this->fpdf->SetDrawColor(0,0,0);  
        $this->fpdf->SetLineWidth(0.2);   
        $this->fpdf->Rect(5,58,200,40, "D");
        //Titre Facture 
        if ($f->mrgcod=='TERME'){

            // Police helvetica gras 15
            $this->fpdf->SetFont('helvetica','B',20);
            // Décalage à droite
            $this->fpdf->Cell(80);
            // Titre
            $this->fpdf->Cell(30,8,'FACTURE A TERME',0,0,'C');
            // Saut de ligne
            $this->fpdf->Ln(20);
        }
        else {
    
            // Police helvetica gras 15
            $this->fpdf->SetFont('helvetica','B',20);
            // Décalage à droite
            $this->fpdf->Cell(80);
            // Titre
            $this->fpdf->Cell(30,8,'FACTURE AU COMPTANT',0,0,'C');
            // Saut de ligne
            $this->fpdf->Ln(20);
    
        }

            //Informations Facture
            $this->fpdf->SetFont('helvetica','B',12);
            $this->fpdf->SetXY(10,60);
            $this->fpdf->MultiCell(40,5,utf8_decode("FACTURE N° :"),'','L',false);
            $this->fpdf->SetXY(50,60);
            $this->fpdf->MultiCell(60,5,$f->facrfe,'','L',false);
            $this->fpdf->SetTitle(utf8_decode("Facture n° : Votre numéro de facture"));

            $this->fpdf->SetFont('helvetica','B',14);
            $this->fpdf->SetXY(95,60);
            $this->fpdf->Cell(10,5,"DOIT",0,2,'',false);

            $this->fpdf->SetFont('helvetica','B',10);
            $this->fpdf->SetXY(110,60);
            $this->fpdf->MultiCell(100,5,"Client : ".$f->trsnom,'','L',false);

            $this->fpdf->SetFont('helvetica','',10);
            $this->fpdf->SetXY(110,65);
            $this->fpdf->MultiCell(100,5,$f->cnsnnm,'','L',false);

            $this->fpdf->SetFont('helvetica','',10);
            $this->fpdf->SetXY(110,70);
            $this->fpdf->MultiCell(100,5,"Adresse : ".$f->cliadr,'','L',false);

                //Date Facture
            $this->fpdf->SetFont('helvetica','B',10);
            $this->fpdf->SetXY(10,80);
            $this->fpdf->MultiCell(40,5,"Date : ",'','L',false);

            $this->fpdf->SetFont('helvetica','',10);
            $this->fpdf->SetXY(50,80);
            $this->fpdf->MultiCell(30,5,date_format(date_create($f->facdat), 'd-m-Y'),'','L',false);


            //Mode de Réglement

            $this->fpdf->SetFont('helvetica','B',10);
            $this->fpdf->SetXY(10,85);
            $this->fpdf->MultiCell(40,5,utf8_decode("Mode de Réglement : "),'','L',false);

            $this->fpdf->SetFont('helvetica','',10);
            $this->fpdf->SetXY(50,85);
            $this->fpdf->MultiCell(30,5,$f->mrgcod,'','L',false);

                //N° COMPTE

            $this->fpdf->SetFont('helvetica','B',10);
            $this->fpdf->SetXY(10,90);
            $this->fpdf->MultiCell(40,5,utf8_decode("N° COMPTE : "),'','L',false);

            $this->fpdf->SetFont('helvetica','',10);
            $this->fpdf->SetXY(50,90);
            $this->fpdf->MultiCell(30,5,$f->trscod,'','L',false);

            //Reg Com

            $this->fpdf->SetFont('helvetica','B',10);
            $this->fpdf->SetXY(80,80);
            $this->fpdf->MultiCell(40,5,utf8_decode("N° R.C. : "),'','L',false);

            $this->fpdf->SetFont('helvetica','',8);
            $this->fpdf->SetXY(100,80);
            $this->fpdf->MultiCell(40,5,$f->trsnrc,'','L',false);

            //NIF

            $this->fpdf->SetFont('helvetica','B',10);
            $this->fpdf->SetXY(140,80);
            $this->fpdf->MultiCell(20,5,"N.I.F. : ",'','L',false);

            $this->fpdf->SetFont('helvetica','',8);
            $this->fpdf->SetXY(155,80);
            $this->fpdf->MultiCell(50,5,$f->trsnif,'','L',false);

            //TEL

            $this->fpdf->SetFont('helvetica','B',10);
            $this->fpdf->SetXY(80,85);
            $this->fpdf->MultiCell(40,5,utf8_decode("Tél : "),'','L',false);

            $this->fpdf->SetFont('helvetica','',8);
            $this->fpdf->SetXY(100,85);
            $this->fpdf->MultiCell(40,5,$f->trstel,'','L',false);

            //NIS

            $this->fpdf->SetFont('helvetica','B',10);
            $this->fpdf->SetXY(140,85);
            $this->fpdf->MultiCell(20,5,"N.I.S : ",'','L',false);

            $this->fpdf->SetFont('helvetica','',8);
            $this->fpdf->SetXY(155,85);
            $this->fpdf->MultiCell(50,5,$f->trsnis,'','L',false);

            //EMAIL

            $this->fpdf->SetFont('helvetica','B',10);
            $this->fpdf->SetXY(80,90);
            $this->fpdf->MultiCell(40,5,"Email : ",'','L',false);

            $this->fpdf->SetFont('helvetica','',8);
            $this->fpdf->SetXY(100,90);
            $this->fpdf->MultiCell(40,5,$f->trseml,'','L',false);

            //FAX

            $this->fpdf->SetFont('helvetica','B',10);
            $this->fpdf->SetXY(140,90);
            $this->fpdf->MultiCell(20,5,"Fax : ",'','L',false);

            $this->fpdf->SetFont('helvetica','',8);
            $this->fpdf->SetXY(155,90);
            $this->fpdf->MultiCell(50,5,$f->trsfax,'','L',false);

            //N° ESCALE
            $this->fpdf->SetFont('helvetica','B',10);
            $this->fpdf->SetXY(10,100);
            $this->fpdf->MultiCell(40,5,utf8_decode("N° Escale : "),'','L',false);

            $this->fpdf->SetFont('helvetica','',10);
            $this->fpdf->SetXY(50,100);
            $this->fpdf->MultiCell(30,5,$f->escnum,'','L',false);


            if ($f->domcod=='DCAP' or $f->domcod=='DREM'){
                //   Cours
                $this->fpdf->SetFont('helvetica','B',10);
                $this->fpdf->SetXY(80,105);
                $this->fpdf->MultiCell(40,5,"Cours : ",'','L',false);
        
                $this->fpdf->SetFont('helvetica','',10);
                $this->fpdf->SetXY(100,105);
                $this->fpdf->MultiCell(60,5,$f->dvsval,'','L',false);
        
                //   TJB
                $this->fpdf->SetFont('helvetica','B',10);
                $this->fpdf->SetXY(80,110);
                $this->fpdf->MultiCell(40,5,"T.J.B. : ",'','L',false);
        
                $this->fpdf->SetFont('helvetica','',10);
                $this->fpdf->SetXY(100,110);
                $this->fpdf->MultiCell(60,5,$f->navtjb,'','L',false);
                            //VOLUME
                $this->fpdf->SetFont('helvetica','B',10);
                $this->fpdf->SetXY(150,110);
                $this->fpdf->MultiCell(40,5,"Volume : ",'','L',false);
        
                $this->fpdf->SetFont('helvetica','',10);
                $this->fpdf->SetXY(180,110);
                $this->fpdf->MultiCell(50,5,$f->navvol,'','L',false);
            }
            else {
                //   BL
                $this->fpdf->SetFont('helvetica','B',10);
                $this->fpdf->SetXY(80,105);
                $this->fpdf->MultiCell(40,5,utf8_decode("N° BL : "),'','L',false);
        
                $this->fpdf->SetFont('helvetica','',10);
                $this->fpdf->SetXY(100,105);
                $this->fpdf->MultiCell(60,5,$f->cnsbld,'','L',false);


            }
        
                    //N° TAXATION
                $this->fpdf->SetFont('helvetica','B',10);
                $this->fpdf->SetXY(10,105);
                $this->fpdf->MultiCell(40,5,utf8_decode("ELEMENT N° : "),'','L',false);
        
                $this->fpdf->SetFont('helvetica','',10);
                $this->fpdf->SetXY(50,105);
                $this->fpdf->MultiCell(30,5,$f->taxnum,'','L',false);

                    //Navire

                $this->fpdf->SetFont('helvetica','B',10);
                $this->fpdf->SetXY(80,100);
                $this->fpdf->MultiCell(40,5,"Navire : ",'','L',false);

                $this->fpdf->SetFont('helvetica','',10);
                $this->fpdf->SetXY(100,100);
                $this->fpdf->MultiCell(60,5,$f->navnom,'','L',false);


                //Date Arrivée

                $this->fpdf->SetFont('helvetica','B',10);
                $this->fpdf->SetXY(150,100);
                $this->fpdf->MultiCell(40,5,utf8_decode("Date Arrivée : "),'','L',false);

                $this->fpdf->SetFont('helvetica','',10);
                $this->fpdf->SetXY(180,100);
                $this->fpdf->MultiCell(50,5,date_format(date_create($f->escdar), 'd-m-Y'),'','L',false);

                //Date Solde

                $this->fpdf->SetFont('helvetica','B',10);
                $this->fpdf->SetXY(150,105);
                $this->fpdf->MultiCell(40,5,"Date Solde : ",'','L',false);

                $this->fpdf->SetFont('helvetica','',10);
                $this->fpdf->SetXY(180,105);
                $this->fpdf->MultiCell(50,5,date_format(date_create($f->facdat), 'd-m-Y'),'','L',false);

                $position_detail = 128;
                $position_entete=120;
                //tableau prestations
                $this->fpdf->SetDrawColor(183); // Couleur du fond RVB
                $this->fpdf->SetFillColor(221); // Couleur des filets RVB
                $this->fpdf->SetTextColor(0); // Couleur du texte noir
                $this->fpdf->SetY($position_entete);
                // position de colonne 1 (10mm à gauche)    
                $this->fpdf->SetX(10);
                $this->fpdf->Cell(20,5,'Code',1,0,'C',1);  // 60 >largeur colonne, 8 >hauteur colonne
                // position de la colonne 2 (70 = 10+60)
                $this->fpdf->SetX(30); 
                $this->fpdf->Cell(60,5,'Libelle Prestation',1,0,'C',1);
                // position de la colonne 3 (130 = 70+60)
                $this->fpdf->SetX(90); 
                $this->fpdf->Cell(20,5,'Quant',1,0,'C',1);
            
                $this->fpdf->SetX(110); 
                $this->fpdf->Cell(20,5,'Unite',1,0,'C',1);
            
                $this->fpdf->SetX(130); 
                $this->fpdf->Cell(20,5,'P.U.',1,0,'C',1);
            
                $this->fpdf->SetX(150); 
                $this->fpdf->Cell(50,5,'Montant',1,0,'C',1);
            
                $this->fpdf->Ln(); // Retour à la ligne



                foreach($fdetail as $cle =>$valeur){

                    if ($f->domcod=='DCAP' or $f->domcod=='DREM'){
                        $this->fpdf->SetY($position_detail);
                        $this->fpdf->SetX(150); 
                       // $this->MultiCell(50,8,utf8_decode(number_format($valeur['DFAMTN'], 2, ',', ' ')),1,'C');
                        $this->fpdf->MultiCell(25,5,number_format($valeur['dfamntus'], 2, ',', ' '),1,'C');
            
                        $this->fpdf->SetY($position_detail);
                        $this->fpdf->SetX(175); 
                       // $this->MultiCell(50,8,utf8_decode(number_format($valeur['DFAMTN'], 2, ',', ' ')),1,'C');
                        $this->fpdf->MultiCell(25,5,number_format($valeur['dfamnt'], 2, ',', ' '),1,'C');
            
                    }
                    else{
                        
                        $this->fpdf->SetY($position_detail);
                        $this->fpdf->SetX(150); 
                        //$this->MultiCell(50,5,number_format($valeur['DFAMTN'], 2, ',', ' '),1,'C');
                        $this->fpdf->MultiCell(50,5,number_format($valeur['dfamnt'], 2, ',', ' '),1,'C');
            
                    }
                   // position abcisse de la colonne 1 (10mm du bord)
                    $this->fpdf->SetY($position_detail);
                    $this->fpdf->SetX(10);
                    $this->fpdf->MultiCell(20,5,utf8_decode($valeur['prscod']),1,'C');
            
                    $this->fpdf->SetY($position_detail);
                    $this->fpdf->SetX(30); 
                    $this->fpdf->MultiCell(60,5,utf8_decode(rtrim($valeur['prslib'])),1,'L');
            
                    $this->fpdf->SetY($position_detail);
                    $this->fpdf->SetX(90); 
                    $this->fpdf->MultiCell(20,5,utf8_decode($valeur['dfaqte']),1,'C');
            
                    $this->fpdf->SetY($position_detail);
                    $this->fpdf->SetX(110); 
                    $this->fpdf->MultiCell(20,5,utf8_decode($valeur['dfadur']),1,'C');
            
                    $this->fpdf->SetY($position_detail);
                    $this->fpdf->SetX(130); 
                    //$this->MultiCell(20,5,number_format(floatval($valeur['DFAPUN']), 4, ',', ' '),1,'C');
                    $this->fpdf->MultiCell(20,5,number_format($valeur['dfapun'], 2, ',', ' '),1,'C');
            
                    $position_detail += 5;
        
                }


                /*$this->fpdf->SetDrawColor(0,0,0);  
                $this->fpdf->SetLineWidth(0.2);   
                $this->fpdf->Rect(5,200,200,80, "D");
            
                $this->fpdf->Rect(5,200,150,80, "D");
                $this->fpdf->Rect(5,220,110,60, "D");*/

                $this->fpdf->SetDrawColor(0,0,0);  
                $this->fpdf->SetLineWidth(0.2);   
                $this->fpdf->Rect(5,225,200,60, "D");
            
                $this->fpdf->Rect(5,225,150,60, "D");
                $this->fpdf->Rect(5,245,110,40, "D");


                if ($f->mrgcod=='TERME'){
        
                    //Cachet et Signature
                    $this->fpdf->SetFont('Arial','',10);
                    $this->fpdf->SetXY(20,245);
                    $this->fpdf->MultiCell(70,5,'Certifie Conforme','','C',false);
            
                    $this->fpdf->SetFont('Arial','',10);
                    $this->fpdf->SetXY(20,250);
                    $this->fpdf->MultiCell(70,5,'Le Chef de Service Facturation','','C',false);
            
                    $this->fpdf->SetFont('Arial','',10);
                    $this->fpdf->SetXY(20,265);
                    $this->fpdf->MultiCell(50,5,'Cachet et Signature','','C',false);
                    
                    $this->fpdf->SetFont('Arial','',10);
                    $this->fpdf->SetXY(20,270);
                    $this->fpdf->MultiCell(50,5,'Etablie Par:','','L',false);
                }
            
                else{
            
                            //Cachet et Signature
                    $this->fpdf->SetFont('Arial','',10);
                    $this->fpdf->SetXY(20,255);
                    $this->fpdf->MultiCell(50,5,'LE CAISSIER','','L',false);
            
                    $this->fpdf->SetFont('Arial','',10);
                    $this->fpdf->SetXY(60,245);
                    $this->fpdf->MultiCell(50,5,'Certifie Conforme','','C',false);
            
                    $this->fpdf->SetFont('Arial','',10);
                    $this->fpdf->SetXY(60,255);
                    $this->fpdf->MultiCell(50,5,'LE FACTURIER','','C',false);
            
                    /*        //QR CODE
                    $lien='https://www.portalger.com.dz'; // Vous pouvez modifier le lien selon vos besoins
                    QRcode::png($lien, 'image-qrcode.png'); // On crée notre QR Code
                    $this->Image('image-qrcode.png',70,240,30);*/
            
                    $this->fpdf->SetFont('Arial','',10);
                    $this->fpdf->SetXY(20,260);
                    $this->fpdf->MultiCell(50,5,'Cachet et Signature','','L',false);

                    $this->fpdf->SetFont('Arial','',10);
                    $this->fpdf->SetXY(70,260);
                    $this->fpdf->MultiCell(50,5,'Cachet et Signature','','L',false);
            
                }


                $this->fpdf->SetFont('Arial','B',10);
                $this->fpdf->SetXY(10,227);
                $this->fpdf->MultiCell(100,5,utf8_decode('Arrêté la présente facture à la somme de:'),'','L',false);
                $this->fpdf->SetFont('Arial','B',10);
                $this->fpdf->SetXY(115,232);
                $this->fpdf->MultiCell(40,5,'FRAIS D\'IMPRESSION','','R',false);
                $this->fpdf->SetFont('Arial','B',10);
                $this->fpdf->SetXY(115,240);
                $this->fpdf->MultiCell(40,5,'TOTAL H.T.','','R',false);
                $this->fpdf->SetFont('Arial','B',10);
                $this->fpdf->SetXY(115,250);
                $this->fpdf->MultiCell(40,5,'TVA.','','R',false);
                $this->fpdf->SetFont('Arial','B',10);
                $this->fpdf->SetXY(115,260);
                $this->fpdf->MultiCell(40,5,'TIMBRE','','R',false);
                $this->fpdf->SetFont('Arial','B',10);
                $this->fpdf->SetXY(115,270);
                $this->fpdf->MultiCell(40,5,'TOTAL TTC.','','R',false);




                if ($f->domcod=='DCAP' or $f->domcod=='DREM'){
                    //FIX
                    $this->fpdf->SetFont('Arial','',11);
                    $this->fpdf->SetXY(160,232);
                    //$this->MultiCell(50,5,number_format($f->FacTtc, 2, ',', ' '),'','C',false);
                    $this->fpdf->MultiCell(25,5,$f->facfixus,'','C',false);
            
                        //HT
                    $this->fpdf->SetFont('Arial','',11);
                    $this->fpdf->SetXY(160,240);
                    //$this->MultiCell(50,5,number_format($f->FacTtc, 2, ',', ' '),'','C',false);
                    $this->fpdf->MultiCell(25,5,number_format($f->facmntus, 2, ',', ' '),'','C',false);
                    //TVA
                    $this->fpdf->SetFont('Arial','',11);
                    $this->fpdf->SetXY(160,250);
                    //$this->MultiCell(50,5,number_format($f->FacTtc, 2, ',', ' '),'','C',false);
                    $this->fpdf->MultiCell(25,5,number_format($f->factvaus, 2, ',', ' '),'','C',false);
                        //  TTC
                    $this->fpdf->SetFont('Arial','',11);
                    $this->fpdf->SetXY(160,270);
                    //$this->MultiCell(50,5,number_format($f->FacTtc, 2, ',', ' '),'','C',false);
                    $this->fpdf->MultiCell(25,5,number_format($f->facttcus, 2, ',', ' '),'','C',false);
                }

                $lettre=new ChiffresEnLettres();
                $this->fpdf->SetFont('Arial','',10);
                $this->fpdf->SetXY(10,232);
                $this->fpdf->MultiCell(100,5,$lettre->get_conversion($f->facttc),'','L',false);
                
                
            
                $this->fpdf->SetFont('Arial','',11);
                $this->fpdf->SetXY(180,232);
                //$this->MultiCell(50,5,number_format($f->FacTtc, 2, ',', ' '),'','C',false);
                $this->fpdf->MultiCell(25,5,number_format($f->facfix, 2, ',', ' '),'','C',false);
                
            
            
                $this->fpdf->SetFont('Arial','',11);
                $this->fpdf->SetXY(180,240);
                //$this->MultiCell(25,5,number_format($f->FacMtn, 2, ',', ' '),'','C',false);
                $this->fpdf->MultiCell(25,5,number_format($f->facmnt, 2, ',', ' '),'','C',false);
            
                //TIMBRE
            
                $this->fpdf->SetFont('Arial','',11);
                $this->fpdf->SetXY(180,260);
                //$this->MultiCell(50,5,number_format($f->FacTtc, 2, ',', ' '),'','C',false);
                $this->fpdf->MultiCell(25,5,number_format($f->factmb, 2, ',', ' '),'','C',false);
            
            
                $this->fpdf->SetFont('Arial','',11);
                $this->fpdf->SetXY(180,250);
                //$this->MultiCell(50,5,number_format($f->FacTtc, 2, ',', ' '),'','C',false);
                $this->fpdf->MultiCell(25,5,number_format($f->factva, 2, ',', ' '),'','C',false);
            
            
                $this->fpdf->SetFont('Arial','',11);
                $this->fpdf->SetXY(180,270);
                //$this->MultiCell(50,5,number_format($f->FacTtc, 2, ',', ' '),'','C',false);
                $this->fpdf->MultiCell(25,5,number_format($f->facttc, 2, ',', ' '),'','C',false);


        return $this->fpdf->Output();
        //exit;
    }

}
