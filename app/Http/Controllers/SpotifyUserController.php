<?php

namespace App\Http\Controllers;

use App\Models\SpotifyUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SpotifyUserController extends Controller
{
    private $clientId;
    private $clientSecret;
    private $redirectUri;

    public function __construct()
    {
        $this->clientId = env('SPOTIFY_CLIENT_ID');
        $this->clientSecret = env('SPOTIFY_CLIENT_SECRET');
        $this->redirectUri = env('SPOTIFY_REDIRECT_URI');
    }

    public function redirectToSpotify()
    {
        $scopes = 'user-read-private user-read-email';
        $url = 'https://accounts.spotify.com/authorize?' . http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'scope' => $scopes,
        ]);

        return redirect($url);
    }

    public function handleCallback(Request $request)
    {
        $code = $request->code;

        $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        $data = $response->json();

        $accessToken = $data['access_token'];
        $refreshToken = $data['refresh_token'];
        $expiresIn = $data['expires_in'];

        $userResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('https://api.spotify.com/v1/me');

        $userData = $userResponse->json();

        $spotifyUser = SpotifyUser::updateOrCreate(
            ['spotify_id' => $userData['id']],
            [
                'email' => $userData['email'],
                'display_name' => $userData['display_name'],
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_expires_at' => Carbon::now()->addSeconds($expiresIn),
            ]
        );

        return redirect('/')->with('success', 'Spotify-Konto erfolgreich verknüpft!');
    }
}
