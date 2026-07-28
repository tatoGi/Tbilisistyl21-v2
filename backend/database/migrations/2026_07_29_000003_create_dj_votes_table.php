<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dj_votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('round_id');
            $table->uuid('dj_id');
            $table->string('voter_token', 64);
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->foreign('round_id')->references('id')->on('dj_voting_rounds')->cascadeOnDelete();
            $table->foreign('dj_id')->references('id')->on('djs')->cascadeOnDelete();

            // The deduplication guarantee lives here, not in application code,
            // so two simultaneous requests cannot both insert.
            $table->unique(['round_id', 'voter_token']);
            $table->index(['round_id', 'dj_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dj_votes');
    }
};
