<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpotifyUser extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'spotify_id',
        'email',
        'display_name',
        'image',
        'access_token',
        'refresh_token',
        'token_expires_at',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
    ];

    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class);
    }
}
