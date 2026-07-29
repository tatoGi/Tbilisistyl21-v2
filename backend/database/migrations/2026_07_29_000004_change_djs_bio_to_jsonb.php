<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Filament forces `SELECT DISTINCT djs.*` for every BelongsToMany relationship
 * select (see Filament\Support\Services\RelationshipJoiner), which is how the
 * DJ picker on the voting-round form is built. Postgres has no equality
 * operator for `json`, so DISTINCT over a row containing `bio` fails with
 * "could not identify an equality operator for type json". `jsonb` does have
 * one, and is the better Postgres type regardless.
 *
 * Only Postgres draws the json/jsonb distinction — on SQLite (the test driver)
 * both are text, so there is nothing to change.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE djs ALTER COLUMN bio TYPE jsonb USING bio::jsonb');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE djs ALTER COLUMN bio TYPE json USING bio::json');
    }
};
