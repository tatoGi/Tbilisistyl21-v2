<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_orders', function (Blueprint $table) {
            // Hosted-payment-page URL returned by the gateway; needed to send the
            // buyer to Quipu's payment page (product orders were missing it,
            // unlike sold_tickets).
            $table->text('pg_hpp_url')->nullable()->after('pg_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_orders', function (Blueprint $table) {
            $table->dropColumn('pg_hpp_url');
        });
    }
};
