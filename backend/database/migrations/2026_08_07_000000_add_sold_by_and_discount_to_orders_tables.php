<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sold_tickets', function (Blueprint $table) {
            $table->string('sold_by')->nullable()->after('scanned_by');
            $table->decimal('discount_amount', 10, 2)->nullable()->after('amount');
        });

        Schema::table('product_orders', function (Blueprint $table) {
            $table->string('sold_by')->nullable()->after('status');
            $table->decimal('discount_amount', 10, 2)->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('sold_tickets', function (Blueprint $table) {
            $table->dropColumn(['sold_by', 'discount_amount']);
        });

        Schema::table('product_orders', function (Blueprint $table) {
            $table->dropColumn(['sold_by', 'discount_amount']);
        });
    }
};
