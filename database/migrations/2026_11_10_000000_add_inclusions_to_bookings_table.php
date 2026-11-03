<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Services / features included in the booking (Breakfast, Safari Tour,
     * or any custom item staff typed in), stored as a JSON list of names and
     * printed on the guest's invoice under "Includes / Services Provided".
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->json('inclusions')->nullable()->after('special_notes');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('inclusions');
        });
    }
};
