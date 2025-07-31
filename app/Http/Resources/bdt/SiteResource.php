<?php

namespace App\Http\Resources\bdt;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'Code_Site' => $this->Code_Site,
            'CodeWilaya' => $this->wilaya->CodeWilaya,
            'nom_wilaya' => $this->wilaya->nom_wilaya,
            'CodeCommune' => $this->commune->ccom,
            'commune' => $this->commune->commune,
            'KeySite' => $this->Code_Affaire.'-'.$this->Code_Site,
            'LabelSite' => $this->commune->commune.' WILAYA de : '.$this->wilaya->nom_wilaya,

        ];
    }
}
