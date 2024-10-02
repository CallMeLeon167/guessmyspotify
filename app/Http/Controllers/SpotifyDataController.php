<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class SpotifyDataController extends Controller
{
    protected function makeApiRequest($endpoint, $method = 'GET', $data = [])
    {
        $user = Auth::user();
        if (Carbon::now()->gt($user->token_expires_at)) {
            $spotifyController = new SpotifyUserController();
            $newAccessToken = $spotifyController->refreshAccessToken($user);
            if (!$newAccessToken) {
                return null; // Token refresh failed
            }
        }
        $response = Http::withToken($user->access_token)->$method("https://api.spotify.com/v1/$endpoint", $data);

        return $response->json();
    }

    public function getUserPlaylists()
    {
        $playlists = $this->makeApiRequest('me/playlists');

        return $playlists;
    }
}
