<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive redesign fields for the ticket tier cards. All nullable / defaulted
 * so existing rows are untouched and keep rendering.
 *
 * - category:    short tier label (translatable JSON), e.g. STANDARD / JOKER PASS
 * - features:    included-features checklist (translatable JSON, newline-separated)
 * - is_featured: highlights the "popular" tier card
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->text('category')->nullable();
            $table->text('features')->nullable();
            $table->boolean('is_featured')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['category', 'features', 'is_featured']);
        });
    }
};
