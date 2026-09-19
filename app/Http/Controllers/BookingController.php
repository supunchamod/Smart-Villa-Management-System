<?php

namespace App\Http\Controllers;

use App\Jobs\SendBookingWhatsAppJob;
use App\Jobs\SendPostCheckoutReviewWhatsAppJob;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends Controller
{
    /**
     * Status -> calendar color, kept in one place so the list badges and
     * the FullCalendar feed always agree.
     */
    private const STATUS_COLORS = [
        'pending' => '#f6a642',
        'confirmed' => '#5278ff',
        'checked_out' => '#2fa84f',
        'cancelled' => '#dc2626',
    ];

    /**
     * Display a listing of the villa's bookings.
     *
     * Sorted by newest-created first (not by check_in date) so a booking
     * just added or submitted always lands at the top of the table,
     * regardless of how far out its stay is.
     *
     * The quick-filter bar's counts and the KPI cards are always computed
     * across every booking (not just the current page or the active
     * search/filter), so they stay accurate no matter what's currently
     * showing in the table.
     */
    public function index(Request $request): View
    {
        $today = today();
        $tomorrow = $today->copy()->addDay();

        // Only 'all' / 'active' / 'balance_due' / 'history' are exposed as
        // filter pills in the UI, but 'pending' stays a valid, directly
        // linkable value so the "pending enquiries" alert can deep-link
        // into it without adding a fifth pill.
        $tab = in_array($request->query('tab'), ['all', 'active', 'balance_due', 'history', 'pending'], true)
            ? $request->query('tab')
            : 'all';

        $range = in_array($request->query('range'), ['today', 'week', 'month'], true)
            ? $request->query('range')
            : null;

        $bookingsQuery = Booking::query()->with('room')->latest();

        match ($tab) {
            'active' => $bookingsQuery->where('status', 'confirmed'),
            'balance_due' => $bookingsQuery->where('status', 'confirmed')->whereColumn('advance_payment', '<', 'total_amount'),
            'history' => $bookingsQuery->whereIn('status', ['checked_out', 'cancelled']),
            'pending' => $bookingsQuery->where('status', 'pending'),
            default => null,
        };

        if ($search = trim((string) $request->query('search', ''))) {
            $bookingsQuery->where(function ($query) use ($search) {
                $query->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");

                if (ctype_digit($search)) {
                    $query->orWhere('id', (int) $search);
                }
            });
        }

        if ($roomType = $request->query('room_type')) {
            $bookingsQuery->whereHas('room', fn ($query) => $query->where('type', $roomType));
        }

        $type = in_array($request->query('type'), array_keys(Booking::BOOKING_TYPES), true)
            ? $request->query('type')
            : 'all';

        if ($type !== 'all') {
            $bookingsQuery->where('booking_type', $type);
        }

        match ($range) {
            'today' => $bookingsQuery->whereDate('check_in', $today),
            'week' => $bookingsQuery->whereBetween('check_in', [$today, $today->copy()->addDays(7)]),
            'month' => $bookingsQuery->whereBetween('check_in', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()]),
            default => null,
        };

        return view('bookings.index', [
            'bookings' => $bookingsQuery->paginate(10)->withQueryString(),
            'activeTab' => $tab,
            'activeRange' => $range,
            'activeType' => $type,
            'search' => $request->query('search', ''),
            'roomTypes' => Room::query()->select('type')->distinct()->orderBy('type')->pluck('type'),
            'selectedRoomType' => $roomType ?? '',
            'pendingBookingsCount' => Booking::where('status', 'pending')->count(),
            'todayCheckInsCount' => Booking::where('status', 'confirmed')->where('booking_type', '!=', 'safari_only')->whereDate('check_in', $today)->count(),
            'todayCheckOutsCount' => Booking::where('status', 'confirmed')->where('booking_type', '!=', 'safari_only')->whereDate('check_out', $today)->count(),
            'tomorrowCheckInsCount' => Booking::where('status', 'confirmed')->where('booking_type', '!=', 'safari_only')->whereDate('check_in', $tomorrow)->count(),
            'tomorrowCheckOutsCount' => Booking::where('status', 'confirmed')->where('booking_type', '!=', 'safari_only')->whereDate('check_out', $tomorrow)->count(),
            'balanceDueCount' => Booking::where('status', 'confirmed')->whereColumn('advance_payment', '<', 'total_amount')->count(),
            'todayArrivals' => Booking::with('room')->where('status', 'confirmed')->where('booking_type', '!=', 'safari_only')->whereDate('check_in', $today)->orderBy('check_in')->get(),
            'todayDepartures' => Booking::with('room')->where('status', 'confirmed')->where('booking_type', '!=', 'safari_only')->whereDate('check_out', $today)->orderBy('check_out')->get(),
        ]);
    }

    /**
     * Show the form for creating a new booking.
     */
    public function create(): View
    {
        return view('bookings.create', [
            'rooms' => Room::orderBy('name_or_number')->get(),
        ]);
    }

    /**
     * Store a newly created booking.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateBooking($request);

        $booking = Booking::create($validated);

        // Only a confirmed booking gets an immediate WhatsApp confirmation
        // + invoice - a 'pending' one is still awaiting review via confirm().
        if ($booking->status === 'confirmed') {
            $this->dispatchAutomatedWhatsApp(
                fn () => SendBookingWhatsAppJob::dispatch($booking),
                'booking confirmation',
                $booking->id
            );
        }

        return redirect()->route('bookings.index')->with('status', 'Booking created successfully.');
    }

    /**
     * Display the given booking.
     */
    public function show(Booking $booking): View
    {
        return view('bookings.show', [
            'booking' => $booking->load('room'),
        ]);
    }

    /**
     * Show the form for editing the given booking.
     */
    public function edit(Booking $booking): View
    {
        return view('bookings.edit', [
            'booking' => $booking,
            'rooms' => Room::orderBy('name_or_number')->get(),
        ]);
    }

    /**
     * Update the given booking.
     */
    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $this->validateBooking($request, $booking);

        $booking->update($validated);

        return redirect()->route('bookings.index')->with('status', 'Booking updated successfully.');
    }

    /**
     * Remove the given booking.
     */
    public function destroy(Booking $booking): RedirectResponse
    {
        $booking->delete();

        return redirect()->route('bookings.index')->with('status', 'Booking deleted successfully.');
    }

    /**
     * Mark a confirmed booking as checked out, settling the balance. Also
     * callable via AJAX (e.g. the dashboard's inline checkout cards, and
     * the Manage Bookings "Checkout & Pay Balance" modal), which gets a
     * JSON reply instead of a redirect so the UI can update in place.
     */
    public function checkout(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        if ($booking->status !== 'confirmed') {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Only confirmed bookings can be checked out.'], 422);
            }

            return back()->with('error', 'Only confirmed bookings can be checked out.');
        }

        $validated = $request->validate([
            'payment_method' => ['nullable', 'string', Rule::in(['cash', 'card', 'bank_transfer'])],
        ]);

        $booking->update([
            'status' => 'checked_out',
            'final_settlement_amount' => (float) $booking->total_amount - (float) $booking->advance_payment,
            'checked_out_at' => now(),
            'payment_method' => $validated['payment_method'] ?? null,
        ]);

        // The booking is now marked as paid in full - send the final
        // invoice immediately, and queue a review request for an hour
        // from now, once the guest has actually had time to leave.
        $this->dispatchAutomatedWhatsApp(
            fn () => SendBookingWhatsAppJob::dispatch($booking),
            'checkout final invoice',
            $booking->id
        );
        $this->dispatchAutomatedWhatsApp(
            fn () => SendPostCheckoutReviewWhatsAppJob::dispatch($booking)->delay(now()->addHour()),
            'post-checkout review request',
            $booking->id
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status' => $booking->status,
                'final_invoice_url' => route('bookings.invoice.final', $booking),
                'message' => 'Booking checked out successfully. The final invoice is ready to download.',
            ]);
        }

        return redirect()->route('bookings.show', $booking)
            ->with('status', 'Booking checked out successfully. The final invoice is ready to download.');
    }

    /**
     * Accept a pending online booking request, moving it to 'confirmed'.
     * Also callable via AJAX for inline quick-action buttons.
     */
    public function confirm(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        if ($booking->status !== 'pending') {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Only pending requests can be confirmed.'], 422);
            }

            return back()->with('error', 'Only pending requests can be confirmed.');
        }

        $booking->update(['status' => 'confirmed']);

        $this->dispatchAutomatedWhatsApp(
            fn () => SendBookingWhatsAppJob::dispatch($booking),
            'booking acceptance',
            $booking->id
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status' => $booking->status,
                'message' => 'Booking confirmed successfully.',
            ]);
        }

        return back()->with('status', 'Booking confirmed successfully.');
    }

    /**
     * Decline a pending online booking request, moving it to 'cancelled'
     * rather than deleting it, so there's still a record of the enquiry.
     */
    public function decline(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        if ($booking->status !== 'pending') {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Only pending requests can be declined.'], 422);
            }

            return back()->with('error', 'Only pending requests can be declined.');
        }

        $booking->update(['status' => 'cancelled']);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => $booking->status,
                'message' => 'Booking declined.',
            ]);
        }

        return back()->with('status', 'Booking declined.');
    }

    /**
     * Manually (re)send a booking's WhatsApp confirmation/invoice from the
     * booking details page. Dispatches the same background job the
     * create/confirm/checkout flows fire automatically, so the message
     * always reflects the booking's current stage.
     *
     * Deliberately bypasses the WHATSAPP_AUTO_SEND gate (see
     * dispatchAutomatedWhatsApp() below) - this is an explicit manual
     * action, not an automated trigger, so it should always attempt to
     * send regardless of the auto-send policy. If WAHA itself is
     * unreachable the dispatched job still fails safely (logged, no
     * exception) rather than breaking this endpoint.
     */
    public function sendWhatsApp(Booking $booking): JsonResponse
    {
        if (in_array($booking->status, ['pending', 'cancelled'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'This booking must be confirmed before a WhatsApp message can be sent.',
            ], 422);
        }

        if (! $booking->customer_phone) {
            return response()->json([
                'success' => false,
                'message' => 'This booking has no phone number on file.',
            ], 422);
        }

        SendBookingWhatsAppJob::dispatch($booking);

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp dispatch initiated',
        ]);
    }

    /**
     * Stream the stage 1 confirmation invoice, showing the remaining balance due.
     */
    public function confirmationInvoice(Booking $booking): Response
    {
        $booking->load('room');

        return Pdf::loadView('invoices.pdf', [
            'booking' => $booking,
            'stage' => 'confirmation',
        ])->setPaper('a4', 'portrait')->stream("invoice-confirmation-{$booking->id}.pdf");
    }

    /**
     * Stream the stage 2 final invoice once the booking has been checked out.
     */
    public function finalInvoice(Booking $booking): Response|RedirectResponse
    {
        if ($booking->status !== 'checked_out') {
            return back()->with('error', 'This booking has not been checked out yet.');
        }

        $booking->load('room');

        return Pdf::loadView('invoices.pdf', [
            'booking' => $booking,
            'stage' => 'final',
        ])->setPaper('a4', 'portrait')->stream("invoice-final-{$booking->id}.pdf");
    }

    /**
     * JSON feed of the current villa's bookings, shaped for FullCalendar.
     */
    public function calendarFeed(): JsonResponse
    {
        $events = Booking::with('room')->get()->map(function (Booking $booking) {
            return [
                'id' => $booking->id,
                'title' => $booking->room_label.' — '.$booking->customer_name,
                'start' => $booking->check_in->toDateString(),
                'end' => $booking->check_out->toDateString(),
                'color' => self::STATUS_COLORS[$booking->status] ?? '#5278ff',
                'extendedProps' => [
                    'customer_name' => $booking->customer_name,
                    'customer_email' => $booking->customer_email,
                    'customer_phone' => $booking->customer_phone,
                    'room' => $booking->room_label,
                    'check_in' => $booking->check_in->toFormattedDateString(),
                    'check_out' => $booking->check_out->toFormattedDateString(),
                    'total_amount' => number_format((float) $booking->total_amount, 2),
                    'advance_payment' => number_format((float) $booking->advance_payment, 2),
                    'balance' => number_format($booking->remaining_balance, 2),
                    'status' => $booking->status,
                ],
            ];
        });

        return response()->json($events);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateBooking(Request $request, ?Booking $booking = null): array
    {
        // Safari fields only mean anything once the villa has switched the
        // module on - otherwise every booking is forced back to the
        // original accommodation-only shape, regardless of what a crafted
        // request sends.
        $bookingType = Setting::current()->is_safari_enabled
            ? $request->input('booking_type', 'accommodation_only')
            : 'accommodation_only';

        if (! array_key_exists($bookingType, Booking::BOOKING_TYPES)) {
            $bookingType = 'accommodation_only';
        }

        $needsRoom = $bookingType !== 'safari_only';
        $needsSafari = $bookingType !== 'accommodation_only';

        $validated = $request->validate([
            'room_id' => [$needsRoom ? 'required' : 'nullable', 'exists:rooms,id'],
            'check_in' => [$needsRoom ? 'required' : 'nullable', 'date'],
            'check_out' => array_filter([$needsRoom ? 'required' : 'nullable', 'date', $needsRoom ? 'after:check_in' : null]),
            'safari_tour_name' => [$needsSafari ? 'required' : 'nullable', 'string', 'max:255'],
            'safari_date' => [$needsSafari ? 'required' : 'nullable', 'date'],
            'safari_vehicle_details' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'advance_payment' => ['required', 'numeric', 'min:0', 'lte:total_amount'],
            'status' => [Rule::in(['pending', 'confirmed', 'checked_out', 'cancelled'])],
            'board_type' => ['nullable', Rule::in(array_keys(Booking::BOARD_TYPES))],
        ]);

        $validated['booking_type'] = $bookingType;
        $validated['status'] = $validated['status'] ?? 'confirmed';

        if (! $needsRoom) {
            // A safari-only booking has no room or stay dates of its own -
            // the safari date stands in for both so every date-driven query
            // and view elsewhere (reminders, dashboard widgets, the
            // calendar feed) keeps working without special-casing this type.
            $validated['room_id'] = null;
            $validated['check_in'] = $validated['safari_date'];
            $validated['check_out'] = $validated['safari_date'];
        }

        if (! $needsSafari) {
            $validated['safari_tour_name'] = null;
            $validated['safari_date'] = null;
            $validated['safari_vehicle_details'] = null;
        }

        if ($needsRoom) {
            $overlaps = Booking::where('room_id', $validated['room_id'])
                ->where('status', '!=', 'cancelled')
                ->when($booking, fn ($query) => $query->where('id', '!=', $booking->id))
                ->where('check_in', '<', $validated['check_out'])
                ->where('check_out', '>', $validated['check_in'])
                ->exists();

            if ($overlaps) {
                throw ValidationException::withMessages([
                    'check_in' => 'This room is already booked for the selected dates.',
                ]);
            }
        }

        return $validated;
    }

    /**
     * Runs an automated WhatsApp dispatch closure only when
     * WHATSAPP_AUTO_SEND is enabled, so a local environment without WAHA
     * configured never fails a booking create/confirm/checkout because it
     * tried to reach WhatsApp. When disabled, the dispatch is skipped
     * entirely (nothing is queued) and a debug notice is logged instead -
     * the calling booking operation always proceeds either way.
     *
     * Not used by the manual "Resend via WhatsApp" button (sendWhatsApp()
     * above), which should always attempt to send.
     */
    private function dispatchAutomatedWhatsApp(\Closure $dispatch, string $trigger, int $bookingId): void
    {
        if (! config('waha.auto_send', false)) {
            Log::debug("WhatsApp auto-send disabled; skipping automated {$trigger} message.", [
                'booking_id' => $bookingId,
            ]);

            return;
        }

        $dispatch();
    }
}
