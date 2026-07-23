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

class SendPostCheckoutReviewWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    private readonly int $bookingId;

    /**
     * Accepts either an already-loaded Booking or just its id - only the
     * id is kept, and the booking is re-fetched fresh in handle(), since
     * this job is dispatched with a delay and the booking's state may
     * have moved on by the time it actually runs.
     */
    public function __construct(Booking|int $booking)
    {
        $this->bookingId = $booking instanceof Booking ? $booking->id : $booking;
    }

    public function handle(WhatsAppService $whatsapp): void
    {
        $booking = Booking::find($this->bookingId);

        if (! $booking) {
            Log::error('SendPostCheckoutReviewWhatsAppJob: booking no longer exists', ['booking_id' => $this->bookingId]);

            return;
        }

        if ($booking->status !== 'checked_out') {
            // The booking was reopened/edited before this delayed job ran -
            // don't ask for a review on a stay that's no longer checked out.
            return;
        }

        if ($booking->review_request_sent_at) {
            // Already sent - guards against a duplicate if the job is
            // ever delivered twice.
            return;
        }

        if (! $booking->customer_phone) {
            Log::error('SendPostCheckoutReviewWhatsAppJob: booking has no phone number on file', ['booking_id' => $booking->id]);

            return;
        }

        $settings = Setting::current();

        if (! $settings->review_link) {
            Log::error('SendPostCheckoutReviewWhatsAppJob: no review link configured in Settings', ['booking_id' => $booking->id]);

            return;
        }

        $sent = $whatsapp->sendTextMessage($booking->customer_phone, $this->message($booking, $settings));

        if ($sent) {
            $booking->update(['review_request_sent_at' => now()]);
        } else {
            Log::error('SendPostCheckoutReviewWhatsAppJob: WhatsAppService failed to send the review request', [
                'booking_id' => $booking->id,
            ]);
        }
    }

    private function message(Booking $booking, Setting $settings): string
    {
        return implode("\n", [
            "🌿 *{$settings->villa_name}*",
            '',
            "Hi {$booking->customer_name}, thank you for staying with us! We hope you had a wonderful time.",
            '',
            '⭐ We would love to hear your feedback! Please take a moment to leave us a review:',
            $settings->review_link,
            '',
            'Hope to see you again soon!',
        ]);
    }
}
