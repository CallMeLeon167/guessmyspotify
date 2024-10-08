<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spotify Playlist-Rätsel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('public/css/style.css') }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('public/js/app.js') }}"></script>
    <script>
        var userId = @json(Auth::id());
    </script>
</head>

<body>
    @include('includes.flash-message')
    @include('includes.header')

    <main class="container">
        <section id="home" class="fade-in">
            <h1>Willkommen beim Spotify Playlist-Rätsel!</h1>
            <p>Tauche ein in die Welt der Musik und teste dein Wissen über Spotify-Playlists. Erkenne Songs und ordne
                sie den richtigen Playlists zu. Bist du bereit für die musikalische Herausforderung?</p>
            @if (Auth::check())
                <button id="start-btn" class="btn" data-id="{{ $user->id }}">
                    Jetzt spielen</button>
                <div class="challange">
                    <h2>Spiele die Playlisten anderer Spieler</h2>
                    <div class="challange-container">
                        @foreach ($controller->getAllUser() as $user)
                            @continue($user->id == Auth::id())
                            <div class="challange-user btn" data-id="{{ $user->id }}">
                                @if ($user->image)
                                    <img src="{{ $user->image }}" alt="user-pfp">
                                @endif
                                <span>{{ $user->display_name }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn btn-nav"><svg xmlns="http://www.w3.org/2000/svg"
                        fill="none" height="24" width="24" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                    </svg>
                    Anmelden</a>
            @endif
        </section>

        <section id="game" class="fade-in">
            <div id="quiz-container">
                <span class="playlist-from"></span>
                <div id="song-info">
                    <!-- Songbild, Titel und Künstler werden hier dynamisch eingefügt -->
                </div>
                <div id="playlists">
                    <!-- Playlist-Optionen werden hier dynamisch eingefügt -->
                </div>
                <div id="result"></div>
                <button id="next-question" class="btn" style="display: none;">Nächster Song</button>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 Spotify Playlist-Rätsel. Alle Rechte vorbehalten.</p>
    </footer>
</body>

</html>
