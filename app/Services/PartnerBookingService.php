<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Expense;
use App\Models\PartnerBooking;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Owns the partner-resort workflow: registering a Full Package booking,
 * recording the advance, and settling the remainder. Every payment is
 * mirrored as a direct expense on the guest booking (one row per stage,
 * marked with partner_booking_id) so the booking's net profit is right and
 * the figures can never drift from the partner record.
 */
class PartnerBookingService
{
    /**
     * Keep the partner entry in step with the booking's type: a Full Package
     * always has one (starting as Pending Advance); an entry for a booking
     * that is no longer a package is dropped only if nothing was recorded.
     */
    public function syncRegistration(Booking $booking): ?PartnerBooking
    {
        if ($booking->booking_type === 'full_package') {
            return $booking->partnerBooking()->firstOrCreate([], ['status' => PartnerBooking::PENDING_ADVANCE]);
        }

        $entry = $booking->partnerBooking;

        if ($entry && $entry->status === PartnerBooking::PENDING_ADVANCE && ! $entry->partner_name) {
            $entry->delete();
        }

        return null;
    }

    /**
     * Save (or correct) the partner, the agreed cost and the advance paid.
     * Moves the entry to Advance Paid / Confirmed.
     *
     * @param  array{partner_name: string, agreed_cost: float|string, advance_amount: float|string, advance_date?: string|null}  $data
     */
    public function recordAdvance(PartnerBooking $entry, array $data): PartnerBooking
    {
        return DB::transaction(function () use ($entry, $data) {
            $advance = round((float) $data['advance_amount'], 2);
            $date = ! empty($data['advance_date']) ? $data['advance_date'] : today()->toDateString();

            $entry->update([
                'partner_name' => trim($data['partner_name']),
                'agreed_cost' => round((float) $data['agreed_cost'], 2),
                'advance_paid' => $advance,
                'advance_paid_on' => $date,
                'status' => PartnerBooking::ADVANCE_PAID,
            ]);

            $this->syncExpense($entry, 'advance', PartnerBooking::ADVANCE_CATEGORY, $advance, $date, "Advance to {$entry->partner_name}");

            return $entry->fresh();
        });
    }

    /**
     * Record the final payment to the partner and mark the entry Fully Settled.
     */
    public function settle(PartnerBooking $entry, float $amount, ?string $date = null): PartnerBooking
    {
        return DB::transaction(function () use ($entry, $amount, $date) {
            $amount = round($amount, 2);
            $date = $date ?: today()->toDateString();

            $entry->update([
                'final_paid' => $amount,
                'settled_on' => $date,
                'status' => PartnerBooking::SETTLED,
            ]);

            $this->syncExpense($entry, 'final', PartnerBooking::FINAL_CATEGORY, $amount, $date, "Final settlement to {$entry->partner_name}");

            return $entry->fresh();
        });
    }

    /**
     * Create / update / remove the booking expense for one stage. A zero
     * amount means nothing was paid at that stage, so no expense exists.
     */
    private function syncExpense(PartnerBooking $entry, string $stage, string $category, float $amount, CarbonInterface|string $date, string $description): void
    {
        $existing = Expense::query()
            ->where('partner_booking_id', $entry->id)
            ->where('partner_stage', $stage)
            ->first();

        if ($amount <= 0) {
            $existing?->delete();

            return;
        }

        $attributes = [
            'booking_id' => $entry->booking_id,
            'category' => $category,
            'description' => $description,
            'amount' => $amount,
            'expense_date' => $date,
        ];

        $existing
            ? $existing->update($attributes)
            : Expense::create($attributes + ['partner_booking_id' => $entry->id, 'partner_stage' => $stage]);
    }
}
