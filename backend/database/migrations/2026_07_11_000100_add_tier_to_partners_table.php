<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive partner tier for the redesigned /partners page.
 * Default 'official' so every existing partner lands in the Official group
 * automatically — no backfill, no data loss.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('tier')->default('official');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('tier');
        });
    }
};
