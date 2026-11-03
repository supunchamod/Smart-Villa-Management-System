<?php

namespace App\Http\Controllers;

use App\Models\PartnerBooking;
use App\Services\PartnerBookingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Partner Resort Bookings": the external resort / hotel / cabana behind
 * each Full Package booking - agreed cost, advance paid, and the final
 * settlement once the guest has checked out.
 */
class PartnerBookingController extends Controller
{
    private const TABS = ['all', 'pending_advance', 'advance_paid', 'pending_final', 'settled'];

    public function __construct(private readonly PartnerBookingService $partners)
    {
    }

    public function index(Request $request): View
    {
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'all';
        $search = trim((string) $request->query('search', ''));

        $query = PartnerBooking::query()->with('booking.room');

        match ($tab) {
            'pending_advance' => $query->where('status', PartnerBooking::PENDING_ADVANCE),
            'advance_paid' => $query->where('status', PartnerBooking::ADVANCE_PAID)
                ->whereHas('booking', fn (Builder $booking) => $booking->where('status', '!=', 'checked_out')),
            'pending_final' => $query->pendingFinal(),
            'settled' => $query->where('status', PartnerBooking::SETTLED),
            default => null,
        };

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';

            $query->where(function (Builder $query) use ($like, $search) {
                $query->where('partner_name', 'like', $like)
                    ->orWhereHas('booking', function (Builder $booking) use ($like, $search) {
                        $booking->where('customer_name', 'like', $like)
                            ->orWhere('stay_hotel_name', 'like', $like)
                            ->orWhere('safari_tour_name', 'like', $like);

                        if (ctype_digit(ltrim($search, '#'))) {
                            $booking->orWhere('id', (int) ltrim($search, '#'));
                        }
                    });
            });
        }

        // Items needing action first: awaiting final settlement, then pending advance.
        $entries = $query
            ->orderByRaw("CASE status WHEN 'advance_paid' THEN 0 WHEN 'pending_advance' THEN 1 ELSE 2 END")
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $outstanding = (float) PartnerBooking::query()
            ->where('status', PartnerBooking::ADVANCE_PAID)
            ->selectRaw('COALESCE(SUM(CASE WHEN agreed_cost IS NULL THEN 0 ELSE agreed_cost - advance_paid END), 0) as owed')
            ->value('owed');

        return view('partner-bookings.index', [
            'entries' => $entries,
            'tab' => $tab,
            'search' => $search,
            'counts' => [
                'all' => PartnerBooking::count(),
                'pending_advance' => PartnerBooking::where('status', PartnerBooking::PENDING_ADVANCE)->count(),
                'advance_paid' => PartnerBooking::where('status', PartnerBooking::ADVANCE_PAID)
                    ->whereHas('booking', fn (Builder $booking) => $booking->where('status', '!=', 'checked_out'))->count(),
                'pending_final' => PartnerBooking::pendingFinal()->count(),
                'settled' => PartnerBooking::where('status', PartnerBooking::SETTLED)->count(),
            ],
            'outstanding' => round($outstanding, 2),
        ]);
    }

    /**
     * Record the partner, the agreed cost and the advance paid. Moves the
     * entry to Advance Paid / Confirmed and books the advance as a direct
     * expense on the guest booking. Can be repeated to correct the figures
     * until the entry is fully settled.
     */
    public function recordAdvance(Request $request, PartnerBooking $partnerBooking): RedirectResponse
    {
        if ($partnerBooking->status === PartnerBooking::SETTLED) {
            return back()->with('error', 'This partner booking is fully settled and can no longer be changed.');
        }

        // The partner is the booking's stay hotel (no input). The total agreed cost is optional here
        // and can be added later; only when given must the advance fit within it.
        $validated = $request->validate([
            'agreed_cost' => ['nullable', 'numeric', 'min:0.01', 'max:99999999'],
            'advance_amount' => array_filter([
                'required', 'numeric', 'min:0.01', 'max:99999999',
                $request->filled('agreed_cost') ? 'lte:agreed_cost' : null,
            ]),
            'advance_date' => ['nullable', 'date', 'before_or_equal:today'],
        ], [
            'advance_amount.lte' => 'The advance cannot be more than the total agreed cost.',
        ]);

        $this->partners->recordAdvance($partnerBooking, $validated);

        return back()->with('status', 'Advance recorded. Booking #'.$partnerBooking->booking_id.' is now Advance Paid / Confirmed.');
    }

    /**
     * Pay the partner the remaining balance once the guest has checked out,
     * mark the entry Fully Settled and book the payment as a direct expense.
     */
    public function settle(Request $request, PartnerBooking $partnerBooking): RedirectResponse
    {
        if ($partnerBooking->status === PartnerBooking::SETTLED) {
            return back()->with('error', 'This partner booking is already fully settled.');
        }

        if ($partnerBooking->status !== PartnerBooking::ADVANCE_PAID) {
            return back()->with('error', 'Record the partner advance before settling the final payment.');
        }

        if ($partnerBooking->booking?->status !== 'checked_out') {
            return back()->with('error', 'The final settlement can be recorded once the guest has checked out.');
        }

        // If the total agreed cost was never entered, it has to be given now (and cover the advance).
        $validated = $request->validate([
            'agreed_cost' => [
                $partnerBooking->agreed_cost === null ? 'required' : 'nullable',
                'numeric', 'min:'.max(0.01, (float) $partnerBooking->advance_paid), 'max:99999999',
            ],
            'final_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'settled_date' => ['nullable', 'date', 'before_or_equal:today'],
        ], [
            'agreed_cost.required' => 'Enter the total agreed cost to settle this booking.',
            'agreed_cost.min' => 'The total agreed cost cannot be less than the advance already paid.',
        ]);

        $this->partners->settle(
            $partnerBooking,
            (float) $validated['final_amount'],
            $validated['settled_date'] ?? null,
            isset($validated['agreed_cost']) ? (float) $validated['agreed_cost'] : null,
        );

        return back()->with('status', 'Final settlement recorded. Partner booking #'.$partnerBooking->booking_id.' is Fully Settled.');
    }
}
