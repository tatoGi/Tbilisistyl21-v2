<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_orders', function (Blueprint $table) {
            $table->string('id', 8)->primary();
            $table->uuid('product_id');
            $table->string('product_title');
            $table->string('size');
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending, paid, collected, failed
            $table->unsignedBigInteger('pg_order_id')->nullable();
            $table->text('pg_password')->nullable(); // encrypted
            $table->text('qr_code')->nullable(); // encrypted
            $table->timestamps();

            $table->index('status');
            $table->index('pg_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_orders');
    }
};
