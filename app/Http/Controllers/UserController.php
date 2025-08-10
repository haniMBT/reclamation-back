<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;


class UserController extends Controller
{
    public function search(Request $request)
    {
        $privilege = Auth::user()->scopePrivileges('utilisateurs');
        $drs = DB::table('direction')->select('DIRECTION')->groupBy('DIRECTION')->get();

        $profils = DB::table('p_profils');
        // if ($privilege->visibilite == "L") {
        //     $profils=$profils->where(function ($query)  {
        //         $query->where('limitation','L')
        //         ->orwhere('limitation','P');
        //     });
        // } elseif ($privilege->visibilite == "P") {
        //     $profils=$profils->where('limitation','P');
        // }
        $profils=$profils->get();

        $utilisateurs = User::query();
        // leftJoin('direction', function ($join) {
        //     $join->on('direction.code_ag', '=', 'Busers.Structure');
        // })
        // ->leftJoin('Bfonction', 'Bfonction.CodeFnt', '=', 'Busers.Fonction');

        $actif=$request->actif;
        $search=$request->search;
        if ($search <> 'null') {
            // $utilisateurs =  $utilisateurs->where(function ($query) use ($search) {
            //     $query->where('Matricule', 'like', '%' . $search . '%')
            //     ->orwhere('Nom', 'like', '%' . $search . '%')
            //     ->orwhere('Prénom', 'like', '%' . $search . '%')
            //     ->orwhere('Busers.Nom_DR', 'like', '%' . $search . '%')
            //     ->orwhere('code_ag', 'like', '%' . $search . '%')
            //     ->orwhere('nom_ag', 'like', '%' . $search . '%')
            //     ->orwhere('Busers.email', 'like', '%' . $search . '%')
            //     ->orwhere('Busers.privilege', 'like', '%' . $search . '%')
            //     ->orwhere('Bfonction.LibelleFct','like','%'.$search.'%');
            // });
        }

        // if ($privilege->visibilite == "G") {
        //     if ($request->dr_id) {
        //         $utilisateurs = $utilisateurs->where("user.direction", $request->dr_id);
        //     }
        //     // if ($request->str_id) {
        //     //     $utilisateurs = $utilisateurs->where("Busers.Structure", $request->str_id);
        //     // }
        // }
        // // elseif ($privilege->visibilite == "R") {
        // //     if ($request->str_id) {
        // //         $utilisateurs = $utilisateurs->where("Busers.Structure", $request->str_id);
        // //     }
        // //     $utilisateurs = $utilisateurs->where("Busers.Nom_DR", Auth::user()->Nom_DR);
        // // }
        //  elseif ($privilege->visibilite == "L") {
        //     $utilisateurs = $utilisateurs->where("user.direction", Auth::user()->direction);

        // } elseif ($privilege->visibilite == "P") {
        //     $utilisateurs = $utilisateurs->where('Busers.id','=', Auth::user()->id) ;
        // }
        // if ($request->actif=='0') {
        //     $utilisateurs = $utilisateurs->where("Actif",0);
        // }elseif($request->actif=='1'){
        //     $utilisateurs = $utilisateurs->where(function ($query) use ($request) {
        //         $query->where('Actif','1')
        //         ->orwhere('Actif',null);
        //     });
        // }

        $utilisateurs =  $utilisateurs
        // ->select('Matricule', 'Nom', 'Prénom', 'Busers.Nom_DR', 'code_ag', 'nom_ag', 'privilege', 'Fonction', 'Busers.email', 'Structure', 'LibelleFct', 'Actif', 'Date_activation')
        ->get();
        // $fonctions = DB::table('Bfonction')->orderBy('LibelleFct', 'asc')->get();

        return response()->json(['utilisateurs' => $utilisateurs, 'profils' => $profils,
        //  'drs' => $drs, 'fonctions' => $fonctions, 'str_id' => $request->str_id, 'dr_id' => $request->dr_id
        ], 200);
    }
}
