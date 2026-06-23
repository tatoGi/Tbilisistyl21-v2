<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('music_tracks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('title');
            $table->string('artist');
            $table->uuid('audio_file_id')->nullable();
            $table->integer('order')->default(0);
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->foreign('audio_file_id')->references('id')->on('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('music_tracks');
    }
};
