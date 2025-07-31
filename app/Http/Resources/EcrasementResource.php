<?php

namespace App\Http\Resources;

use App\Models\Essais\EssaiPVEchantillon;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EcrasementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'NumEchantillion' => $this->epr_id,
            'typeIntervention' => $this->commande?->intervention?->codeTypeIntervention,
            'DateCoulage' => Carbon::parse($this->DateCoulage)->format('d/m/Y'),
            'DateEssaiPrevisionnelle' => $this->DateEssaiPrevisionnelle,
            'Age' => $this->Age,
            'Masse' => $this->epr_masse,
            'FormeEprouvette' => $this->TypeEprouvette->eprv_type,
            'ChargeRupture' => $this->epr_charge,
            'Section' => $this->epr_section,
            'MasseVolmuique' => $this->epr_masse,
            'RC_bars' => $this->epr_fci,
            'Hauteur' => $this->epr_hauteur,
            'Diametre' => $this->epr_cote,
            'planification' => $this->planification,
            'pv_essais' => EssaiPVEchantillon::where('NumCommande', $this->echantillion?->commande?->NumCommande)->first(),
            'non_ecrasable' => $this->non_ecrasable,
            'ecrasement' => $this->ecrasement,
            'NumCommande' => $this->NumCommande,
            'modes_rupture' => $this->modes_rupture,
            'modes_cure' => $this->modes_cure,
            'Motif_non_ecrasement' => $this->Motif_non_ecrasement,
        ];
    }
}
