<?php

namespace App\Http\Resources\bdt;

use App\Models\Affaire;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class FicheAlerteResource extends JsonResource
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
            'numero' => $this->numero,
            'titre' => $this->titre,
            'missions' => $this->missions,
            'mission' => $this->mission,
            'gros_oeuvres' => $this->gros_oeuvres,
            'description_probleme' => $this->description_probleme,
            'affaire' => $this->getAffaire(),
            'localite' => $this->getSite(),
            'entreprise' => $this->getEntreprise(),
            'states' => $this->getStates(),
        ];
    }

    private function getStates()
    {
        $array = [];
        if ($this->description_ca) {
            array_push($array, (object) ['key' => 'description_ca', 'val' =>  'Description du problème']);
        }
        if ($this->element_declenchant_ca) {
            array_push($array, (object) ['key' => 'element_declenchant_ca', 'val' => 'Éléments déclenchant le problème/ Causes probables']);
        }
        if ($this->references_reglementaire_ca) {
            array_push($array, (object) ['key' => 'references_reglementaire_ca', 'val' => 'Références réglementaires ou nomatives']);
        }
        if ($this->dispositions_mesure_ca) {
            array_push($array, (object) ['key' => 'dispositions_mesure_ca', 'val' => 'Dispositions et mesures d\'urgence']);
        }
        if ($this->dispositions_prendre_ca) {
            array_push($array, (object) ['key' => 'dispositions_prendre_ca', 'val' =>  'Dispositions à prendre']);
        }
        if ($this->extrait_dispositions_ca) {
            array_push($array, (object) ['key' => 'extrait_dispositions_ca', 'val' =>  'Extrait des dispositions normatives et reglementaires']);
        }
        if ($this->rappel_formulaires_ca) {
            array_push($array, (object) ['key' => 'rappel_formulaires_ca', 'val' =>  'RAPPEL DES FORMULAIRES DE L\'INSTRUCTION I.2-P1-CTR-1']);
        }
        return $array;
    }

    private function getAffaire()
    {
        if ($this->projet) {
            $affaire = Affaire::where('Code_Affaire', $this->projet)->first();
            return (object) [
                'Code_Affaire' => $affaire->Code_Affaire,
                'LabelAffaire' => $affaire->Code_Affaire . ' / ' . $affaire->IntituleAffaire
            ];
        }
    }

    private function getSite()
    {
        if ($this->localite) {
            $site = Site::where('Code_Affaire', explode('-', $this->localite)[0])->where('Code_Site', explode('-', $this->localite)[1])->first();
            return new SiteResource($site);
        }
    }

    private function getEntreprise()
    {
        if ($this->entreprise) {
            $entreprise_info = DB::table('BEntrepriseRealisation')
                ->where('BEntrepriseRealisation.code', $this->entreprise)->first();
            return (object)[
                'KeyEntreprise' => $this->entreprise,
                'LabelEntreprise' => $entreprise_info?->nom . ' ' . $entreprise_info?->adr . ($entreprise_info?->tel ? ' Tél : ' . $entreprise_info?->tel : '') . ($entreprise_info?->fax ? ' Fax : ' . $entreprise_info?->fax : ''),
            ];
        }
    }
}
