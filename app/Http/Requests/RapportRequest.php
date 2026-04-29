<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RapportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    // public function authorize()
    // {
    //     return false;
    // }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
      
        if ($this->etape == 1) {
            
            $data = [
                "type_rapport" => "required",
                "projet_id" => "required",
                "site_id" => "required",
            ];
            if ($this->m2_CHECK) {
                // $data["description_hypothese_m2.*"] = "required";
                $data["description_sommaire_m2"] = "required";
                $data["donne_particulieres_m2"] = "required";
                $data["autre_documents_m2"] = "required";
                // $data["caractere_electrique_m2"] = "required";
                // $data["factur_dutilisation_m2"] = "required";
                // $data["facteur_simultaneite_m2"] = "required";
                // $data["chute_tension_m2"] = "required";
                $data["essais_informations_m2"] = "required";
                // $data["autre_m2"] = "required";
                if ($this->type_rapport == 'ADEX') {
                    $data["observation_elements_m2"] = "required";
                }
                // $data["check_piece_graphiques_m2.*"] = "required";
                // $data["check_piece_ecrites_m2.*"] = "required";
                // $data["bloc_check_m2.*"] = "required";
            }
            if ($this->m3_CHECK) {
                // $data["description_hypothese_m3.*"] = "required";
                $data["description_sommaire_m3"] = "required";
                $data["donne_particulieres_m3"] = "required";
                $data["autre_documents_m3"] = "required";
                // $data["debit_debase_m3"] = "required";
                // $data["simultanite_m3"] = "required";
                // $data["evacuations_m3"] = "required";
                // $data["eau_froide_sanitaire_m3"] = "required";
                // $data["eau_chaude_sanitaire_m3"] = "required";
                // $data["conduit_defumee_m3"] = "required";
                $data["essais_informations_m3"] = "required";
                if ($this->type_rapport == 'ADEX') {
                    $data["observation_elements_m3"] = "required";
                }
            }
            if ($this->m4_CHECK) {
                // $data["description_hypothese_m4.*"] = "required";
                $data["description_sommaire_m4"] = "required";
                $data["donne_particulieres_m4"] = "required";
                $data["autre_documents_m4"] = "required";
                // $data["condition_base_hiver_m4"] = "required";
                // $data["condition_base_ete_m4"] = "required";
                $data["essais_informations_m4"] = "required";
                if ($this->type_rapport == 'ADEX') {
                    $data["observation_elements_m4"] = "required";
                }
            }
        }
       
        if ($this->etape ==2) {
            $data = [
                // "type_rapport" => "required",
                "projet_id" => "required",
                "site_id" => "required",
                "rapport_id" => "required"
            ];
            if ($this->m2_CHECK <> null) {
                // $data["check_piece_graphiques_m2"] = "required";
                // $data["check_piece_ecrites_m2"] = "required";
                $data["bloc_check_m2"] = "required";
            }
            if ($this->m3_CHECK <> null) {
                // $data["check_piece_graphiques_m3"] = "required";
                // $data["check_piece_ecrites_m3"] = "required";
                $data["bloc_check_m3"] = "required";
            }
            if ($this->m4_CHECK <> null) {
                // $data["check_piece_graphiques_m4"] = "required";
                // $data["check_piece_ecrites_m4"] = "required";
                $data["bloc_check_m4"] = "required";
            }
        }
        if ($this->etape == 3) {
            $data = [
                // "type_rapport" => "required",
                "projet_id" => "required",
                "site_id" => "required",
                "rapport_id" => "required"
            ];
            if ($this->m2_CHECK <> null) {

                // if ($this->type_rapport == 'ADEX') {
                //     $data["piece_ecrite_examiné_check_m2"] = "required";
                //     $data["piece_graphique_examiné_check_m2"] = "required";
                // }elseif($this->type_rapport == 'RICT'){
                //     $data["piece_ecrite_consulté_check_m2"] = "required";
                //     $data["piece_graphique_consulté_check_m2"] = "required";
                // }
            }
            if ($this->m3_CHECK <> null) {

                // if ($this->type_rapport == 'ADEX') {
                //     $data["piece_ecrite_examiné_check_m3"] = "required";
                //     $data["piece_graphique_examiné_check_m3"] = "required";
                // }elseif($this->type_rapport == 'RICT'){
                //     $data["piece_ecrite_consulté_check_m3"] = "required";
                //     $data["piece_graphique_consulté_check_m3"] = "required";
                // }
            }
            if ($this->m4_CHECK <> null) {
            //     if ($this->type_rapport == 'ADEX') {
            //         $data["piece_ecrite_examiné_check_m4"] = "required";
            //         $data["piece_graphique_examiné_check_m4"] = "required";
            //     }elseif($this->type_rapport == 'RICT'){
            //         $data["piece_ecrite_consulté_check_m4"] = "required";
            //         $data["piece_graphique_consulté_check_m4"] = "required";
            //     }
            }
        }
        if ($this->etape == 4) {
            $data = [
                // "type_rapport" => "required",
                "projet_id" => "required",
                "site_id" => "required",
                "rapport_id" => "required",
                // "conclustions_avis" => "required",
            ];
            if ($this->m2_CHECK <> null) {
                $data["revision_pieces_m2"] = "required_without_all:revision_ecrite_m2,revision_graphique_m2";
                $data["revision_ecrite_m2"] = "required_without_all:revision_pieces_m2,revision_graphique_m2";
                $data["revision_graphique_m2"] = "required_without_all:revision_pieces_m2,revision_ecrite_m2";

            }
            if ($this->m3_CHECK <> null) {
                $data["revision_pieces_m3"] = "required_without_all:revision_ecrite_m3,revision_graphique_m3";
                $data["revision_ecrite_m3"] = "required_without_all:revision_pieces_m3,revision_graphique_m3";
                $data["revision_graphique_m3"] = "required_without_all:revision_pieces_m3,revision_ecrite_m3";

            }
            if ($this->m4_CHECK <> null) {
                $data["revision_pieces_m4"] = "required_without_all:revision_ecrite_m4,revision_graphique_m4";
                $data["revision_ecrite_m4"] = "required_without_all:revision_pieces_m4,revision_graphique_m4";
                $data["revision_graphique_m4"] = "required_without_all:revision_pieces_m4,revision_ecrite_m4";
            }
        }
        if ($this->etape ==5) {
            $data = [
                // "type_rapport" => "required",
                "projet_id" => "required",
                "site_id" => "required",
                "rapport_id" => "required",
                // "conclustions_avis" => "required",
            ];
            if ($this->sous_etape =="conclusion") {
                $data["conclustions_avis"] = "required";
            }
            if ($this->m2_CHECK <> null) {
                if ($this->sous_etape =='ecrite_examiné') {
                    $data["piece_ecrite_examiné_check_m2"] = "required";
                    $data["references_reserve_piece_ecrite_m2"] = "required";
                    $data["avis_reserve_piece_ecrite_m2"] = "required";
                    $data["observations_reserve_piece_ecrite_m2"] = "required";
                } elseif ($this->sous_etape =='graphique_examiné') {
                    $data["piece_graphique_examiné_check_m2"] = "required";
                    $data["references_reserve_piece_graphique_m2"] = "required";
                    $data["avis_reserve_piece_graphique_m2"] = "required";
                    $data["observations_reserve_piece_graphique_m2"] = "required";
                }
                // dd($this->m2_CHECK);

            }
            if ($this->m3_CHECK <> null) {
                if ($this->sous_etape =='ecrite_examiné') {
                    $data["piece_ecrite_examiné_check_m3"] = "required";
                    $data["references_reserve_piece_ecrite_m3"] = "required";
                    $data["avis_reserve_piece_ecrite_m3"] = "required";
                    $data["observations_reserve_piece_ecrite_m3"] = "required";
                } elseif ($this->sous_etape =='graphique_examiné') {
                    $data["piece_graphique_examiné_check_m3"] = "required";
                    $data["references_reserve_piece_graphique_m3"] = "required";
                    $data["avis_reserve_piece_graphique_m3"] = "required";
                    $data["observations_reserve_piece_graphique_m3"] = "required";
                }
            }
            if ($this->m4_CHECK <> null) {
                if ($this->sous_etape =='ecrite_examiné') {
                    $data["piece_ecrite_examiné_check_m4"] = "required";
                    $data["references_reserve_piece_ecrite_m4"] = "required";
                    $data["avis_reserve_piece_ecrite_m4"] = "required";
                    $data["observations_reserve_piece_ecrite_m4"] = "required";
                } elseif ($this->sous_etape =='graphique_examiné') {
                    $data["piece_graphique_examiné_check_m4"] = "required";
                    $data["references_reserve_piece_graphique_m4"] = "required";
                    $data["avis_reserve_piece_graphique_m4"] = "required";
                    $data["observations_reserve_piece_graphique_m4"] = "required";
                }
            }
        }
        
        return $data;
    }
}
