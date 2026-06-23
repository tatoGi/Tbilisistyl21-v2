<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sold_tickets', function (Blueprint $table) {
            $table->string('id', 8)->primary(); // 8-char uppercase code
            $table->string('personal_number', 11);
            $table->string('email');
            $table->string('name');
            $table->string('surname');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending, paid, failed, scanned
            $table->uuid('original_ticket_id')->nullable();
            $table->string('event_name')->nullable();
            $table->date('event_date')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->string('scanned_by')->nullable();
            $table->unsignedBigInteger('pg_order_id')->nullable();
            $table->text('pg_hpp_url')->nullable();
            $table->text('pg_password')->nullable(); // encrypted
            $table->text('qr_code')->nullable(); // encrypted
            $table->timestamp('failed_at')->nullable();
            $table->string('fail_reason')->nullable();
            $table->timestamps();

            $table->index('personal_number');
            $table->index('status');
            $table->index('pg_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sold_tickets');
    }
};
