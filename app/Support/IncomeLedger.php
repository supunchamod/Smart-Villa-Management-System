<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\BookingExpense;
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

    /**
     * True net profit per booking:
     *
     *   net profit = revenue (room/package charge + extra charges)
     *                - direct costs recorded against that booking
     *
     * A booking is included when it had cash-in activity or a direct cost
     * dated inside the range, but its figures are the booking's own
     * lifetime totals - revenue and costs are never split by period, so the
     * result is the real margin on that stay. Cancelled bookings earn no
     * revenue but are still listed if they carry costs.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function bookingProfitability(CarbonInterface $start, CarbonInterface $end): Collection
    {
        $incomeIds = static::between($start, $end)->map(fn (array $event) => $event['booking']->id);

        $costIds = BookingExpense::query()
            ->whereDate('expense_date', '>=', $start->toDateString())
            ->whereDate('expense_date', '<=', $end->toDateString())
            ->pluck('booking_id');

        $ids = $incomeIds->concat($costIds)->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Booking::with(['room', 'extras', 'bookingExpenses'])
            ->whereIn('id', $ids)
            ->get()
            ->map(function (Booking $booking) {
                $cancelled = $booking->status === 'cancelled';
                $roomCharges = $cancelled ? 0.0 : (float) $booking->total_amount;
                $extrasTotal = $cancelled ? 0.0 : $booking->extras_total;
                $revenue = round($roomCharges + $extrasTotal, 2);
                $costs = $booking->booking_expenses_total;
                $net = round($revenue - $costs, 2);

                return [
                    'booking' => $booking,
                    'date' => $booking->check_out ?? $booking->check_in,
                    'room_charges' => round($roomCharges, 2),
                    'extras_total' => round($extrasTotal, 2),
                    'revenue' => $revenue,
                    'costs' => $costs,
                    'expenses' => $booking->bookingExpenses->map(fn (BookingExpense $expense) => [
                        'category' => $expense->category,
                        'description' => $expense->description,
                        'amount' => (float) $expense->amount,
                        'date' => $expense->incurred_on,
                    ])->values(),
                    'net_profit' => $net,
                    'margin' => $revenue > 0 ? round(($net / $revenue) * 100, 1) : 0.0,
                ];
            })
            ->sortByDesc(fn (array $row) => $row['date'])
            ->values();
    }

    /**
     * Revenue / direct costs / net profit totals for a bookingProfitability() result.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{revenue: float, costs: float, net_profit: float}
     */
    public static function profitabilityTotals(Collection $rows): array
    {
        $revenue = round((float) $rows->sum('revenue'), 2);
        $costs = round((float) $rows->sum('costs'), 2);

        return ['revenue' => $revenue, 'costs' => $costs, 'net_profit' => round($revenue - $costs, 2)];
    }
}
