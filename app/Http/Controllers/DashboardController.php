<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        echo "<pre>";
        var_dump(app(SpotifyDataController::class)->setUserPlaylists());
        echo "</pre>";
        return view('dashboard', [
            'user' => Auth::user(),
            'controller' => app(SpotifyDataController::class),
        ]);
    }
}
