<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Value add-on requests from the public booking calculator (BBQ &
     * campfire setup, safari jeep arrangement, outdoor dining preference).
     * These are request-only toggles the owner sees on the WhatsApp
     * enquiry and the booking record - they don't add to the calculated
     * total, since none of them have an owner-set price yet.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('bbq_addon')->default(false)->after('guests_children');
            $table->boolean('safari_jeep_addon')->default(false)->after('bbq_addon');
            $table->boolean('outdoor_dining_preference')->default(false)->after('safari_jeep_addon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['bbq_addon', 'safari_jeep_addon', 'outdoor_dining_preference']);
        });
    }
};
