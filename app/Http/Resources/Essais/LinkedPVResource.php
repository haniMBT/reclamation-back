<?php

namespace App\Http\Resources\Essais;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LinkedPVResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'date_ecrasement' => Carbon::parse($this->DateEcrasement)->format('d/m/Y'),
            'num_pv_entreprise' => $this->NumPvEntreprise,
            'num_sous_pv' => $this->num_sous_pv,
            'num_pv_ctc' => $this->NumPvCTC,
            'average_fci_entreprise' => $this->moyenne_fci_entreprise,
            'average_fci_ctc' => $this->moyenne_fci_ctc,
            'average_fci_diff' => $this->diff_moyenne_fci,
            'Bloc_entreprise' => $this->pv_entrerise?->Bloc ?? null,
            'pv_ctc' => $this->pv_ctc,
            'pv_entrerise' => $this->pv_entrerise,
        ];
    }
}
