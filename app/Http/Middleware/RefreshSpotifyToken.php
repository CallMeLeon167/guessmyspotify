<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\SpotifyUser;
use App\Http\Controllers\SpotifyUserController;

class RefreshSpotifyToken
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user() instanceof SpotifyUser) {
            $user = Auth::user();

            if (Carbon::now()->gt($user->token_expires_at)) {
                $spotifyController = new SpotifyUserController();
                $newAccessToken = $spotifyController->refreshAccessToken($user);

                if (!$newAccessToken) {
                    // Token refresh failed
                    Auth::logout();
                    return redirect('/login')->with('error', 'Spotify-Sitzung abgelaufen. Bitte loggen Sie sich erneut ein.');
                }
            }
        }

        return $next($request);
    }
}
