<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spotify Playlist Quiz</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            padding: 20px;
        }

        #quiz-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .playlist {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            cursor: pointer;
        }

        .playlist:hover {
            background-color: #f0f0f0;
        }

        .playlist img {
            width: 50px;
            height: 50px;
            margin-right: 10px;
            vertical-align: middle;
        }

        #result {
            margin-top: 20px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div id="quiz-container">
        <h1>Spotify Playlist Quiz</h1>
        <div id="song-info"></div>
        <div id="playlists"></div>
        <div id="result"></div>
        <button id="next-question">Nächste Frage</button>
    </div>

    <script>
        $(document).ready(function() {
            let currentSong;
            let correctPlaylistId;

            function loadQuestion() {
                $.ajax({
                    url: '/api/spotify/random-playlists-and-song',
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        currentSong = response.song;
                        let playlists = response.playlists;

                        $('#song-info').html(
                            `<h2>In welcher Playlist ist dieser Song?</h2>
                                          <p><strong>${currentSong.name}</strong> von ${currentSong.artist}</p>
                                          <img src="${currentSong.image}" alt="${currentSong.name}" style="width:100px; height:100px;">`
                        );

                        let playlistsHtml = '';
                        playlists.forEach(function(playlist) {
                            playlistsHtml += `<div class="playlist" data-id="${playlist.id}">
                                              <img src="${playlist.image}" alt="${playlist.name}">
                                              ${playlist.name}
                                          </div>`;
                        });
                        $('#playlists').html(playlistsHtml);
                        $('#result').text('');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        $('#quiz-container').html(
                            '<p>Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.</p>'
                        );
                    }
                });
            }

            $(document).on('click', '.playlist', function() {
                let selectedPlaylistId = $(this).data('id');
                $.ajax({
                    url: '/api/spotify/is-song-in-playlist',
                    method: 'GET',
                    data: {
                        songSpotifyId: currentSong.spotify_id,
                        playlistId: selectedPlaylistId
                    },
                    success: function(isInPlaylist) {
                        if (isInPlaylist) {
                            $('#result').text('Richtig! Der Song ist in dieser Playlist.').css(
                                'color', 'green');
                        } else {
                            $('#result').text('Falsch. Der Song ist nicht in dieser Playlist.')
                                .css('color', 'red');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        $('#result').text(
                            'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.'
                        ).css('color', 'red');
                    }
                });
            });

            $('#next-question').click(loadQuestion);

            // Lade die erste Frage beim Start
            loadQuestion();
        });
    </script>
</body>

</html>
