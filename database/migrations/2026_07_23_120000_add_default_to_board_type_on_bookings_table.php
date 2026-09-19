<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * board_type already exists (added nullable, no default, by
     * 2026_07_23_030520_add_public_booking_fields_to_bookings_table) for
     * the public guest booking flow, which always sets it explicitly.
     * The internal admin "Add/Edit Booking" form now exposes it too, so
     * every booking - not just public ones - should land on a sane
     * baseline ('cabana_only', i.e. "Room Only") when nothing else is
     * chosen, rather than staying null.
     *
     * Changing a column requires doctrine/dbal on connections other than
     * SQLite - see composer.json (same as the migration that first added
     * this column).
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('board_type')->default('cabana_only')->nullable()->change();
        });

        // Backfill existing rows (created before this feature reached the
        // admin form) so every booking - not just new ones - shows a
        // board type badge instead of a blank one.
        DB::table('bookings')->whereNull('board_type')->update(['board_type' => 'cabana_only']);
    }

    /**
     * Reverse the migrations.
     *
     * Drops the default only - the backfilled rows are left as
     * 'cabana_only' rather than reverted to null, since that backfill
     * reflects real (if previously unset) bookings, not test data.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('board_type')->nullable()->default(null)->change();
        });
    }
};
