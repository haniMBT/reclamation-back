<?php

namespace App\Http\Resources\Essais;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class LiaisonPVsPVEntreprise extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'num_pv_entreprise' => $this->num_pv,
            'num_sous_pv' => $this->num_sous_pv,
            'Code_Affaire' => $this->pv->Code_Affaire,
            'Code_Site' => $this->pv->Code_Site,
            'IntituleAffaire' => $this->pv->Affaire->IntituleAffaire,
            'bloc_entreprise' => $this->Bloc,
            'date_prelevement' => $this->DatePrelevement,
            'date_ecrasement' => $this->DateEcrasement,
            'elements_ouvrage_entreprise' => DB::table('t_essais_elem_ouvrages')->join('b_pgr_liste_famille', 'b_pgr_liste_famille.id' , '=', 't_essais_elem_ouvrages.elem_famille')->join('t_essais_pv_elements_ouvrage', 't_essais_pv_elements_ouvrage.elem_id', 't_essais_elem_ouvrages.elem_id')->where('t_essais_pv_elements_ouvrage.pe_id', $this->num_pv)->get(),
            'echantillions_entreprise' => $this->ecrasements,
        ];
    }
}
