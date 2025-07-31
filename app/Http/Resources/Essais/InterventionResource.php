<?php

namespace App\Http\Resources\Essais;

use App\Models\espaceclient\BMaitre_ouvrage;
use App\Models\espaceclient\Buser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class InterventionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $chargeAffaireMatricule = DB::table('Taffaire')
            ->where('Code_Affaire', $this->Code_Affaire)
            ->value('chargedaffaire');

        $chargeAffaire = Buser::where('Matricule', $chargeAffaireMatricule)
            ->selectRaw("CONCAT(Nom, ' ', Nomjeunefille, ' ', Prénom) as concatenatedString")
            ->first();

        $maitreOuvrageCode = DB::table('Taffaire')
            ->where('Code_Affaire', $this->Code_Affaire)
            ->value('MaitreOuvrage');

        $MaitreOuvrage = BMaitre_ouvrage::where('code', $maitreOuvrageCode)
            ->first();
        return [
            'Structure' => $this->structuree?->nom_ag,
            'Nom_DR' => $this->Nom_DR,
            'Code_Affaire' => $this->Code_Affaire,
            'NumCommande' => $this->NumCommande,
            'pe_id' => $this->pe_id,
            'pe_date_pv' => $this->pe_date_pv,
            'EntrepriseRealisation' => DB::table('BentrepriseRealisation')->where(
                'code',
                $this->pe_entreprise
            )->first(),
            'maitre_ouvrage' => $MaitreOuvrage, 
            'bet' => DB::table('tbetsite')
                ->where('tbetsite.Code_Affaire', $this->Code_Affaire)
                ->first(),
            'chargedaffaire' => $chargeAffaire->concatenatedString ?? null,
            'Validation_labo' => $this->Validation_labo,
            'user_code' => $this->user_code,
            'Nom_DR_Laboratoire' => $this->commande?->Laboratoire?->agence?->Nom_DR,
            'Structure_Laboratoire' => $this->commande?->Laboratoire?->agence?->nom_ag,
            'TypeIntervention' => $this->commande?->TypeIntervention,
        ];
    }
}
