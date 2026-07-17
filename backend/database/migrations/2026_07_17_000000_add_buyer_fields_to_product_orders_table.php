<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_orders', function (Blueprint $table) {
            // Nullable: orders placed before these fields existed have neither.
            $table->string('surname')->nullable()->after('name');
            $table->string('personal_number', 11)->nullable()->after('surname');
        });
    }

    public function down(): void
    {
        Schema::table('product_orders', function (Blueprint $table) {
            $table->dropColumn(['surname', 'personal_number']);
        });
    }
};
