<?php

namespace App\Http\Controllers;

use App\Models\VoletApp;
use App\Models\Profil;
use App\Models\Privilege;
use Illuminate\Http\Request;
use App\Http\Resources\VoletResource;
use App\Http\Requests\CreateVoletRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VoletController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $privileges = Auth::user()->scopePrivileges('volets');

        // if (!$privileges->consultation)
        //     return $this->sendErrorResponse('Vous n\'avez pas les privilèges pour consulter cette page', 403);
        $volets = VoletApp::query();

        if (isset($request->search))
            $volets = $volets->where('volet', 'like', '%' . $request->search . '%')
                ->orWhere('module', 'like', '%' . $request->search . '%');

        $perPage = $request->per_page;
        $page = $request->page;
        $volets = $volets->paginate($perPage, ['*'], 'page', $page);

        $totalPages = $volets->lastPage();
        $currentPage = $volets->currentPage();

        return $this->sendSuccessResponse(
            [
                'volets' => VoletResource::collection($volets),
                'pagination' => [
                    'per_page' => intval($perPage),
                    'page' => $currentPage,
                    'lastPage' => $totalPages
                ]
            ]
        );
    }

    public function store(CreateVoletRequest $request)
    {
        $validated = $request->validated();

        $volet = VoletApp::create($validated);
        $profils = Profil::get();

        foreach ($profils as $profil) {
            Privilege::create([
                'profil_code' => $profil->code,
                'module' => $validated['module'],
                'volet' => $validated['volet'],
                'description' => $validated['description'],
                'consultation' => 1,
                'suppression' => 0,
                'modification' => 0,
                'insertion' => 0,
                'visibilite' => $profil->limitation,
                'role' => $profil->role,
            ]);
        }
        return response()->json($profil, '200');

    }

    public function destroy($id)
    {
        $volet = VoletApp::find($id);
        if (!$volet) {
            return $this->sendErrorResponse('Le volet n\'existe pas', 404);
        }
        Privilege::where('volet', $volet->volet)
            ->where('module', $volet->module)
            ->delete();
        $volet->delete();

                    return response()->json([
                            'success' => true,
                            'message' => 'Le volet à été supprimé'
                        ], '200');
    }
}
