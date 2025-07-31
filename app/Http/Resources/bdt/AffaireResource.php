<?php

namespace App\Http\Resources\bdt;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffaireResource extends JsonResource
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
            'LabelAffaire' => $this->Code_Affaire . ' / ' . $this->IntituleAffaire
        ];
    }
}
