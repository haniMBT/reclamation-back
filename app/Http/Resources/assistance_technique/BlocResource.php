<?php

namespace App\Http\Resources\assistance_technique;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlocResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'CodeBloc' => $this->Code_Affaire.'-'.$this->Code_site.'-'.$this->Code_Bloc,
        ];
    }
}
