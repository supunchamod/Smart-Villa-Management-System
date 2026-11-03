<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Direct costs the villa itself pays for one specific booking (external
     * meals, transport, supplies, ...). Unlike booking_extras these are NOT
     * billed to the guest - they only reduce the profit made on the booking.
     * created_at doubles as the date the cost was recorded.
     */
    public function up(): void
    {
        Schema::create('booking_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_expenses');
    }
};
