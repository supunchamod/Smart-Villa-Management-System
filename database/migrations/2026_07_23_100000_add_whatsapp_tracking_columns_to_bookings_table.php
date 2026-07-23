<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tracks whether each scheduled WhatsApp notification has already
     * gone out for a booking, so the daily bookings:send-whatsapp-notifications
     * command never sends the same reminder twice.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('payment_method');
            $table->timestamp('checkin_info_sent_at')->nullable()->after('reminder_sent_at');
            $table->timestamp('review_request_sent_at')->nullable()->after('checkin_info_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['reminder_sent_at', 'checkin_info_sent_at', 'review_request_sent_at']);
        });
    }
};
