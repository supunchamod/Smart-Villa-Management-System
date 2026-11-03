<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extra details for Safari-Only bookings: where/when the guests are
     * collected, the group size, and the optional lunch / drone-video add-ons.
     * Left empty (and false) for bookings of any other type.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('pickup_location')->nullable()->after('safari_vehicle_details');
            $table->time('pickup_time')->nullable()->after('pickup_location');
            $table->unsignedSmallInteger('group_pax_size')->nullable()->after('pickup_time');
            $table->boolean('include_lunch')->default(false)->after('group_pax_size');
            $table->boolean('include_drone_videos')->default(false)->after('include_lunch');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['pickup_location', 'pickup_time', 'group_pax_size', 'include_lunch', 'include_drone_videos']);
        });
    }
};
