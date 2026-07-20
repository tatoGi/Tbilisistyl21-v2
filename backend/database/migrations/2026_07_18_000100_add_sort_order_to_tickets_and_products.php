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
            $table->integer('sort_order')->default(0);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->integer('sort_order')->default(0);
        });

        // Seed the order from creation time so the current display order is
        // preserved until an admin drags rows into a new one.
        foreach (['tickets', 'products'] as $tableName) {
            $ids = DB::table($tableName)->orderBy('created_at')->pluck('id');
            foreach ($ids as $i => $id) {
                DB::table($tableName)->where('id', $id)->update(['sort_order' => $i + 1]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('tickets', fn (Blueprint $table) => $table->dropColumn('sort_order'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('sort_order'));
    }
};
