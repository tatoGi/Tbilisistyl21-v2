<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('title');
            $table->json('description')->nullable();
            $table->decimal('price_gel', 10, 2);
            $table->string('category')->nullable();
            $table->boolean('is_vip')->default(false);
            $table->uuid('image_id')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->foreign('image_id')->references('id')->on('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
