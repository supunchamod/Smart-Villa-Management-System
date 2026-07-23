<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\Setting;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendBookingWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Booking statuses that mean the stay is complete and paid in full,
     * so the "final" stage (final invoice + "Paid in Full" caption)
     * applies. Booking::status never actually stores 'completed' today
     * (only 'checked_out'), but the check tolerates it too in case that
     * naming is ever adopted.
     */
    private const FINAL_STAGE_STATUSES = ['completed', 'checked_out'];

    public int $tries = 3;

    public int $backoff = 30;

    private readonly int $bookingId;

    /**
     * Accepts either an already-loaded Booking or just its id - only the
     * id is kept, and the booking is re-fetched fresh in handle(), since
     * the job may run minutes after dispatch and its status (confirmed vs
     * checked_out) decides which invoice stage gets attached.
     */
    public function __construct(Booking|int $booking)
    {
        $this->bookingId = $booking instanceof Booking ? $booking->id : $booking;
    }

    public function handle(WhatsAppService $whatsapp): void
    {
        $booking = Booking::with('room')->find($this->bookingId);

        if (! $booking) {
            Log::error('SendBookingWhatsAppJob: booking no longer exists', ['booking_id' => $this->bookingId]);

            return;
        }

        if (! $booking->customer_phone) {
            Log::error('SendBookingWhatsAppJob: booking has no phone number on file', ['booking_id' => $booking->id]);

            return;
        }

        $stage = in_array($booking->status, self::FINAL_STAGE_STATUSES, true) ? 'final' : 'confirmation';

        $invoicePath = $this->resolveInvoicePath($booking, $stage);

        if (! $invoicePath) {
            Log::error('SendBookingWhatsAppJob: failed to generate invoice PDF', ['booking_id' => $booking->id]);

            return;
        }

        $sent = $whatsapp->sendDocument(
            $booking->customer_phone,
            $invoicePath,
            basename($invoicePath),
            $this->caption($booking, $stage)
        );

        if (! $sent) {
            Log::error('SendBookingWhatsAppJob: WhatsAppService failed to send the invoice document', [
                'booking_id' => $booking->id,
                'invoice_path' => $invoicePath,
            ]);
        }
    }

    /**
     * Returns the absolute local path to the booking's invoice PDF under
     * storage/app/public/invoices, generating it there first if needed.
     * The confirmation and final invoices are always different files
     * (invoice-confirmation-{id}.pdf vs invoice-final-{id}.pdf), so
     * there's never a mix-up between stages - but the final invoice is
     * always regenerated fresh rather than reusing a cached file, since
     * it reflects checkout-time data (settlement amount, payment method)
     * that can change between when it was first generated and a later
     * resend. The confirmation invoice is safe to cache, since nothing
     * about it changes once it exists.
     */
    private function resolveInvoicePath(Booking $booking, string $stage): ?string
    {
        $relativePath = "invoices/invoice-{$stage}-{$booking->id}.pdf";
        $forceRegenerate = $stage === 'final';

        try {
            if ($forceRegenerate || ! Storage::disk('public')->exists($relativePath)) {
                $pdf = Pdf::loadView('invoices.pdf', ['booking' => $booking, 'stage' => $stage])
                    ->setPaper('a4', 'portrait');

                Storage::disk('public')->put($relativePath, $pdf->output());
            }
        } catch (\Throwable $e) {
            Log::error('SendBookingWhatsAppJob: invoice PDF generation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        return Storage::disk('public')->path($relativePath);
    }

    /**
     * The WhatsApp caption sent alongside the invoice PDF.
     */
    private function caption(Booking $booking, string $stage): string
    {
        $settings = Setting::current();
        $currency = $settings->currency ?: 'LKR';
        $isFinal = $stage === 'final';

        $lines = [
            "🌿 *{$settings->villa_name}*",
            '',
            '✅ Hi '.$booking->customer_name.', your booking is '.($isFinal ? 'complete' : 'confirmed').'!',
            '',
            "🏡 Room: {$booking->room->name_or_number}",
            '📅 Check-in: '.$booking->check_in->format('d M Y'),
            '📅 Check-out: '.$booking->check_out->format('d M Y'),
            "💰 Total: {$currency} ".number_format((float) $booking->total_amount, 2),
        ];

        if ($isFinal) {
            $lines[] = "💳 Paid in Full - Thank you!";
        } else {
            $lines[] = "💳 Balance Due: {$currency} ".number_format($booking->remaining_balance, 2);
        }

        $lines[] = '';
        $lines[] = '📄 Your '.($isFinal ? 'final invoice' : 'booking confirmation').' is attached.';
        $lines[] = '';
        $lines[] = "Thank you for choosing {$settings->villa_name}! 🌿";

        return implode("\n", $lines);
    }
}
