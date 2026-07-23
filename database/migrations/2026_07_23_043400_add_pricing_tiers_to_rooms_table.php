<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Lets a room price itself two ways: a flat price_per_night (unchanged
     * default), or - for cabanas like the Family Two-Story Cabana that
     * charge more as more guests stay - a JSON map of
     * {"max guests for this rate": rate}, e.g.
     * {"2": 12500, "4": 15000, "6": 20000, "8": 25000}. Null means "use the
     * flat rate", so every existing room keeps working unchanged.
     * photo_urls holds extra gallery images for the public page's photo
     * slider, on top of the single existing photo_url "cover" photo.
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->json('pricing_tiers')->nullable()->after('price_per_night');
            $table->json('photo_urls')->nullable()->after('photo_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['pricing_tiers', 'photo_urls']);
        });
    }
};
