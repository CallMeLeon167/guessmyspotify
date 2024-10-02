<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSongsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('songs')) {
            Schema::create('songs', function (Blueprint $table) {
                $table->id();
                $table->string('spotify_id')->unique();
                $table->string('name');
                $table->string('artist');
                $table->string('album');
                $table->string('preview_url', 500)->nullable();
                $table->string('image', 2048)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('songs');
    }
}
