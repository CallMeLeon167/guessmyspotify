<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SpotifyUserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/login', [SpotifyUserController::class, 'redirectToSpotify'])->name('login');
Route::get('/callback', [SpotifyUserController::class, 'handleCallback']);

Route::get('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');
