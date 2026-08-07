<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sold_tickets', function (Blueprint $table) {
            $table->decimal('base_amount', 10, 2)->nullable()->after('amount');
            $table->decimal('surcharge_amount', 10, 2)->default(0)->after('base_amount');
            $table->decimal('surcharge_rate', 5, 2)->nullable()->after('surcharge_amount');
        });

        Schema::table('product_orders', function (Blueprint $table) {
            $table->decimal('base_amount', 10, 2)->nullable()->after('amount');
            $table->decimal('surcharge_amount', 10, 2)->default(0)->after('base_amount');
            $table->decimal('surcharge_rate', 5, 2)->nullable()->after('surcharge_amount');
            $table->timestamp('paid_at')->nullable()->after('status');
        });

        DB::table('sold_tickets')->whereNull('base_amount')->update([
            'base_amount' => DB::raw('amount'),
            'surcharge_amount' => 0,
            'surcharge_rate' => null,
        ]);

        DB::table('product_orders')->whereNull('base_amount')->update([
            'base_amount' => DB::raw('amount'),
            'surcharge_amount' => 0,
            'surcharge_rate' => null,
        ]);

        DB::table('product_orders')
            ->where('status', 'paid')
            ->whereNull('paid_at')
            ->update(['paid_at' => DB::raw('COALESCE(updated_at, created_at)')]);
    }

    public function down(): void
    {
        Schema::table('sold_tickets', function (Blueprint $table) {
            $table->dropColumn(['base_amount', 'surcharge_amount', 'surcharge_rate']);
        });
        Schema::table('product_orders', function (Blueprint $table) {
            $table->dropColumn(['base_amount', 'surcharge_amount', 'surcharge_rate', 'paid_at']);
        });
    }
};
