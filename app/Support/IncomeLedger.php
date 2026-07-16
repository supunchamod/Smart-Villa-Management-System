<?php

namespace App\Support;

use App\Models\Booking;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Synthesizes the villa's cash-in events from bookings. There is no
 * separate payments table - every non-cancelled booking contributes an
 * "Advance Payment" event (recorded when the booking was made) and,
 * once checked out, a "Final Settlement" event (recorded at checkout).
 * Income, Profit Analyzer, and the PDF Reports module all read from here
 * so the three stay in agreement.
 */
class IncomeLedger
{
    /**
     * @return Collection<int, array{type: string, date: \Illuminate\Support\Carbon, amount: float, booking: Booking}>
     */
    public static function events(): Collection
    {
        return Booking::with('room')
            ->where('status', '!=', 'cancelled')
            ->get()
            ->flatMap(function (Booking $booking) {
                $events = collect();

                if ((float) $booking->advance_payment > 0) {
                    $events->push([
                        'type' => 'Advance Payment',
                        'date' => $booking->created_at->copy(),
                        'amount' => (float) $booking->advance_payment,
                        'booking' => $booking,
                    ]);
                }

                if ($booking->status === 'checked_out' && (float) $booking->final_settlement_amount > 0) {
                    $events->push([
                        'type' => 'Final Settlement',
                        'date' => ($booking->checked_out_at ?? $booking->updated_at)->copy(),
                        'amount' => (float) $booking->final_settlement_amount,
                        'booking' => $booking,
                    ]);
                }

                return $events;
            });
    }

    /**
     * All cash-in events with a date inside the given inclusive range,
     * sorted oldest to newest.
     *
     * @return Collection<int, array{type: string, date: \Illuminate\Support\Carbon, amount: float, booking: Booking}>
     */
    public static function between(CarbonInterface $start, CarbonInterface $end): Collection
    {
        return static::events()
            ->filter(fn (array $event) => $event['date']->gte($start) && $event['date']->lte($end))
            ->sortBy('date')
            ->values();
    }
}
