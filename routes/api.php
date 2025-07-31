<?php

use App\Http\Controllers\AideController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login', [App\Http\Controllers\MainController::class, 'login']);
Route::get('/fiches', [PublicationFichesController::class, 'fiches'])->name('fiches');
// check.token.exiration(log-history(axios data sending), token expiration or add 30mn)
Route::middleware(['auth:sanctum', 'check.token.expiration'])->group(function ($request) {
    Route::post('/logout', [\App\Http\Controllers\MainController::class, 'logout']);
    Route::get('/user', [\App\Http\Controllers\MainController::class, 'user']);
});
