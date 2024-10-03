$(document).ready(function () {
    let currentSong;
    let correctPlaylistId;
    let audio = new Audio();
    let isPlaying = false;
    let duration = 0;

    function loadQuestion() {
        $.ajax({
            url: '/api/spotify/random-playlists-and-song',
            method: 'GET',
            success: function (response) {
                currentSong = response.song;
                let playlists = response.playlists;
                const spotifyLink = createSpotifyLink(currentSong.spotify_id, 'track');

                $('#song-info').html(`
            <h2>In welcher Playlist ist dieser Song?</h2>
            <img src="${currentSong.image}" alt="${currentSong.name}">
            <div class="song-details">
                <p><strong>${currentSong.name}</strong></p>
                <p>Von: ${currentSong.artist} | Album: ${currentSong.album}</p>
                </div>
                <div class="audio-player">
                    <div class="audio-controls">
                        <button class="play-pause-btn" id="playPauseBtn">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </button>
                    </div>
                        <div class="progress-bar" id="progressBar">
                            <div class="progress" id="progress"></div>
                        </div>
                    <div class="time" id="timeDisplay">0:00</div>
                </div>
            ${spotifyLink}
        `);

                let playlistsHtml = '';
                playlists.forEach(function (playlist) {
                    playlistsHtml += `
                <div class="playlist" data-id="${playlist.id}">
                    <img src="${playlist.image}" alt="${playlist.name}">
                    <p>${playlist.name}</p>
                </div>
            `;
                });
                $('#playlists').html(playlistsHtml);
                $('#result').text('').removeClass('correct incorrect');
                $('#next-question').hide();

                // Verzögerung hinzufügen, bevor der Audio-Player initialisiert wird
                setTimeout(() => {
                    initAudioPlayer(currentSong.preview_url);
                }, 100);
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
                $('#quiz-container').html(
                    '<p style="color: #ff4136;">Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.</p>'
                );
            }
        });
    }

    $(document).on('click', '.playlist', function () {
        let selectedPlaylistId = $(this).data('id');
        $.ajax({
            url: '/api/spotify/is-song-in-playlist',
            method: 'GET',
            data: {
                songSpotifyId: currentSong.spotify_id,
                playlistId: selectedPlaylistId
            },
            success: function (isInPlaylist) {
                if (isInPlaylist) {
                    $('#result').text('Richtig! Der Song ist in dieser Playlist.')
                        .addClass('correct').removeClass('incorrect');
                    $('#quiz-container').addClass('success-animation');
                    createConfetti();
                    setTimeout(() => $('#quiz-container').removeClass(
                        'success-animation'), 500);
                    $('#next-question').show();
                } else {
                    $('#result').text('Falsch. Der Song ist nicht in dieser Playlist.')
                        .addClass('incorrect').removeClass('correct');
                    $('#next-question').hide();
                }
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
                $('#result').text(
                    'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.'
                ).addClass('incorrect').removeClass('correct');
            }
        });
    });

    $('#next-question').click(function () {
        loadQuestion();
    });

    $('#play-btn, #start-btn').click(function () {
        $('#home').hide();
        $('#game').show().addClass('fade-in');
        loadQuestion();
    });

    $(document).on('click', '#playPauseBtn', function () {
        if (isPlaying) {
            audio.pause();
            $(this).html(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>'
            );
        } else {
            audio.play();
            $(this).html(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>'
            );
        }
        isPlaying = !isPlaying;
    });

    $(document).on('click', '#progressBar', function (e) {
        const clickPosition = e.pageX - $(this).offset().left;
        const clickPercent = clickPosition / $(this).width();
        audio.currentTime = clickPercent * audio.duration;
    });

    function createConfetti() {
        for (let i = 0; i < 50; i++) {
            let confetti = $('<div class="confetti"></div>');
            confetti.css({
                'left': Math.random() * window.innerWidth + 'px',
                'top': -10 + 'px',
                'background-color': '#' + Math.floor(Math.random() * 16777215).toString(16),
                'animation-duration': (Math.random() * 3 + 2) + 's',
                'animation-delay': (Math.random() * 5) + 's'
            });
            $('body').append(confetti);
            setTimeout(() => confetti.remove(), 5000);
        }
    }

    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
    }

    function updateProgressBar() {
        if (audio.duration) {
            const progress = (audio.currentTime / audio.duration) * 100;
            $('#progress').css('width', `${progress}%`);
            $('#timeDisplay').text(formatTime(audio.currentTime));
        }
    }

    function createSpotifyLink(spotifyId, type = 'track') {
        const webPlayerUrl = `https://open.spotify.com/${type}/${spotifyId}`;
        const spotifyUri = `spotify:${type}:${spotifyId}`;
        return `<a href="${spotifyUri}" class="btn spotify-open">Auf Spotify öffnen</a>`;
    }

    function initAudioPlayer(previewUrl) {
        audio.pause();
        audio.currentTime = 0;
        isPlaying = false;

        audio.src = previewUrl;
        audio.load();

        audio.addEventListener('loadedmetadata', function () {
            duration = audio.duration;
            $('#timeDisplay').text(formatTime(0));
        });

        audio.addEventListener('timeupdate', updateProgressBar);

        audio.addEventListener('ended', function () {
            isPlaying = false;
            $('#playPauseBtn').html(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>'
            );
        });
    }

    $('#home').show();
    $('#game').hide();
});
