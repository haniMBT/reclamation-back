<?php

namespace App\Http\Resources\Essais;

use App\Models\espaceclient\Buser;
use App\Models\espaceclient\Tsites;
use App\Models\Essais\EblocEXE;
use App\Models\Essais\ElemOuvrages;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterventionSelectResource extends JsonResource
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
            'age1' => $this->age1,
            'age2' => $this->age2,
            'age3' => $this->age3,
            'age4' => $this->age4,
            'age5' => $this->age5,
            'NumCommande' => $this->NumCommande,
            'bloc' => $this->Bloc,
            'numero' => $this->numero,
            'IntituleAffaire' => $this->Affaire->IntituleAffaire,
            'Code_Affaire' => $this->Code_Affaire,
            'Code_Site' => $this->Code_Site,
            'chargedaffaire' => Buser::where('Matricule', $this->Affaire->chargedaffaire)
                ->selectRaw("CONCAT(Matricule, '-', Nom, ' ', Prénom) as concatenatedString")
                ->first()->concatenatedString ?? null,
            'TEntreRealSite' => $this->Affaire->EntrepriseByCodeSite($this->Code_Site),
            'elementOuvrages' => ElemOuvrages::where('elem_affaire', $this->Code_Affaire)
                ->where('elem_site', $this->Code_Site)
                // ->where('elem_bloc', $this->Bloc)
                ->get(),
            'Localisation' => $this->Localisation,
            'PartieOuvrage' => $this->PartieOuvrage,
            'blocs' => EblocEXE::where('Code_Affaire', $this->Code_Affaire ?? null)
            ->where('Code_Site', $this->Code_Site ?? null)
            ->get()
            ->map(function ($row) {
                return [
                    'value' => $row->BlocOrigine,
                    'label' => $row->BlocOrigine,
                ];
            }),
            'catChantier' => Tsites::where('Code_Affaire', $this->Code_Affaire)->where('Code_Site', $this->Code_Site)->first()?->cat_chantier ?? null,
            'partie_ouvrage' => $this->partie_ouvrage != null ? $this->partie_ouvrage?->designation : ''
            // 'pe_nbre_prv' => $this->series->count(),
            // 'Forme' => $this->series->pluck('TypeEprouvette')->toArray(),
            // 'NbrEchantillion' => $this->series->pluck('NbrEchantillion')->toArray(),
            // 'Age' => $this->series->pluck('Age')->toArray(),
            // 'eprouvettes' => $this->series->pluck('eprouvettes')->toArray(),
        ];
    }
}
