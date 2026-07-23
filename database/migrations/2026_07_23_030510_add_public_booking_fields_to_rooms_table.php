<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // Guest capacity and a single representative photo (an image
            // URL rather than an uploaded file - there's no file-storage
            // pipeline for rooms yet) for the public villa booking page.
            $table->unsignedTinyInteger('capacity')->default(2)->after('price_per_night');
            $table->string('photo_url')->nullable()->after('capacity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['capacity', 'photo_url']);
        });
    }
};
