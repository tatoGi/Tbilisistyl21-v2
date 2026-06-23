<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('title');
            $table->json('description')->nullable();
            $table->decimal('price_gel', 10, 2);
            $table->integer('quantity')->default(0);
            $table->date('event_date');
            $table->string('location');
            $table->string('status')->default('draft'); // draft, active, sold_out
            $table->string('sale_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
