<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A cabana type is the public-facing "product" managed from the new
     * Landing Page admin section - name, marketing image/description, and
     * max capacity - kept separate from Rooms (the internal
     * booking/calendar inventory record) per the Settings/Landing Page
     * isolation this feature introduces. room_id is the bridge between
     * the two: bookings still need a real room_id (see bookings table),
     * so a cabana type must be linked to a Room before it can actually be
     * booked from the public page - nullable so a cabana type can exist
     * as a draft before that link is made.
     */
    public function up(): void
    {
        Schema::create('cabana_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('image_url')->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('max_capacity')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cabana_types');
    }
};
