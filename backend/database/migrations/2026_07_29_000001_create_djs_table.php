<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('djs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->json('bio')->nullable();
            $table->uuid('photo_id')->nullable();
            $table->integer('order')->default(0);
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->foreign('photo_id')->references('id')->on('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('djs');
    }
};
