<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A safari-only booking or full package can include several safari tours
     * on different days (e.g. Baker's Bend on Nov 2, Devil's Staircase on
     * Nov 3). One row per trip: its own date, package, pickup time and pax.
     *
     * bookings.safari_tour_name / safari_date / pickup_time / group_pax_size
     * stay as a derived summary of these trips (kept in sync by the
     * controller) so lists, searches and labels keep working.
     */
    public function up(): void
    {
        Schema::create('booking_safari_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->date('safari_date');
            $table->string('tour_name');
            $table->time('pickup_time')->nullable();
            $table->unsignedSmallInteger('pax_count')->default(1);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'safari_date']);
            $table->index('safari_date');
        });

        // Existing safari / package bookings become a single trip.
        DB::table('bookings')
            ->whereIn('booking_type', ['safari_only', 'full_package'])
            ->whereNotNull('safari_date')
            ->orderBy('id')
            ->each(function ($booking) {
                DB::table('booking_safari_trips')->insert([
                    'booking_id' => $booking->id,
                    'safari_date' => $booking->safari_date,
                    'tour_name' => $booking->safari_tour_name ?: 'Safari',
                    'pickup_time' => $booking->pickup_time,
                    'pax_count' => max(1, (int) $booking->group_pax_size),
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_safari_trips');
    }
};
