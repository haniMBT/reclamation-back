<?php

namespace App\Http\Resources\Essais;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class CommandeResource extends JsonResource
{
    private $edit;
    public function __construct($resource, $edit)
    {

        parent::__construct($resource);
        $this->resource = $resource;

        $this->edit = $edit;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>NumLaboratoire
     */
    public function toArray(Request $request): array
    {
        return [
            'CodeCommande' => $this->CodeCommande,
            'NumCommande' => $this->NumCommande,
            'besoin' => $this->BesoinCommande,
            'typeCommande' => $this->TypeCommande,
            'agence' => $this->edit ?
                    ["value" => $this?->Laboratoire?->NumLaboratoire, "label" => $this?->Laboratoire?->NomLaboratoire] : $this?->Laboratoire?->NomLaboratoire,
            'typeIntervention' => $this->edit ?
                    ['label' => $this->intervention?->LibelleTypeIntervention, 'value' => $this->intervention?->codeTypeIntervention, 'inactive' => $this->intervention?->etat ? false : true] : $this->intervention?->LibelleTypeIntervention,
            'localisation' => $this->Localisation,
            'numero' => $this->numero,
            'delaiEssai' => $this->DelaiEssai,
            'bloc' =>  $this->edit ? ["value" => $this->Bloc, "label" => $this->Bloc] : $this->Bloc,
            'partieOuvrageDivers' => $this->edit ?
            ["value" => $this->partie_ouvrage->code, "label" => $this->partie_ouvrage->designation] : $this->partie_ouvrage->designation,
            'description' => $this->Description,
            'IntituleAffaire' => $this->getCodeAffaire(),
            'createdBy' => $this->createdBy,
            'etat' => $this->etat,
            'Code_Affaire' => $this->Code_Affaire,
            'Code_Site' => $this->Code_Site,
            'age1' => $this->age1,
            'age2' => $this->age2,
            'age3' => $this->age3,
            'age4' => $this->age4,
            'age5' => $this->age5,
            'NomLaboratoire' => $this?->Laboratoire?->NomLaboratoire,
            'etateLab' => $this->Laboratoire?->etat,
            'DatePlanificationPrev' => !empty($this->planification?->DatePrelevement) ? Carbon::parse($this->planification?->DatePrelevement)->format('d/m/Y'): '/',
            'MotifNonRealisationCommande' => $this->motif_non_realisation_commande,

        ];
    }
    private function getCodeAffaire()
    {
        $privilege = Auth::user()->scopePrivileges('commandes');
        if ($privilege->role == 'Labo_fixe_resp' || $privilege->role == 'Labo_fixe_agent') {
            return null;
        } else {
            return $this->Affaire->IntituleAffaire;
        }
    }
}
