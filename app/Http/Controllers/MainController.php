<?php

namespace App\Http\Controllers;

use App\Models\Bagence;
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
        $data = [
            'user' => $user,
        ];
        return response()
            ->json(
                $data,
                200
            );
    }
}
