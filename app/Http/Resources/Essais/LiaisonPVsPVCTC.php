<?php

namespace App\Http\Resources\Essais;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class LiaisonPVsPVCTC extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'num_pv_ctc' => $this->NumPvEssai,
            'bloc_ctc' => $this->Bloc,
            'Code_Affaire' => $this->Code_Affaire,
            'Code_Site' => $this->Code_Site,
            'IntituleAffaire' => $this->Affaire?->IntituleAffaire,
            'date_prelevement' => $this->DatePrelevement,
            'date_ecrasement' => $this->DateEcrasement,
            'elements_ouvrage_ctc' => DB::table('t_essais_elem_ouvrages')
                ->join('b_pgr_liste_famille', 'b_pgr_liste_famille.id', '=', 't_essais_elem_ouvrages.elem_famille')
                ->join('t_essais_pv_elements_ouvrage', 't_essais_pv_elements_ouvrage.elem_id', '=', 't_essais_elem_ouvrages.elem_id')
                ->where('t_essais_pv_elements_ouvrage.pe_id', $this->NumIntervention)
                ->select('t_essais_elem_ouvrages.*', 'b_pgr_liste_famille.*', 't_essais_pv_elements_ouvrage.*')
                ->get(),
            'echantillions_ctc' => $this->echantillions,
        ];
    }
}
