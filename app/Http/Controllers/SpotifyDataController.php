<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Models\Song;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class SpotifyDataController extends Controller
{
    protected function makeApiRequest($endpoint, $method = 'GET', $data = [])
    {
        if (Auth::check() === false) {
            return [];
        }

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

    protected function parseNextUrl($url)
    {
        if (!$url) {
            return ['offset' => null, 'limit' => null];
        }
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        return [
            'offset' => $params['offset'] ?? null,
            'limit' => $params['limit'] ?? null
        ];
    }

    public function setUserPlaylists()
    {
        $limit = 100;
        $user = Auth::user();
        $offset = 0;

        do {
            $playlists = $this->makeApiRequest('me/playlists', 'GET', [
                'limit' => $limit,
                'offset' => $offset
            ]);

            foreach ($playlists['items'] as $playlistData) {
                if ($playlistData['owner']['id'] !== $user->spotify_id) {
                    continue;
                }
                $playlist = Playlist::updateOrCreate(
                    ['spotify_id' => $playlistData['id']],
                    [
                        'image' => $playlistData['images'][0]['url'] ?? null,
                        'name' => $playlistData['name'],
                        'user_id' => $user->id,
                    ]
                );

                $this->getPlaylistTracks($playlist, $playlistData['tracks']['href']);
            }

            $nextParams = $this->parseNextUrl($playlists['next'] ?? null);
            $offset = $nextParams['offset'];
        } while ($offset !== null);
    }

    protected function getPlaylistTracks(Playlist $playlist, $tracksHref)
    {
        $offset = 0;
        $limit = 100;

        do {
            $tracks = $this->makeApiRequest(
                str_replace('https://api.spotify.com/v1/', '', $tracksHref),
                'GET',
                ['offset' => $offset, 'limit' => $limit]
            );

            foreach ($tracks['items'] as $item) {
                $trackData = $item['track'];
                if (!isset($trackData['preview_url']) || !$trackData['preview_url']) {
                    continue;
                }
                $song = Song::updateOrCreate(
                    ['spotify_id' => $trackData['id']],
                    [
                        'preview_url' => $trackData['preview_url'],
                        'image' => $trackData['album']['images'][0]['url'] ?? null,
                        'name' => $trackData['name'],
                        'artist' => $trackData['artists'][0]['name'],
                        'album' => $trackData['album']['name'],
                    ]
                );

                $playlist->songs()->syncWithoutDetaching([$song->id]);
            }

            $nextParams = $this->parseNextUrl($tracks['next'] ?? null);
            $offset = $nextParams['offset'];
        } while ($offset !== null);
    }

    public function getUserPlaylistsWithSongs($userId = null)
    {
        $user = $userId ? Auth::findOrFail($userId) : Auth::user();

        $playlists = Playlist::where('user_id', $user->id)
            ->with(['songs' => function ($query) {
                $query->select('songs.id', 'spotify_id', 'name', 'artist', 'album', 'preview_url', 'image');
            }])
            ->get(['id', 'spotify_id', 'name', 'image']);

        $formattedPlaylists = $playlists->map(function ($playlist) {
            return [
                'id' => $playlist->id,
                'spotify_id' => $playlist->spotify_id,
                'name' => $playlist->name,
                'image' => $playlist->image,
                'songs' => $playlist->songs->map(function ($song) {
                    return [
                        'id' => $song->id,
                        'spotify_id' => $song->spotify_id,
                        'name' => $song->name,
                        'artist' => $song->artist,
                        'album' => $song->album,
                        'preview_url' => $song->preview_url,
                        'image' => $song->image,
                    ];
                })->toArray(),
            ];
        })->toArray();

        return $formattedPlaylists;
    }
}
