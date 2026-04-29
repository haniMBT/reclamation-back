<?php

namespace App\Http\Controllers;

use App\Models\Version\Action;
use App\Models\Version\Version;
use Illuminate\Http\Request;

class VersionController extends Controller
{

    public function index(Request $request){
        // Policies
        $user = auth()->user() ;
        if (!$user or $user->privilege != 'Admin'){
            return response()->json([
                'status' => 401,
                'message' => 'Unauthorized',
            ]);
        }

        // Get les filtres
        $perPage = $request->input('per_page', 5);
        $filterByVersion = $request->input('filter_by_version', '');
        $filterByDescription = $request->input('filter_by_description', '');
        $filterByAction = $request->input('filter_by_action', '');

        $versionParts = explode('.', $filterByVersion);

        // Search
        $versions = Version::query()->with(['actions', 'user'])
            ->when(isset($versionParts[0]) && $versionParts[0] != "", function ($query) use ($versionParts) {
                $query->where('major', '=', $versionParts[0]);
            })
            ->when(isset($versionParts[1]) && $versionParts[1] != "", function ($query) use ($versionParts) {
                $query->where('minor', '=', $versionParts[1]);
            })
            ->when(isset($versionParts[2]) && $versionParts[2] != "", function ($query) use ($versionParts) {
                $query->where('patch', '=', $versionParts[2]);
            })
            ->when(isset($versionParts[3]) && $versionParts[3] != "", function ($query) use ($versionParts) {
                $query->where('build', '=', $versionParts[3]);
            })
            ->when($filterByDescription != "", function ($query) use ($filterByDescription) {
                $query->where('description', 'LIKE', '%'.$filterByDescription.'%');
            })
            ->when($filterByAction != "", function ($query) use ($filterByAction) {
                $query->whereHas('actions', function ($query2) use ($filterByAction){
                    $query2->where('action', 'LIKE', '%'.$filterByAction.'%');
                });
            })
            ->orderBy('major', 'desc')
            ->orderBy('minor', 'desc')
            ->orderBy('patch', 'desc')
            ->orderBy('build', 'desc')
            ->paginate($perPage);

        return response()->json([
            'status' => 200,
            'data' => $versions,
        ]);
    }

    private function checkVersionExists($major, $minor, $patch, $build){
        return Version::where('major', $major)->where('minor', $minor)->where('patch', $patch)->where('build', $build)->count();
    }
    public function store(Request $request){
        $user = auth()->user();
        if (!$user or $user->privilege != 'Admin'){
            return response()->json([
                'status' => 401,
                'message' => 'Unauthorized',
            ]);
        }

        // 1. Validator.
        $request->validate([
            'major' => ['required', 'numeric', 'min:1'],
            'minor' => ['required', 'numeric', 'min:0'],
            'patch' => ['required', 'numeric', 'min:0'],
            'build' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'release_date' => ['required', 'date'],
            'actions.*' => ['required', 'string'],
        ], [
            'major.required' => "Ce champs est requis",
            'major.numeric' => "Ce champs doit être un nombre",
            'major.min' => "Ce champs ne doit pas être inferieur à 1",
            'minor.required' => "Ce champs est requis",
            'minor.numeric' => "Ce champs doit être un nombre",
            'minor.min' => "Ce champs ne doit pas être inferieur à 0",
            'patch.required' => "Ce champs est requis",
            'patch.numeric' => "Ce champs doit être un nombre",
            'patch.min' => "Ce champs ne doit pas être inferieur à 0",
            'build.required' => "Ce champs est requis",
            'build.numeric' => "Ce champs doit être un nombre",
            'build.min' => "Ce champs ne doit pas être inferieur à 0",
            'release_date.required' => "Ce champs est requis",
            'release_date.date' => "Ce champs doit être au format d'une date",
        ]);

        // 2. Verif que la version n'existe pas.
        if ($this->checkVersionExists($request->get('major'), $request->get('minor'), $request->get('patch'), $request->get('build')) != 0){
            return response()->json([
                'status' => 422,
                'message' => 'Cette version existe déjà' ,
            ]);
        }

        // 3. Create.
        $version = Version::create([
            'major' => $request->get('major'),
            'minor' => $request->get('minor'),
            'patch' => $request->get('patch'),
            'build' => $request->get('build'),
            'description' => $request->get('description'),
            'release_date' => $request->get('release_date'),
            'user_matricule' => $user->Matricule ,
        ]);
        if ($request->actions != null && sizeof($request->get('actions')) > 0){
            foreach ($request->get('actions') as $action) {
                Action::create([
                    'action' => $action ,
                    'version_id' => $version->id,
                ]);
            }
        }
        $version->load('actions');

        // 4. Return.
        return response()->json([
            'status' => 200,
            'data' => $version,
            'message' => 'Version crée avec success',
        ]);
    }

    public function show($id){
        $user = auth()->user() ;
        if (!$user or $user->privilege != 'Admin'){
            return response()->json([
                'status' => 401,
                'message' => 'Unauthorized',
            ]);
        }

        $version = Version::with(['actions', 'user'])->find($id);
        if ($version == null){
            return response()->json([
                'status' => 404,
                'message' => 'Version Not Found' ,
            ]);
        }
        return response()->json([
            'status' => 200,
            'data' => $version,
        ]);
    }

    public function update(Request $request, $id){

        $user = auth()->user() ;
        if (!$user or $user->privilege != 'Admin'){
            return response()->json([
                'status' => 401,
                'message' => 'Unauthorized',
            ]);
        }

        // 1. Validator.
        $request->validate([
            'major' => ['required', 'numeric', 'min:1'],
            'minor' => ['required', 'numeric', 'min:0'],
            'patch' => ['required', 'numeric', 'min:0'],
            'build' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'release_date' => ['required', 'date'],
            'actions.*' => ['required', 'string'],
        ], [
            'major.required' => "Ce champs est requis",
            'major.numeric' => "Ce champs doit être un nombre",
            'major.min' => "Ce champs ne doit pas être inferieur à 1",
            'minor.required' => "Ce champs est requis",
            'minor.numeric' => "Ce champs doit être un nombre",
            'minor.min' => "Ce champs ne doit pas être inferieur à 0",
            'patch.required' => "Ce champs est requis",
            'patch.numeric' => "Ce champs doit être un nombre",
            'patch.min' => "Ce champs ne doit pas être inferieur à 0",
            'build.required' => "Ce champs est requis",
            'build.numeric' => "Ce champs doit être un nombre",
            'build.min' => "Ce champs ne doit pas être inferieur à 0",
            'release_date.required' => "Ce champs est requis",
            'release_date.date' => "Ce champs doit être au format d'une date",
        ]);

        // 2. Verif que la version n'existe pas.
        if (Version::where('major', $request->major)->where('minor', $request->minor)->where('patch', $request->patch)->where('build', $request->build)->where('id', '!=', $id)->exists()){
            return response()->json([
                'status' => 422,
                'message' => 'Cette version existe déjà' ,
            ]);
        }

        // 3. Update.
        $version = Version::where('id', $id)->first() ;
        if ($version == null){
            return response()->json([
                'status' => 404,
                'message' => 'Version Not Found' ,
            ]);
        }
        $version->major = $request->get('major');
        $version->minor = $request->get('minor');
        $version->patch = $request->get('patch');
        $version->build = $request->get('build');
        $version->description = $request->get('description');
        $version->release_date = $request->get('release_date');
        $version->save();

        // 4. Return
        return response()->json([
            'status' => 200,
            'data' => $version,
            'message' => 'Version supprimé avec succes'
        ]);
    }

    public function destroy($id){
        $user = auth()->user() ;
        if (!$user or $user->privilege != 'Admin'){
            return response()->json([
                'status' => 401,
                'message' => 'Unauthorized',
            ]);
        }

        $version = Version::where('id', $id)->first() ;
        if (!$version){
            return response()->json([
                'status' => 404,
                'message' => 'Version Not Found' ,
            ]);
        }
        $version->delete();
        return response()->json([
            'status' => 200,
            'data' => $version,
            'message' => 'Entité supprimée avec success' ,
        ]);
    }

    ////////////////////////////

    public function getLatestVersion(){
        $user = auth()->user() ;
        if (!$user){
            return response()->json([
                'status' => 401,
                'message' => 'Unauthorized',
            ]);
        }

        $maxMajor = Version::max('major') ;
        $maxMinor = Version::where('major', '=', $maxMajor)->max('minor') ;
        $maxPatch = Version::where('major', '=', $maxMajor)->where('minor', '=', $maxMinor)->max('minor') ;
        $maxBuild = Version::where('major', '=', $maxMajor)->where('minor', '=', $maxMinor)->where('patch', '=', $maxPatch)->max('build') ;
        return response()->json([
            'status' => 200 ,
            'major' => $maxMajor == null ? 1 : $maxMajor ,
            'minor' => $maxMinor == null ? 0 : $maxMinor ,
            'patch' => $maxPatch == null ? 0 : $maxPatch ,
            'build' => $maxBuild == null ? 0 : $maxBuild ,
        ]);
    }

    public function getVersions(){
        $versions = Version::with('actions')
            ->orderBy('major', 'desc')
            ->orderBy('minor', 'desc')
            ->orderBy('patch', 'desc')
            ->orderBy('build', 'desc')
            ->get();
        return response()->json([
            'status' => 200,
            'data' => $versions,
        ]);
    }

    protected function getUserProfil(){
        $user = auth()->user() ;
        if (!$user){
            return response()->json([
                'status' => 401,
                'message' => 'Unauthorized',
            ]);
        }
        return response()->json([
            'status' => 200 ,
            'data' => $user->privilege,
        ]);
    }

    ////////////////////////////

}
