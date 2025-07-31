<?php

namespace App\Http\Resources\Essais;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
                'typeIntervention' => $this->intervention?->LibelleTypeIntervention,
                'numero' => $this->numero,
                'localisation' => $this->Localisation,
                'description' =>  $this->Description,
                'CodeCommande' =>  $this->CodeCommande,
                'NumCommande' => $this->NumCommande,
                'DatePrelevement' =>  $this->Planification?->DatePrelevement ?? null,
                'id' => $this->Planification?->id ?? null,
                'etat' => $this->etat ?? null,
                'motif_programmation_ca' => $this->motif_programmation_ca ?? null,
        ];
    }
}
