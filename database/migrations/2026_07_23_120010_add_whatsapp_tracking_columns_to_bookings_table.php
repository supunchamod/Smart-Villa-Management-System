<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Records when each click-to-send WhatsApp message was last sent for
     * a booking, so the admin UI can show "already sent" status badges
     * instead of leaving staff to guess whether a guest was messaged.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('wa_confirmation_sent_at')->nullable()->after('payment_method');
            $table->timestamp('wa_reminder_sent_at')->nullable()->after('wa_confirmation_sent_at');
            $table->timestamp('wa_checkin_sent_at')->nullable()->after('wa_reminder_sent_at');
            $table->timestamp('wa_thankyou_sent_at')->nullable()->after('wa_checkin_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'wa_confirmation_sent_at',
                'wa_reminder_sent_at',
                'wa_checkin_sent_at',
                'wa_thankyou_sent_at',
            ]);
        });
    }
};
