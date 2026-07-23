<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Widens the status enum with 'pending' - the state a booking is
     * created in when it comes from the public villa page, before the
     * owner has reviewed and confirmed it (see PublicBookingController).
     * Deliberately NOT included in the room/date overlap check used
     * elsewhere (Booking::scopeUpcoming and BookingController's
     * validateBooking both only exclude 'cancelled'), so a pending public
     * enquiry never blocks another guest from submitting one for the same
     * dates - the owner resolves any real conflict manually when
     * confirming one and cancelling the other from the admin bookings
     * list, at which point the existing overlap check protects them from
     * double-confirming into a clash.
     *
     * Changing an enum's allowed values requires doctrine/dbal on
     * connections other than SQLite - see composer.json.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('status', ['pending', 'confirmed', 'checked_out', 'cancelled'])
                ->default('confirmed')
                ->change();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('board_type')->nullable()->after('status');
            $table->json('selected_menu_items')->nullable()->after('board_type');
            $table->unsignedTinyInteger('guests_adults')->nullable()->after('selected_menu_items');
            $table->unsignedTinyInteger('guests_children')->nullable()->after('guests_adults');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['board_type', 'selected_menu_items', 'guests_adults', 'guests_children']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('status', ['confirmed', 'checked_out', 'cancelled'])
                ->default('confirmed')
                ->change();
        });
    }
};
