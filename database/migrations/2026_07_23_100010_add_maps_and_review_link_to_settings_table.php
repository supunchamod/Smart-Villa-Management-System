<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Two links the scheduled WhatsApp notifications need: where the
     * villa actually is (shared on check-in day) and where a guest can
     * leave a review (shared an hour after checkout). Both nullable -
     * the jobs that use them skip sending rather than send a broken/empty
     * link when the owner hasn't set one yet.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('google_maps_link')->nullable()->after('address');
            $table->string('review_link')->nullable()->after('public_whatsapp_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['google_maps_link', 'review_link']);
        });
    }
};
