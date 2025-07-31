<?php

namespace App\Http\Resources\Essais;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceptionsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'NumCommande' => $this->NumCommande,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'NumSerie' => $this->NumSerie,
            'DatePrelevement' => $this->DatePrelevement,
            'epr_id' => $this->epr_id,
            'DateEssaiPrevisionnelle' => $this->DateEssaiPrevisionnelle,
            'DatePrelevement' => $this->DatePrelevement,
            'DateReception' => $this->DateReception,
            'Age' => $this->Age,
            'Observations' => $this->Observations,
            'typeIntervention' => $this?->commande?->intervention?->codeTypeIntervention,
            'operateur' => $this?->planification?->user,
            'IsSpecialDay' => $this?->IsSpecialDay,
            'typeEchantillon' => $this?->typeEchantillon,
            'Nom_DR' => $this->commande?->Nom_DR,
            'Structure' => $this->commande?->structuree?->nom_ag,
            'Nom_DR_Laboratoire' => $this->commande?->Laboratoire?->agence?->Nom_DR,
            'Structure_Laboratoire' => $this->commande?->Laboratoire?->agence?->nom_ag,
        ];
    }
}
