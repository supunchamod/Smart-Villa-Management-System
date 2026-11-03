<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Log;

/**
 * Guard for every *automated* WhatsApp dispatch fired from a web request.
 *
 * Skips the dispatch entirely when WHATSAPP_AUTO_SEND is off, and swallows
 * (but logs) any exception from dispatching - e.g. the queue database
 * being unavailable, or a sync queue running the job inline against an
 * unreachable WAHA - so a notification problem can never turn an
 * already-saved booking, confirmation or checkout into a 500 error.
 */
class AutomatedWhatsApp
{
    public static function dispatch(Closure $dispatch, string $trigger, int $bookingId): void
    {
        if (! config('waha.auto_send', false)) {
            Log::debug("WhatsApp auto-send disabled; skipping automated {$trigger} message.", [
                'booking_id' => $bookingId,
            ]);

            return;
        }

        try {
            $dispatch();
        } catch (\Throwable $e) {
            Log::error("WhatsApp: failed to dispatch automated {$trigger} message.", [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
