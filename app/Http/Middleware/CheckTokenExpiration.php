<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\LogHistory;
class CheckTokenExpiration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::user()->privilege != 'Admin') {
            // Récupérer l'en-tête 'X-Log'
            $logHeader = $request->header('X-Log');
            // Convertir l'en-tête JSON en tableau associatif
            $log = json_decode($logHeader, true);
            // Utiliser les données de log
            // $time = $log['time'];
            if ($log != null)
                $route = $log['route'];
            if ($log != null) {
                if (!strpos($route, 'log-history') && !strpos($route, 'advanced-search-meta-data') && !strpos($route, 'directions') && !strpos($route, 'structures') && !strpos($route, 'privileges')) {
                    $data = [
                        'Email' => Auth::user()?->Email,
                        'ip' => $request->ip(),
                        'route' => $route,
                        'time' => date("Y-m-d H:i:s", strtotime(date("d-m-Y H:i:s"))),
                    ];
                    LogHistory::insert($data);
                    Log::info(
                        'User connected',
                        $data
                    );
                }
            }
        }
        $token = $request->bearerToken();
        $accessToken = PersonalAccessToken::findToken($token);
        $now = now();
        $carbonDate = \Carbon\Carbon::parse($now);
        $carbonDate->addHour();
        $carbonDate->toIso8601String();
        if ($accessToken && $accessToken->expires_at < $carbonDate) {
            // Invalidate the token
            $accessToken->delete();
            // Optionally, log out the user
            auth()->logout();
            return response()->json(['message' => 'Token expired'], 401);
        }
        $accessToken->expires_at = now()->addMinutes(540);
        $accessToken->save();
        return $next($request);
    }
}
