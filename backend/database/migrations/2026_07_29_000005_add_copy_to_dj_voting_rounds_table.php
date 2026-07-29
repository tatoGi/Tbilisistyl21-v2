<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-editable copy for the public voting section. Nullable on purpose: a
 * blank field means "use the frontend's own translated default", so existing
 * rounds keep working untouched.
 *
 * jsonb rather than json — Postgres cannot run DISTINCT over a json column,
 * which Filament does for relationship selects (see the djs.bio migration).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dj_voting_rounds', function (Blueprint $table) {
            $table->jsonb('heading')->nullable()->after('title');
            $table->jsonb('subtitle')->nullable()->after('heading');
        });
    }

    public function down(): void
    {
        Schema::table('dj_voting_rounds', function (Blueprint $table) {
            $table->dropColumn(['heading', 'subtitle']);
        });
    }
};
