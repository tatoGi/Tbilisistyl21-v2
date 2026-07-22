<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->boolean('is_techno')->default(false);
        });

        Schema::table('sold_tickets', function (Blueprint $table) {
            $table->boolean('is_techno')->default(false);
        });

        // Backfill from names so any pre-existing "techno" titles keep working.
        // Filtered in PHP so it runs on both Postgres (json title) and sqlite.
        $isTechnoName = fn (?string $text): bool => $text !== null
            && (str_contains(strtolower($text), 'techno') || str_contains($text, 'ტექნო'));

        foreach (DB::table('tickets')->select('id', 'title')->get() as $row) {
            if ($isTechnoName($row->title)) {
                DB::table('tickets')->where('id', $row->id)->update(['is_techno' => true]);
            }
        }

        foreach (DB::table('sold_tickets')->select('id', 'event_name')->get() as $row) {
            if ($isTechnoName($row->event_name)) {
                DB::table('sold_tickets')->where('id', $row->id)->update(['is_techno' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('tickets', fn (Blueprint $table) => $table->dropColumn('is_techno'));
        Schema::table('sold_tickets', fn (Blueprint $table) => $table->dropColumn('is_techno'));
    }
};
