<?php

namespace App\Http\Resources\Essais;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class CommandesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'CodeCommande' => $this->CodeCommande,
            'NumCommande' => $this->NumCommande,
            'AutresInfo' => $this->AutresInfo,
            'BesoinCommande' => $this->BesoinCommande,
            'DateCommande' => $this->DateCommande,
            'DelaiEssai' => $this->DelaiEssai,
            'Description' => $this->Description,
            'ElementAusculter' => $this->ElementAusculter,
            'Localisation' => $this->Localisation,
            'NbrCarotte' => $this->NbrCarotte,
            'NormeEssai' => $this->NormeEssai,
            'PartieOuvrage' => $this->PartieOuvrage,
            'TypeCommande' => $this->TypeCommande,
            'Code_Site' => $this->Code_Site,
            'NumLaboratoire' => $this->NumLaboratoire,
            'Nom_DR' => $this->Nom_DR,
            'Structure' => $this->Structure,
            'etat' => $this->etat,
            'essai' => $this->essai,
            'designations_autres' => $this->designations_autres,
            'validation_chargedaffaire' => $this->validation_chargedaffaire,
            'date_validation_chargedaffaire' => $this->date_validation_chargedaffaire,
            'validation_pm' => $this->validation_pm,
            'date_validation_pm' => $this->date_validation_pm,
            'rejet_pm' => $this->rejet_pm,
            'date_rejet_pm' => $this->date_rejet_pm,
            'motif_rejet_pm' => $this->motif_rejet_pm,
            'annulation_pm' => $this->annulation_pm,
            'date_annulation_pm' => $this->date_annulation_pm,
            'motif_annulation_pm' => $this->motif_annulation_pm,
            'Bloc' => $this->Bloc,
            'createdBy' => $this->createdBy,
            'numero' => $this->numero,
            'type_intervention' => $this->intervention?->codeTypeIntervention ?? null,
            'DatePlanificationPrev' => $this->Planification?->DatePrelevement ?? null,
            'Code_Affaire' => $this->getCodeAffaire(),
            'etateLab' => $this->Laboratoire?->etat,
            'motif_rejet_laboratoire_fixe' => $this->Laboratoire?->motif_rejet_laboratoire_fixe,
            'NomLaboratoire' => $this->Laboratoire?->NomLaboratoire,
            'partie_ouvrage' => $this->partie_ouvrage,
            'Agence' => $this->structuree?->nom_ag,
            'Nom_DR' => $this->Nom_DR,
        ];
    }

    private function getCodeAffaire()
    {
        $privilege = Auth::user()->scopePrivileges('commandes');
        if ($privilege->role == 'Labo_fixe_resp' || $privilege->role == 'Labo_fixe_agent') {
            return null;
        } else {
            return $this->Code_Affaire;
        }
    }
}
