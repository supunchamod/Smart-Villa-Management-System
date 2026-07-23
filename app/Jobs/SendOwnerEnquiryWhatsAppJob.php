<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\Setting;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOwnerEnquiryWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    private readonly int $bookingId;

    /**
     * Accepts either an already-loaded Booking or just its id - only the
     * id is kept, and the booking is re-fetched fresh in handle(), same
     * pattern as SendBookingWhatsAppJob.
     */
    public function __construct(Booking|int $booking)
    {
        $this->bookingId = $booking instanceof Booking ? $booking->id : $booking;
    }

    public function handle(WhatsAppService $whatsapp): void
    {
        $booking = Booking::with('room')->find($this->bookingId);

        if (! $booking) {
            Log::error('SendOwnerEnquiryWhatsAppJob: booking no longer exists', ['booking_id' => $this->bookingId]);

            return;
        }

        $settings = Setting::current();
        $ownerPhone = $settings->public_whatsapp_number ?: $settings->phone_number;

        if (! $ownerPhone) {
            Log::error('SendOwnerEnquiryWhatsAppJob: villa has no WhatsApp/phone number configured in Settings', [
                'booking_id' => $booking->id,
            ]);

            return;
        }

        $sent = $whatsapp->sendTextMessage($ownerPhone, $this->message($booking, $settings));

        if (! $sent) {
            Log::error('SendOwnerEnquiryWhatsAppJob: WhatsAppService failed to send the enquiry alert', [
                'booking_id' => $booking->id,
            ]);
        }
    }

    /**
     * The WhatsApp text sent to the villa owner for a new public-website
     * enquiry, mirroring the summary the old wa.me deep link used to
     * pre-fill.
     */
    private function message(Booking $booking, Setting $settings): string
    {
        $reference = 'INQ-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT);
        $adults = (int) $booking->guests_adults;
        $children = (int) $booking->guests_children;

        $lines = [
            "🌿 New Booking Enquiry - {$settings->villa_name}",
            '',
            "Reference: {$reference}",
            "Guest: {$booking->customer_name}",
            "Phone: {$booking->customer_phone}",
            "Room: {$booking->room->name_or_number}",
            'Check-in: '.$booking->check_in->format('d M Y'),
            'Check-out: '.$booking->check_out->format('d M Y'),
            'Guests: '.$adults.' Adult'.($adults === 1 ? '' : 's').($children > 0 ? ", {$children} Child".($children === 1 ? '' : 'ren') : ''),
            'Board Type: '.($booking->board_type_label ?: '—'),
        ];

        $addons = array_filter([
            $booking->bbq_addon ? 'BBQ & Campfire Experience' : null,
            $booking->safari_jeep_addon ? 'Safari Jeep Arrangement' : null,
            $booking->outdoor_dining_preference ? 'Outdoor Dining Preference' : null,
        ]);

        if ($addons !== []) {
            $lines[] = 'Add-ons Requested: '.implode(', ', $addons);
        }

        $lines[] = '';
        $lines[] = "Estimated Total: {$settings->currency} ".number_format((float) $booking->total_amount, 2);
        $lines[] = '';
        $lines[] = 'Please review and confirm this booking from the admin dashboard.';

        return implode("\n", $lines);
    }
}
