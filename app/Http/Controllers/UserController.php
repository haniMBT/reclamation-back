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

        return response()->json(['utilisateurs' => $utilisateurs, 'profils' => $profils,'drs' => $drs,
        //   'fonctions' => $fonctions, 'str_id' => $request->str_id, 'dr_id' => $request->dr_id
        ], 200);
    }

     public function store(Request $request)
    {

        // return response()->json(['request' =>$request->all()],525);

        $validator = Validator::make($request->all(), [
            // 'Matricule' => 'required|digits:5|unique:Busers,Matricule',
            'Matricule' => 'required|max:5|min:5|unique:users,Matricule',
            'Nom' => 'required',
            'Prenom' => 'required',
            'direction' => 'required',
            'email' => 'email|required',
            // 'email' => $request->email ? 'email|nullable' : '',
            'password' => 'required',
            // 'Fonction' => 'required|max:3|min:3'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data['Matricule'] = $request->Matricule;
        $data['Nom'] = $request->Nom;
        $data['Prenom'] = $request->Prenom;
        $data['direction'] = $request->Nom_DR;
        // $data['Structure'] = $request->Structure;
        // $data['Fonction'] = $request->Fonction;
        $data['email'] = $request->email;
        $data['password'] = Hash::make($request->password);
        DB::table('users')->insert($data);

        return response()->json(['message' => 'L\'utilisateur a été enregistré.'], 200);
    }

    public function update(Request $request, $matricule)
    {

        // $validator = Validator::make($request->all(), [
        //     // 'email' => $request->email ? 'email|nullable' : '',
        // ]);

        // if ($validator->fails()) {
        //     return response()->json(['errors' => $validator->errors()], 422);
        // }

        $data = [
            // 'Fonction' => $request->Fonction,
            'privilege' => $request->privilege,
            'email' => $request->email,
            'direction' => $request->direction,
            // 'Structure' => $request->Structure
        ];

        if (isset($request->password))
            $data['password'] =  Hash::make($request->password);

        DB::table('users')->where('Matricule', $matricule)->update($data);

        return response()->json(['message' => 'L\'utilisateur a été modifié.'], 200);
    }

    // public function profilUpdate(Request $request)
    // {

    //     // return response()->json($request->all(),525);

    //     if ($request->hasFile('image')) {
    //         $image = $request->file('image');
    //         $fileName = Auth::user()->Matricule . '.' . $image->getClientOriginalExtension();

    //         //delete
    //         $filePath = '/public/profils/' . $fileName;
    //         if (Storage::exists($filePath)) {
    //             Storage::delete($filePath);
    //         }
    //         // //insert
    //         $image = $request->file('image');
    //         $fileName = Auth::user()->Matricule . '.' . $image->getClientOriginalExtension();
    //         $image->storeAs('/public/profils/', $fileName);
    //         $data['image'] = $fileName;

    //         // // Save the file name in the database
    //         DB::table('Busers')->where('Matricule', Auth::user()->Matricule)->update($data);
    //         $stateimage = asset('/storage/profils/' . $fileName);
    //         // return response()->json($stateimage,422);

    //     }
    //     if (empty(Auth::user()->email)) {

    //         $validator = Validator::make($request->all(), [
    //             'email' => $request->email ? 'email|nullable' : ''
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json(['errors' => $validator->errors()], 422);
    //         }

    //         $data['email'] = $request->email;
    //         DB::table('Busers')->where('Matricule', Auth::user()->Matricule)->update($data);
    //     } else {

    //         $validator = Validator::make($request->all(), [
    //             'email' => $request->email ? 'email|nullable' : '',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json(['errors' => $validator->errors()], 422);
    //         }

    //         $data['email'] = $request->email;
    //         DB::table('Busers')->where('Matricule', Auth::user()->Matricule)->update($data);
    //     }


    //     if (!empty($request->new_password) || !empty($request->old_password) || !empty($request->new_password_confirmation)) {


    //         $validator = Validator::make($request->all(), [
    //             'old_password' => 'required',
    //             'new_password' => 'required|confirmed',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json(['errors' => $validator->errors()], 422);
    //         }

    //         #Match The Old Password
    //         if (!Hash::check($request->old_password, auth()->user()->passwordPlatforme)) {
    //             return response()->json(["errors", "L'ancien mot de passe ne correspond pas!"], 500);
    //         }

    //         #Update the new Password
    //         DB::table('Busers')->where('Matricule', auth()->user()->Matricule)->update([
    //             'passwordPlatforme' => Hash::make($request->new_password)
    //         ]);
    //     }

    //     // return response()->json([auth()->user()->passwordPlatforme,auth()->user()->email]);
    //     if ($request->hasFile('image')) {
    //         return response()->json(['message' => 'Le profil a été modifié.', 'stateimage' => $stateimage], 200);
    //     } else {
    //         return response()->json(['message' => 'Le profil a été modifié.'], 200);
    //     }
    // }

    public function revokeProfile(Request $request, $Matricule)
    {
        DB::statement("UPDATE users SET privilege = null WHERE Matricule = '" . $Matricule . "'");
        return response()->json(['message' => 'Le profil à été supprimé !'], 200);
    }

    public function destroy($Matricule)
    {
        // return response()->json(['errors' => $Matricule], 200);
        User::where('Matricule', $Matricule)->delete();
        return response()->json(['message' => 'L\'utilisateur a été supprimé.'], 200);
    }
    // public function activation($Matricule)
    // {
    //     // return response()->json(['errors' => $Matricule], 200);
    //     $User = User::where('Matricule', $Matricule)->first();

    //     if($User->Actif == '0'){
    //         DB::table('Busers')->where('Matricule', $Matricule)->update(
    //             [
    //                 'Actif' => '1',
    //                 'Date_activation' => now()->format('Y-m-d'),
    //             ]
    //         );
    //         return response()->json(['message' => 'L\'utilisateur a été activé.'], 200);
    //     }else{
    //         DB::table('Busers')->where('Matricule', $Matricule)->update(
    //             [
    //                 'Actif' => '0',
    //                 'Date_activation' => now()->format('Y-m-d')
    //                 ]
    //             );
    //             return response()->json(['message' => 'L\'utilisateur a été désactivé.'], 200);
    //     }


    // }
    // public function new_password(Request $request)
    // {

    //         $validator = Validator::make($request->all(), [
    //             'new_password' => 'required|confirmed',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json(['errors' => $validator->errors()], 422);
    //         }

    //         #Update the new Password
    //         DB::table('Busers')->where('Matricule', auth()->user()->Matricule)->update([
    //             'passwordPlatforme' => Hash::make($request->new_password)
    //         ]);

    //         return response()->json(['message' => 'Votre mot de passe a été modifié.'], 200);

    // }
}
