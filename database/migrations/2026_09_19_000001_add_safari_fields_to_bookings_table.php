<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the optional Safari Management Module fields. `booking_type`
     * classifies a booking as an accommodation stay, a safari-only
     * reservation (no room), or a combined stay + safari full package.
     * `room_id` is widened to nullable because a safari-only booking has
     * no room - callers that read $booking->room must null-check it
     * (Booking::room_label handles this for display purposes).
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('booking_type')->default('accommodation_only')->after('room_id');
            $table->string('safari_tour_name')->nullable()->after('booking_type');
            $table->date('safari_date')->nullable()->after('safari_tour_name');
            $table->string('safari_vehicle_details')->nullable()->after('safari_date');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable(false)->change();
            $table->dropColumn(['booking_type', 'safari_tour_name', 'safari_date', 'safari_vehicle_details']);
        });
    }
};
