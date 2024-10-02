<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlaylistSongTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('playlist_song')) {
            Schema::create('playlist_song', function (Blueprint $table) {
                $table->id();
                $table->foreignId('playlist_id')->constrained()->onDelete('cascade');
                $table->foreignId('song_id')->constrained()->onDelete('cascade');
                $table->timestamps();

                $table->unique(['playlist_id', 'song_id']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('playlist_song');
    }
}
