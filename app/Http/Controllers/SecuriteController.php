<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Profil;
use App\Models\Privilege;
use App\Models\VoletApp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
class SecuriteController extends Controller
{
    public function getProfil($p)
    {
        if ($p == 'G') {
            $securites = Profil::all();
        } elseif ($p == 'L') {
            $securites = Profil::where('limitation', '!=', 'G')
                ->get();
        } elseif ($p == 'P') {
            $securites = Profil::where('limitation', 'P')->get();
        }
        return $securites;
    }

    public function index()
    {
        $privilege = Auth::user()->scopePrivileges('securites');

        $profil_privilege = Profil::where('code', Auth::user()->privilege)->first();
        // $auth= Auth::user();
        // $privilege = $auth->scopePrivileges('securites');
        $profils = DB::table('p_profils')
        ->whereNot('libelle', 'like', '%' . 'RDEX' . '%');
        if ($privilege->visibilite == "R") {
            $profils=$profils->where(function ($query)  {
                $query->where('limitation','R')
                ->orwhere('limitation','L')
                ->orwhere('limitation','P');
            });
        } elseif ($privilege->visibilite == "L") {
            $profils=$profils->where(function ($query)  {
                $query->where('limitation','L')
                ->orwhere('limitation','P');
            });
        } elseif ($privilege->visibilite == "P") {
            $profils=$profils->where('limitation','P');
        }
        $profils=$profils->get();
        $fonctions = DB::table('Bfonction')->orderBy('LibelleFct', 'asc')->get();
        // $securites=$this->getProfil($p);

        return response()->json(['profil_privilege' => $profil_privilege, 'profils' => $profils, 'fonctions' => $fonctions], 200);
    }

    public function search($search)
    {
        // return response()->json(['profils' => '33333'], 200);
        // $privilege = Auth::user()->scopePrivileges('securites');

        $profils = DB::table('p_profils')
        ->whereNot('libelle', 'like', '%' . 'RDEX' . '%');
        if ($search <> 'null') {
            $profils =  $profils->where(function ($query) use ($search) {
                $query->where('code', 'like', '%' . $search . '%')
                ->orwhere('libelle', 'like', '%' . $search . '%')
                ->orwhere('description', 'like', '%' . $search . '%')
                ->orwhere('role', 'like', '%' . $search . '%')
                ->orwhere('limitation', 'like', '%' . $search . '%')
                // ->orwhere('fonction', 'like', '%' . $search . '%')
                ;
            });
        }
        // if ($privilege->visibilite == "L") {
        //     $profils=$profils->where(function ($query)  {
        //         $query->where('limitation','L')
        //         ->orwhere('limitation','P');
        //     });
        // } elseif ($privilege->visibilite == "P") {
        //     $profils=$profils->where('limitation','P');
        // }
        $profils=$profils->get();


        return response()->json(['profils' => $profils], 200);


    }

    public function searchprivilege($search, $profil_code)
    {

        $privileges = DB::table('p_privileges')->where('profil_code', $profil_code);

        if ($search <> 'null') {
            $privileges =  $privileges->where(function ($query) use ($search) {
                $query->where('profil_code', 'like', '%' . $search . '%')
                    ->orwhere('module', 'like', '%' . $search . '%')
                    ->orwhere('volet', 'like', '%' . $search . '%')
                    ->orwhere('description', 'like', '%' . $search . '%')
                    ->orwhere('role', 'like', '%' . $search . '%')
                    ->orwhere('consultation', 'like', '%' . $search . '%')
                    ->orwhere('modification', 'like', '%' . $search . '%')
                    ->orwhere('insertion', 'like', '%' . $search . '%')
                    ->orwhere('suppression', 'like', '%' . $search . '%')
                    ->orwhere('visibilite', 'like', '%' . $search . '%');
            });
        }

        $privileges =  $privileges->get();

        return response()->json(['privileges' => $privileges], 200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:p_profils,code',
            'libelle' => 'required|unique:p_profils,libelle',
            'limitation' => 'required',
            'role' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data['code'] = $request->code;
        $data['libelle'] = $request->libelle;
        $data['description'] = $request->description;
        // $data['fonction'] = $request->fonction;
        $data['limitation'] = $request->limitation;
        $data['role'] = $request->role;
        DB::table('p_profils')->insert($data);

        $voletsApp=VoletApp::all();
        foreach($voletsApp as $voletApp){
                  $newPrivilege= new Privilege;
                  $newPrivilege->profil_code =$request->code;
                  $newPrivilege->volet_app =$voletApp->volet;
                  $newPrivilege->module_app =$voletApp->module;
                  $newPrivilege->description =$voletApp->description;
                  $newPrivilege->consultation =1;
                  $newPrivilege->insertion =0;
                  $newPrivilege->modification =0;
                  $newPrivilege->suppression =0;
                  $newPrivilege->visibilite =$request->limitation;
                  $newPrivilege->role = $request->role;
                  $newPrivilege->save();
        }

        return response()->json(['message' => 'Le profil a été enregistré'], 200);
    }

    public function destroy($code)
    {
        $usersWithProfile = DB::table('users')->where('privilege', $code)->exists();

        if ($usersWithProfile) {
            return response()->json([
                'message' => 'Impossible de supprimer ce profil. Veuillez d\'abord modifier le profil des utilisateurs concernés.'
            ], 200);
        }

        Profil::where('code', $code)->delete();
        Privilege::where('profil_code', $code)->delete();

        return response()->json([
            'message' => 'Le profil a été supprimé avec succès.'
        ], 200);
    }

    public function privilegeIndex($profil_code)
    {

        $privileges = Privilege::where([
            'profil_code' => $profil_code,
        ])->get();

        $profil_privilege = Profil::where('code', $profil_code)->first();

        return response()->json(['profil_privilege' => $profil_privilege, 'privileges' => $privileges], 200);
    }

    public function privilegeUpdate(Request $request, $profil_code)
    {


        foreach ($request->id as $key => $val) {

            DB::table('p_privileges')->whereId($key)->update([
                'role' => $val['role'],
                'consultation' => $val['consultation'] == '1' ? true : false,
                'modification' => $val['modification'] == '1' ? true : false,
                'insertion' => $val['insertion'] == '1' ? true : false,
                'suppression' => $val['suppression'] == '1' ? true : false,
                'visibilite' => $val['visibilite'],
            ]);
        }

        return response()->json(['message' => 'Les privilèges ont été modifiés'], 200);
    }
}
