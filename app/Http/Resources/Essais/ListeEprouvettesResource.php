<?php

namespace App\Http\Resources\Essais;

use App\Models\Affaire;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListeEprouvettesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'Affaire' => $this->Code_Affaire,
            'Site' => $this->Code_Site,
            'NumEchantillion' => $this->epr_entrp_ctc == 0 ? $this->epr_id : $this->epr_ref,
            'Prelevement' => $this->epr_entrp_ctc == 0 ? 'CTC' : 'Auto contrôle ETB',
            'Type' => $this->eprv_type,
            'DatePrelevement' => Carbon::parse($this->DatePrelevement)->format('d/m/Y'),
            'DateEcrasement' => Carbon::parse($this->DateEcrasement)->format('d/m/Y'),
            'DateEssaiPrevisionnelle' => Carbon::parse($this->DateEssaiPrevisionnelle)->format('d/m/Y'),
            'Age' => $this->Age,
            'Fci' => $this->epr_fci,
            'recu' => $this->recu,
            'ecrasement' => $this->ecrasement,
            'multisite' => Affaire::where('Code_Affaire', $this->Code_Affaire)->first()?->Multisite
        ];
    }
}
