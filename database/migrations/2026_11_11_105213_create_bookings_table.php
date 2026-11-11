<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->string('guest_name');
            $table->string('guest_email');
            $table->date('check_in');
            $table->date('check_out');
            $table->string('status', 16)->default('confirmed');
            $table->unsignedInteger('total_cents');
            $table->timestamps();

            // Supports the overlap query: WHERE room_id = ? AND status = ? AND check_in < ? AND check_out > ?
            $table->index(['room_id', 'status', 'check_in', 'check_out']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
