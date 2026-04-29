<?php

namespace App\Http\Controllers;

use App\Models\Direction;
use App\Models\Profil;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MainController extends Controller
{

    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'Email'  => 'required|string',
            'password'  => 'required|string'
        ]);


        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        $user   = User::where('Email', $request->Email)
            ->first();

        if (!$user) {
            return $this->sendErrorResponse('L\'utilisateur n\'est pas trouvé !', 404);
        }

        if ($user && Hash::check($request->password, $user->password)) {

            $tokenResult = $user->createToken('auth-token');
            $token = $tokenResult->plainTextToken;
            $tokenInstance = $tokenResult->accessToken;

            if ($tokenInstance) {
                $tokenInstance->expires_at = Carbon::now()->addMinutes(120);
                $tokenInstance->save();
            } else {
                return response()->json(['error' => 'Unable to set token expiration.'], 500);
            }

            // if ($request->password == '123456') {

            //     return response()->json([
            //         'message'       => 'Vous devez changer votre mot de passe pour continuer!',
            //         'access_token'  => $token,
            //         'token_type'    => 'Bearer'
            //     ]);
            // }
            return response()->json([
                'message'       => 'Login success',
                'access_token'  => $token,
                'token_type'    => 'Bearer',
            ]);
        }

        return $this->sendErrorResponse('Mot de passe incorrect', 404);
    }
    public function logout()
    {
        Auth::user()->tokens()->delete();
        return response()->json(['message' => 'Logout successfull']);
    }

    public function user(Request $request)
    {

        $user = $request->user()->toArray();

        $dr_id = $request->user()->Nom_DR;
         $data = [
            'user' => $user,
            'dr_id' => $dr_id,
        ];

        $privilegeProfil = DB::table('p_profils')->where('p_profils.code', Auth::user()->privilege)->first();
        if ($privilegeProfil->limitation == 'G') {
            $data['selectedDirection'] = null;
            $data['dr_id'] = null;
        }
        if ($privilegeProfil->limitation == 'P' || $privilegeProfil->limitation == 'L') {
            $data['selectedDirection'] = $dr_id;
        }

        $data['profile'] = Profil::where('code', $user['privilege'])->first();


        return response()
            ->json(
                $data,
                200
            );
    }


    public function privileges(Request $request)
    {

        $privileges = $request->user()->scopePrivileges($request->volet);
        $dr_id = $request->user()->DIRECTION;

        if ($privileges->visibilite == 'G') {
            $data['selectedDirection'] = null;
            $data['dr_id'] = null;
        }
        if ($privileges->visibilite == 'P' || $privileges->visibilite == 'L') {
            $data['selectedDirection'] = $dr_id;
                $data["dr_id"] = $dr_id;
        }

        $data = [
            'privileges' => $privileges,
            'data' => $data,
        ];
        return response()
            ->json(
                $data,
                200
            );
    }

     public function allPrivileges()
    {
        $privilege_parametrage = Auth::user()->scopePrivileges('parametrage');
        $privilege_liste_des_reclamations = Auth::user()->scopePrivileges('liste_des_reclamations');
        $privilege_parametrage_pcr = Auth::user()->scopePrivileges('parametrage_pcr');
        $privilege_dasboard_détaillé = Auth::user()->scopePrivileges('dasboard_détaillé');
        $privilege_dasboard_global = Auth::user()->scopePrivileges('dasboard_global');

        $AllPrivilege = [
            'privilege_parametrage' =>   $privilege_parametrage,
            'privilege_liste_des_reclamations' =>   $privilege_liste_des_reclamations,
            'privilege_parametrage_pcr' =>   $privilege_parametrage_pcr,
            'privilege_dasboard_détaillé' => $privilege_dasboard_détaillé,
            'privilege_dasboard_global' => $privilege_dasboard_global,
        ];

        return response()
            ->json(
                $AllPrivilege,
                200
            );
    }
    public function directions(Request $request)
    {
        $p = $request->user()->scopePrivileges($request->volet)?->visibilite;

        if ($p == 'G')
            $directions = Direction::groupby('DIRECTION')
                ->select('DIRECTION')
                ->get();
        else
            $directions = Direction::groupby('DIRECTION')
                ->select('DIRECTION')
                ->where('DIRECTION', $request->user()->direction)
                ->get();

        $data = [
            'directions' => $directions ?? null,
        ];
        return response()
            ->json(
                $data,
                200
            );
    }
}
