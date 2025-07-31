<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
class CourrierDiversResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'Code_Affaire' => $this->Code_Affaire,
            'Code_Site' => $this->Code_site,
            'refCourrier' => $this->RefCourrier ,
            'refNumeroCD' => $this->RefNumeroCD ,
            'dateCourrier' => Carbon::parse($this->datecourrier)->toDateString(),
            'objet'=> $this->Objet ,
            'isValide'=>$this->CourrierValide ,
            'etat'=> $this->etat ,
            // 'redacteur'=> $this->ingénieur_redacteur ,
            'ingénieur_redacteur'=> $this->ingénieur_redacteur ,
            'redacteur' => $this->getRedacteur


        ];
    }
}
