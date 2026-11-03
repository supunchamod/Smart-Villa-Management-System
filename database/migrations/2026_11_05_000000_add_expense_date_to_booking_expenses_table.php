<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The day a booking cost was incurred (defaults to the day it is
     * recorded). Profit reports are dated by this, so a cost entered late
     * can still be booked against the right day. Existing rows are
     * backfilled from their created_at.
     */
    public function up(): void
    {
        Schema::table('booking_expenses', function (Blueprint $table) {
            $table->date('expense_date')->nullable()->after('amount');
            $table->index('expense_date');
        });

        DB::table('booking_expenses')->whereNull('expense_date')->update(['expense_date' => DB::raw('DATE(created_at)')]);
    }

    public function down(): void
    {
        Schema::table('booking_expenses', function (Blueprint $table) {
            $table->dropIndex(['expense_date']);
            $table->dropColumn('expense_date');
        });
    }
};
