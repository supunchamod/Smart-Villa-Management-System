<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Selectable menu items for the public booking calculator's meal
     * plan step, grouped by which sitting they belong to. Global (not
     * per-cabana-type) since the villa serves the same menu regardless
     * of which cabana a guest books.
     */
    public function up(): void
    {
        Schema::create('landing_page_menus', function (Blueprint $table) {
            $table->id();
            $table->string('meal_type')->index();
            $table->string('item_name');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_page_menus');
    }
};
