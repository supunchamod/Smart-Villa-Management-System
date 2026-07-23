<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Turns the public villa landing page into an admin-configurable
     * mini-CMS: branding (a distinct logo/hero image from the internal
     * admin logo, since the public site may want different imagery),
     * hero copy, the WhatsApp number bookings are sent to, and the two
     * meal-plan supplement rates, all editable from Settings instead of
     * being hardcoded controller constants.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('website_logo_url')->nullable()->after('villa_logo');
            $table->string('website_hero_image_url')->nullable()->after('website_logo_url');
            $table->string('website_hero_title')->nullable()->after('website_hero_image_url');
            $table->string('website_hero_subtitle', 500)->nullable()->after('website_hero_title');
            $table->string('public_whatsapp_number')->nullable()->after('phone_number');
            $table->decimal('half_board_rate', 10, 2)->nullable()->after('currency');
            $table->decimal('full_board_rate', 10, 2)->nullable()->after('half_board_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'website_logo_url',
                'website_hero_image_url',
                'website_hero_title',
                'website_hero_subtitle',
                'public_whatsapp_number',
                'half_board_rate',
                'full_board_rate',
            ]);
        });
    }
};
