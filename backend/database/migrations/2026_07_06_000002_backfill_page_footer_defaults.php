<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pages')->whereNull('show_in_footer')->update(['show_in_footer' => false]);
        DB::table('pages')->whereNull('footer_order')->update(['footer_order' => 100]);
    }

    public function down(): void
    {
        // no-op
    }
};
