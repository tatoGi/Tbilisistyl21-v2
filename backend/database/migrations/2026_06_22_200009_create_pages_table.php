<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('title');
            $table->json('nav_label')->nullable();
            $table->string('slug')->unique();
            $table->string('route_path')->nullable();
            $table->boolean('show_in_nav')->default(false);
            $table->integer('nav_order')->default(0);
            $table->boolean('featured_on_home')->default(false);
            $table->string('layout')->nullable();
            $table->json('content_blocks')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
