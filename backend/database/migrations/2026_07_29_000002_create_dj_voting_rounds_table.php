<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dj_voting_rounds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
        });

        Schema::create('dj_voting_round_dj', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('round_id');
            $table->uuid('dj_id');
            $table->integer('order')->nullable();

            $table->foreign('round_id')->references('id')->on('dj_voting_rounds')->cascadeOnDelete();
            $table->foreign('dj_id')->references('id')->on('djs')->cascadeOnDelete();
            $table->unique(['round_id', 'dj_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dj_voting_round_dj');
        Schema::dropIfExists('dj_voting_rounds');
    }
};
