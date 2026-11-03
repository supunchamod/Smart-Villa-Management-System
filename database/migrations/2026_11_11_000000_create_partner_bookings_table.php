<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Full Package bookings are fulfilled by an external partner (resort /
     * hotel / cabana). One partner_bookings row tracks what the villa agreed
     * to pay that partner, the advance it paid, and the final settlement.
     * Every payment is mirrored as a direct expense on the guest booking
     * (expenses.partner_booking_id marks those rows so they stay in sync).
     */
    public function up(): void
    {
        Schema::create('partner_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('partner_name')->nullable();
            $table->decimal('agreed_cost', 10, 2)->nullable();
            $table->decimal('advance_paid', 10, 2)->default(0);
            $table->date('advance_paid_on')->nullable();
            $table->decimal('final_paid', 10, 2)->default(0);
            $table->date('settled_on')->nullable();
            // pending_advance -> advance_paid -> settled
            $table->string('status')->default('pending_advance')->index();
            $table->timestamps();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('partner_booking_id')->nullable()->after('booking_id')->constrained('partner_bookings')->nullOnDelete();
            $table->string('partner_stage')->nullable()->after('partner_booking_id'); // advance | final
        });

        // Existing Full Package bookings enter the module as pending advance.
        DB::table('bookings')->where('booking_type', 'full_package')->orderBy('id')->each(function ($booking) {
            DB::table('partner_bookings')->insert([
                'booking_id' => $booking->id,
                'status' => 'pending_advance',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partner_booking_id');
            $table->dropColumn('partner_stage');
        });

        Schema::dropIfExists('partner_bookings');
    }
};
