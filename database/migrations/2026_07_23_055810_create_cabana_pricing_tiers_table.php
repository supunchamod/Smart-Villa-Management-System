<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per guest-count bracket for a cabana type, e.g.
     * min_pax=1,max_pax=2 / min_pax=3,max_pax=4 / etc. Each bracket
     * carries its own complete nightly rate per board type - these are
     * full rates set directly by the owner, not a base rate plus a
     * computed meal supplement, so Half Board and Full Board can be
     * priced however the owner actually charges for them. Any of the
     * three price columns can be left null if that board type isn't
     * offered for that bracket.
     */
    public function up(): void
    {
        Schema::create('cabana_pricing_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabana_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('min_pax');
            $table->unsignedTinyInteger('max_pax');
            $table->decimal('cabana_only_price', 10, 2)->nullable();
            $table->decimal('half_board_price', 10, 2)->nullable();
            $table->decimal('full_board_price', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cabana_pricing_tiers');
    }
};
