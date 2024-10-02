<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $sp_controller = new SpotifyDataController;

        // var_dump($sp_controller->getUserPlaylists());
        return view('dashboard', [
            'user' => auth()->user(),
        ]);
    }
}
