<?php

namespace App\Http\Resources\Essais;

use App\Models\espaceclient\Buser;
use App\Models\Essais\Carriere;
use App\Models\Essais\CatChantier;
use App\Models\Essais\ClassBeton;
use App\Models\Essais\ModeProduction;
use App\Models\Essais\TypeAdditifs;
use App\Models\Essais\TypeAdjuvant;
use App\Models\Essais\TypeCiments;
use App\Models\Essais\TypeEprouvette;
use App\Models\GestionRisques\Affaire;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FormulationBetonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'Structure' => $this->structuree?->nom_ag,
            'Nom_DR' => $this->Nom_DR,
            'pvf_affaire' => $this->pvf_affaire,
            'pvf_reference' => $this->pvf_reference,
            'pvf_id' => $this->pvf_id,
            'EntrepriseRealisation' => DB::table('BentrepriseRealisation')->where('code',
            $this->pvf_entreprise)->first(),
            // 'maitre_ouvrage' => DB::table('Taffaire')
            // ->where('Taffaire.Code_Affaire', $this->pvf_affaire)
            // ->join('TSites', 'TSites.Code_Affaire', '=', 'Taffaire.Code_Affaire')
            // ->join('TmaitreOeuvreSite', 'TmaitreOeuvreSite.Code_Site', '=', 'TSites.Code_Site')
            // ->first(),
            'pvf_laboratoire' => $this->pvf_laboratoire,
            'Validation_labo' => $this->Validation_labo,
            'pvf_classe_bet' => ClassBeton::where('cb_id', $this->pvf_classe_bet)->first(),
        ];
    }
}