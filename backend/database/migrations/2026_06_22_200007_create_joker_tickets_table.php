<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('joker_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sold_ticket_id', 8);
            $table->string('personal_number', 11);
            $table->string('email');
            $table->string('name');
            $table->string('surname');
            $table->timestamps();

            $table->foreign('sold_ticket_id')->references('id')->on('sold_tickets');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('joker_tickets');
    }
};
