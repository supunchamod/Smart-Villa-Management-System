<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The guest-facing details the WhatsApp click-to-send templates need
     * beyond what already exists (public_whatsapp_number was added
     * earlier for the public booking page's enquiry link): where the
     * villa is, the WiFi credentials to hand guests on arrival, and the
     * review links to ask for after checkout.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('google_map_link')->nullable()->after('public_whatsapp_number');
            $table->string('wifi_name')->nullable()->after('google_map_link');
            $table->string('wifi_password')->nullable()->after('wifi_name');
            $table->string('google_review_link')->nullable()->after('wifi_password');
            $table->string('tripadvisor_link')->nullable()->after('google_review_link');
            $table->string('social_media_link')->nullable()->after('tripadvisor_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'google_map_link',
                'wifi_name',
                'wifi_password',
                'google_review_link',
                'tripadvisor_link',
                'social_media_link',
            ]);
        });
    }
};
