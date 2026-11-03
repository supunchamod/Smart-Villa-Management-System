<?php

namespace App\Http\Controllers;

use App\Jobs\SendBookingWhatsAppJob;
use App\Jobs\SendPostCheckoutReviewWhatsAppJob;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Setting;
use App\Rules\PhoneNumber;
use App\Services\RoomAvailability;
use App\Support\AutomatedWhatsApp;
use App\Support\BookingDetails;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends Controller
{
    public function __construct(private readonly RoomAvailability $availability)
    {
    }

    /**
     * Status -> calendar color, kept in one place so the list badges and
     * the FullCalendar feed always agree.
     */
    /**
     * "Balance due": the advance does not yet cover the room/package charge
     * plus every extra charge on the booking.
     */
    private const BALANCE_DUE_SQL = 'advance_payment < total_amount + COALESCE((SELECT SUM(booking_extras.quantity * booking_extras.unit_price) FROM booking_extras WHERE booking_extras.booking_id = bookings.id), 0)';

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

        $bookingsQuery = Booking::query()->with(['room', 'extras', 'expenses'])->latest();

        match ($tab) {
            'active' => $bookingsQuery->where('status', 'confirmed'),
            'balance_due' => $bookingsQuery->where('status', 'confirmed')->whereRaw(self::BALANCE_DUE_SQL),
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
            'balanceDueCount' => Booking::where('status', 'confirmed')->whereRaw(self::BALANCE_DUE_SQL)->count(),
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
        $charges = Arr::only($validated, ['lunch_charge', 'drone_charge']);
        $data = Arr::except($validated, ['lunch_charge', 'drone_charge']);

        $booking = $this->saveWithAvailabilityCheck($data, function () use ($data, $charges) {
            $booking = Booking::create($data);
            $this->syncSafariCharges($booking, $data, $charges);

            return $booking;
        });

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
            'booking' => $booking->load(['room', 'extras', 'expenses']),
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

        $charges = Arr::only($validated, ['lunch_charge', 'drone_charge']);
        $data = Arr::except($validated, ['lunch_charge', 'drone_charge']);

        $this->saveWithAvailabilityCheck($data, function () use ($booking, $data, $charges) {
            $booking->update($data);
            $this->syncSafariCharges($booking, $data, $charges);
        }, $booking);

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
     * Mark a confirmed booking as checked out, adding any extra charges
     * (meals, BBQ, safari jeep, ...) to the bill and settling the balance:
     *
     *   balance = total_amount (room/package) + extras - advance_payment
     *
     * Also callable via AJAX, which gets a JSON reply instead of a redirect.
     * The extras, the status change and the settlement amount are written in
     * one transaction under a row lock, so a double submit can't add the
     * extras twice or check the booking out twice.
     */
    public function checkout(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        // Rows the user added but left completely blank are noise, not errors.
        $request->merge([
            'extras' => collect($request->input('extras', []))
                ->filter(fn ($row) => is_array($row) && (trim((string) ($row['item_name'] ?? '')) !== '' || trim((string) ($row['unit_price'] ?? '')) !== ''))
                ->values()
                ->all(),
            'booking_expenses' => collect($request->input('booking_expenses', []))
                ->filter(fn ($row) => is_array($row) && (trim((string) ($row['category'] ?? '')) !== '' || trim((string) ($row['amount'] ?? '')) !== ''))
                ->values()
                ->all(),
        ]);

        $validated = $request->validate([
            'payment_method' => ['nullable', 'string', Rule::in(['cash', 'card', 'bank_transfer'])],
            'extras' => ['array', 'max:30'],
            'extras.*.item_name' => ['required', 'string', 'max:120'],
            'extras.*.quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'extras.*.unit_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            // Direct costs the villa paid for this stay (internal - not part of the guest's bill).
            'booking_expenses' => ['array', 'max:30'],
            'booking_expenses.*.category' => ['required', 'string', 'max:100'],
            'booking_expenses.*.description' => ['nullable', 'string', 'max:255'],
            'booking_expenses.*.amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'booking_expenses.*.expense_date' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $fail = function (string $message) use ($request) {
            return $request->wantsJson()
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message);
        };

        $result = DB::transaction(function () use ($booking, $validated) {
            $locked = Booking::query()->lockForUpdate()->find($booking->id);

            if (! $locked || $locked->status !== 'confirmed') {
                return 'Only confirmed bookings can be checked out.';
            }

            $extras = collect($validated['extras'] ?? [])->map(fn (array $row) => [
                'item_name' => trim($row['item_name']),
                'quantity' => (int) ($row['quantity'] ?? 1),
                'unit_price' => round((float) $row['unit_price'], 2),
            ]);

            // Extras already on the booking (e.g. a safari's lunch / drone charges) plus any added now.
            $existingExtrasTotal = round((float) $locked->extras()->get()->sum(fn ($extra) => $extra->line_total), 2);
            $extrasTotal = round($existingExtrasTotal + $extras->sum(fn (array $row) => $row['quantity'] * $row['unit_price']), 2);
            $settlement = round((float) $locked->total_amount + $extrasTotal - (float) $locked->advance_payment, 2);

            if ($settlement > 0 && empty($validated['payment_method'])) {
                return 'Please choose the payment method used to collect the balance.';
            }

            $locked->extras()->createMany($extras->all());

            $locked->expenses()->createMany(
                collect($validated['booking_expenses'] ?? [])->map(fn (array $row) => [
                    'category' => trim($row['category']),
                    'description' => isset($row['description']) && trim($row['description']) !== '' ? trim($row['description']) : null,
                    'amount' => round((float) $row['amount'], 2),
                    'expense_date' => ! empty($row['expense_date']) ? $row['expense_date'] : today()->toDateString(),
                ])->all()
            );

            $locked->update([
                'status' => 'checked_out',
                'final_settlement_amount' => max(0.0, $settlement),
                'checked_out_at' => now(),
                'payment_method' => $validated['payment_method'] ?? null,
            ]);

            return $locked;
        });

        if (is_string($result)) {
            return $fail($result);
        }

        $booking = $result;

        // The booking is now marked as paid in full - send the final
        // invoice (which includes the extras) immediately, and queue a
        // review request for an hour from now, once the guest has actually
        // had time to leave.
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
                'final_bill_url' => route('bookings.invoice.view', [$booking, 'final']),
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

        try {
            $confirmed = $this->confirmWithAvailabilityCheck($booking);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'This booking can no longer be confirmed.';

            return $request->wantsJson()
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message);
        }

        if (! $confirmed) {
            $message = 'Only pending requests can be confirmed.';

            return $request->wantsJson()
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message);
        }

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

        try {
            SendBookingWhatsAppJob::dispatch($booking);
        } catch (\Throwable $e) {
            Log::error('WhatsApp: failed to dispatch manual booking message.', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'The WhatsApp message could not be queued. Please try again shortly.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp dispatch initiated',
        ]);
    }

    /**
     * Stream (or, with ?download=1, download) the stage 1 confirmation
     * invoice, showing the remaining balance due.
     */
    public function confirmationInvoice(Request $request, Booking $booking): Response
    {
        return $this->invoicePdf($request, $booking, 'confirmation');
    }

    /**
     * Stream (or, with ?download=1, download) the stage 2 final invoice once
     * the booking has been checked out.
     */
    public function finalInvoice(Request $request, Booking $booking): Response|RedirectResponse
    {
        if ($booking->status !== 'checked_out') {
            return back()->with('error', 'This booking has not been checked out yet.');
        }

        return $this->invoicePdf($request, $booking, 'final');
    }

    /**
     * The same bill as the PDF, as a clean on-screen page: easy to
     * screenshot, print, or share with the guest over WhatsApp (it carries a
     * click-to-chat link with a text summary of the bill).
     */
    public function viewInvoice(Booking $booking, string $stage): View|RedirectResponse
    {
        abort_unless(in_array($stage, ['confirmation', 'final'], true), 404);

        if ($stage === 'final' && $booking->status !== 'checked_out') {
            return back()->with('error', 'This booking has not been checked out yet.');
        }

        $booking->load(['room', 'extras']);

        return view('invoices.pdf', [
            'booking' => $booking,
            'stage' => $stage,
            'web' => true,
            'whatsAppUrl' => $this->whatsAppShareUrl($booking, $stage),
        ]);
    }

    private function invoicePdf(Request $request, Booking $booking, string $stage): Response
    {
        $booking->load(['room', 'extras']);

        $filename = "invoice-{$stage}-{$booking->id}.pdf";
        $pdf = Pdf::loadView('invoices.pdf', [
            'booking' => $booking,
            'stage' => $stage,
        ])->setPaper('a4', 'portrait');

        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }

    /**
     * wa.me click-to-chat link pre-filled with a plain-text bill summary.
     * Null when the booking has no phone number to message.
     */
    private function whatsAppShareUrl(Booking $booking, string $stage): ?string
    {
        if (! $booking->customer_phone) {
            return null;
        }

        $settings = Setting::current();
        $currency = $settings->currency ?: 'LKR';
        $money = fn (float $amount) => $currency.' '.number_format($amount, 2);

        $lines = [
            "*{$settings->villa_name}* - ".($stage === 'final' ? 'Final Invoice' : 'Booking Invoice'),
            '',
            "Hi {$booking->customer_name},",
            'Stay: '.$booking->room_label.', '.$booking->check_in->format('d M Y').' - '.$booking->check_out->format('d M Y'),
            'Room charges: '.$money((float) $booking->total_amount),
        ];

        foreach ($booking->extras as $extra) {
            $lines[] = "{$extra->item_name} x{$extra->quantity}: ".$money($extra->line_total);
        }

        $lines[] = 'Total: '.$money($booking->grand_total);
        $lines[] = 'Advance paid: '.$money((float) $booking->advance_payment);
        $lines[] = $stage === 'final'
            ? 'Balance paid at checkout: '.$money((float) $booking->final_settlement_amount)
            : 'Balance due: '.$money($booking->remaining_balance);
        $lines[] = '';
        $lines[] = "Thank you for choosing {$settings->villa_name}!";

        $chatId = app(\App\Services\WhatsAppService::class)->formatPhoneNumber($booking->customer_phone);

        return 'https://wa.me/'.str_replace('@c.us', '', $chatId).'?text='.rawurlencode(implode("\n", $lines));
    }

    /**
     * JSON feed of the current villa's bookings, shaped for FullCalendar.
     */
    public function calendarFeed(): JsonResponse
    {
        $settings = Setting::current();

        $events = Booking::with(['room', 'extras'])->get()->map(function (Booking $booking) use ($settings) {
            return [
                'id' => $booking->id,
                'title' => $booking->room_label.' — '.$booking->customer_name,
                'start' => $booking->check_in->toDateString(),
                'end' => $booking->check_out->toDateString(),
                'color' => self::STATUS_COLORS[$booking->status] ?? '#5278ff',
                'extendedProps' => BookingDetails::for($booking, $settings),
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

        // Accommodation bookings use one of the villa's rooms; a Full Package records the hotel the
        // guests stay at instead (no room, no board basis); Safari Only has neither room nor stay dates.
        $needsRoom = $bookingType === 'accommodation_only';
        $needsStayDates = $bookingType !== 'safari_only';
        $needsSafari = $bookingType !== 'accommodation_only';
        $isSafariOnly = $bookingType === 'safari_only';
        $isFullPackage = $bookingType === 'full_package';

        $validated = $request->validate([
            'room_id' => [$needsRoom ? 'required' : 'nullable', 'exists:rooms,id'],
            'stay_hotel_name' => [$isFullPackage ? 'required' : 'nullable', 'string', 'max:255'],
            'check_in' => [$needsStayDates ? 'required' : 'nullable', 'date'],
            'check_out' => array_filter([$needsStayDates ? 'required' : 'nullable', 'date', $needsStayDates ? 'after:check_in' : null]),
            'safari_tour_name' => [$needsSafari ? 'required' : 'nullable', 'string', 'max:255'],
            'safari_date' => [$needsSafari ? 'required' : 'nullable', 'date'],
            'safari_vehicle_details' => ['nullable', 'string', 'max:255'],
            // Trip logistics: pickup time + group size for safari-only and full packages; the rest safari-only.
            'pickup_location' => ['nullable', 'string', 'max:255'],
            'pickup_time' => ['nullable', 'date_format:H:i'],
            'group_pax_size' => [$isSafariOnly || $isFullPackage ? 'required' : 'nullable', 'integer', 'min:1', 'max:500'],
            'include_lunch' => ['nullable', 'boolean'],
            'include_drone_videos' => ['nullable', 'boolean'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30', new PhoneNumber],
            'special_notes' => ['nullable', 'string', 'max:2000'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'advance_payment' => ['required', 'numeric', 'min:0'],
            // Optional safari add-on prices; saved as itemised extra charges.
            'lunch_charge' => [$isSafariOnly && $request->boolean('include_lunch') ? 'required' : 'nullable', 'numeric', 'min:0', 'max:99999999'],
            'drone_charge' => [$isSafariOnly && $request->boolean('include_drone_videos') ? 'required' : 'nullable', 'numeric', 'min:0', 'max:99999999'],
            'status' => [Rule::in(['pending', 'confirmed', 'checked_out', 'cancelled'])],
            'board_type' => ['nullable', Rule::in(array_keys(Booking::BOARD_TYPES))],
        ]);

        // The advance may cover the whole bill: room/package charge plus the safari extras.
        $safariExtras = ($isSafariOnly && $request->boolean('include_lunch') ? (float) ($validated['lunch_charge'] ?? 0) : 0.0)
            + ($isSafariOnly && $request->boolean('include_drone_videos') ? (float) ($validated['drone_charge'] ?? 0) : 0.0);

        if ((float) $validated['advance_payment'] > (float) $validated['total_amount'] + $safariExtras) {
            throw ValidationException::withMessages([
                'advance_payment' => 'The advance payment cannot be more than the total amount'.($safariExtras > 0 ? ' plus the extra charges.' : '.'),
            ]);
        }

        $validated['booking_type'] = $bookingType;
        $validated['status'] = $validated['status'] ?? 'confirmed';

        if (! $needsRoom) {
            $validated['room_id'] = null;
        }

        if (! $isFullPackage) {
            $validated['stay_hotel_name'] = null;
        }

        if ($isSafariOnly) {
            // A safari-only booking has no room or stay dates of its own -
            // the safari date stands in for both so every date-driven query
            // and view elsewhere (reminders, dashboard widgets, the
            // calendar feed) keeps working without special-casing this type.
            $validated['check_in'] = $validated['safari_date'];
            $validated['check_out'] = $validated['safari_date'];
        }

        // Only accommodation bookings have a board basis / meal plan.
        if ($bookingType !== 'accommodation_only') {
            $validated['board_type'] = null;
        }

        if ($isSafariOnly) {
            $validated['include_lunch'] = $request->boolean('include_lunch');
            $validated['include_drone_videos'] = $request->boolean('include_drone_videos');
        } else {
            $validated['include_lunch'] = false;
            $validated['include_drone_videos'] = false;
            $validated['pickup_location'] = null;
        }

        if (! $isSafariOnly && ! $isFullPackage) {
            $validated['pickup_time'] = null;
            $validated['group_pax_size'] = null;
        }

        if (! $needsSafari) {
            $validated['safari_tour_name'] = null;
            $validated['safari_date'] = null;
            $validated['safari_vehicle_details'] = null;
        }

        return $validated;
    }

    /**
     * Keep the safari add-ons in step with the booking's itemised extra
     * charges: "Lunch Service" and "Drone Video Shooting" rows exist exactly
     * when the add-on is ticked and has a charge, so they appear on every
     * bill/invoice and in the balance due. Checked-out bookings are left
     * alone (their bill is already settled). Also drops the cached
     * confirmation PDF so the invoice reflects the new amounts.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $charges
     */
    private function syncSafariCharges(Booking $booking, array $data, array $charges): void
    {
        if ($booking->status === 'checked_out') {
            return;
        }

        $addons = [
            Booking::LUNCH_ITEM => [(bool) ($data['include_lunch'] ?? false), $charges['lunch_charge'] ?? null],
            Booking::DRONE_ITEM => [(bool) ($data['include_drone_videos'] ?? false), $charges['drone_charge'] ?? null],
        ];

        foreach ($addons as $item => [$included, $amount]) {
            $amount = round((float) $amount, 2);
            $row = $booking->extras()->where('item_name', $item)->first();

            if ($included && $amount > 0) {
                $row
                    ? $row->update(['quantity' => 1, 'unit_price' => $amount])
                    : $booking->extras()->create(['item_name' => $item, 'quantity' => 1, 'unit_price' => $amount]);
            } else {
                $row?->delete();
            }
        }

        $booking->unsetRelation('extras');

        Storage::disk('public')->delete("invoices/invoice-confirmation-{$booking->id}.pdf");
    }

    /**
     * Saves the booking, first re-checking availability under a row lock
     * on the room whenever the booking would hold one (see
     * RoomAvailability). Cancelled / checked-out bookings and safari-only
     * bookings (no room) hold nothing, so they skip the check and don't
     * get blocked by it - e.g. editing the notes on a past stay.
     *
     * @param  array<string, mixed>  $validated
     */
    private function saveWithAvailabilityCheck(array $validated, \Closure $write, ?Booking $existing = null): mixed
    {
        $holdsRoom = ! empty($validated['room_id'])
            && in_array($validated['status'], ['pending', 'confirmed'], true);

        if (! $holdsRoom) {
            return DB::transaction($write);
        }

        return $this->availability->withLockedRoom((int) $validated['room_id'], function ($room) use ($validated, $write, $existing) {
            $this->availability->assertAvailable($room, $validated['check_in'], $validated['check_out'], $existing?->id);

            return $write();
        });
    }

    /**
     * Moves a pending booking to confirmed, re-verifying under a room lock
     * that no other booking was accepted for those dates (and the room
     * didn't go into maintenance) since the enquiry came in. Returns false
     * if the booking is no longer pending by the time the lock is held
     * (e.g. a double click), and throws ValidationException on a conflict.
     */
    private function confirmWithAvailabilityCheck(Booking $booking): bool
    {
        $apply = function () use ($booking): bool {
            $fresh = Booking::query()->lockForUpdate()->find($booking->id);

            if (! $fresh || $fresh->status !== 'pending') {
                return false;
            }

            $fresh->update(['status' => 'confirmed']);
            $booking->status = 'confirmed';

            return true;
        };

        if (! $booking->room_id) {
            return DB::transaction($apply);
        }

        return $this->availability->withLockedRoom($booking->room_id, function ($room) use ($booking, $apply) {
            $this->availability->assertAvailable(
                $room,
                $booking->check_in,
                $booking->check_out,
                $booking->id,
                RoomAvailability::ACCEPTED_STATUSES,
            );

            return $apply();
        });
    }

    /**
     * Runs an automated WhatsApp dispatch closure only when
     * WHATSAPP_AUTO_SEND is enabled, and never lets a dispatch failure
     * break the booking operation that triggered it. See
     * AutomatedWhatsApp. Not used by the manual "Resend via WhatsApp"
     * button (sendWhatsApp() above), which should always attempt to send.
     */
    private function dispatchAutomatedWhatsApp(\Closure $dispatch, string $trigger, int $bookingId): void
    {
        AutomatedWhatsApp::dispatch($dispatch, $trigger, $bookingId);
    }
}
