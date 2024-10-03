<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Models\Song;
use App\Models\SpotifyUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
        $user = $userId ? SpotifyUser::findOrFail($userId) : Auth::user();

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

    public function getRandomPlaylistsAndSong($userId = null)
    {
        $allPlaylists = collect($this->getUserPlaylistsWithSongs($userId));

        $randomSong = $allPlaylists->flatMap->songs->random();

        $selectedPlaylists = $allPlaylists->shuffle()->take(3)
            ->map(function ($playlist) use ($randomSong) {
                $containsSong = collect($playlist['songs'])->contains('spotify_id', $randomSong['spotify_id']);
                return [
                    'id' => $playlist['id'],
                    'spotify_id' => $playlist['spotify_id'],
                    'name' => $playlist['name'],
                    'image' => $playlist['image'],
                    'contains_song' => $containsSong,
                ];
            })
            ->sortByDesc('contains_song')
            ->values();

        if (!$selectedPlaylists->contains('contains_song', true)) {
            $playlistWithSong = $allPlaylists->first(function ($playlist) use ($randomSong) {
                return collect($playlist['songs'])->contains('spotify_id', $randomSong['spotify_id']);
            });
            $selectedPlaylists->pop();
            $selectedPlaylists->push([
                'id' => $playlistWithSong['id'],
                'spotify_id' => $playlistWithSong['spotify_id'],
                'name' => $playlistWithSong['name'],
                'image' => $playlistWithSong['image'],
                'contains_song' => true,
            ]);
        }

        return [
            'playlists' => $selectedPlaylists->shuffle()->values()->all(),
            'song' => $randomSong,
        ];
    }

    public function isSongInPlaylist(Request $request, $userId = null)
    {
        $songSpotifyId = $request->input('songSpotifyId');
        $playlistId = $request->input('playlistId');

        $allPlaylists = collect($this->getUserPlaylistsWithSongs($userId));

        $playlist = $allPlaylists->firstWhere('id', $playlistId);

        if (!$playlist) {
            return false;
        }

        return collect($playlist['songs'])->contains('spotify_id', $songSpotifyId);
    }

    public function getUserStats($userId = null)
    {
        $user = $userId ? SpotifyUser::findOrFail($userId) : Auth::user();

        if (!$user) {
            return [];
        }

        $playlistCount = Playlist::where('user_id', $user->id)->count();

        $songCount = Song::whereHas('playlists', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->count();

        return [
            'user_id' => $user->id,
            'display_name' => $user->display_name,
            'playlist_count' => $playlistCount,
            'song_count' => $songCount,
        ];
    }

    public function getAllUser()
    {
        $users = SpotifyUser::all();
        return $users;
    }
}
