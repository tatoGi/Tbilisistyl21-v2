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
            $table->boolean('is_joker')->default(false);
        });

        Schema::table('sold_tickets', function (Blueprint $table) {
            $table->boolean('is_joker')->default(false);
        });

        // Backfill from the names the detection previously relied on — the old
        // latin-only check missed Georgian titles like "ჯოკერ ბილეთი".
        // Filtered in PHP so it works on both Postgres (json title) and the
        // sqlite test database.
        $isJokerName = fn (?string $text): bool => $text !== null
            && (str_contains(strtolower($text), 'joker') || str_contains($text, 'ჯოკერ'));

        foreach (DB::table('tickets')->select('id', 'title')->get() as $row) {
            if ($isJokerName($row->title)) {
                DB::table('tickets')->where('id', $row->id)->update(['is_joker' => true]);
            }
        }

        foreach (DB::table('sold_tickets')->select('id', 'event_name')->get() as $row) {
            if ($isJokerName($row->event_name)) {
                DB::table('sold_tickets')->where('id', $row->id)->update(['is_joker' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('tickets', fn (Blueprint $table) => $table->dropColumn('is_joker'));
        Schema::table('sold_tickets', fn (Blueprint $table) => $table->dropColumn('is_joker'));
    }
};
