<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     * The quick-filter bar's counts are always computed across every
     * booking (not just the current page), so they stay accurate
     * regardless of which page or filter tab is currently showing.
     */
    public function index(): View
    {
        $today = today();

        return view('bookings.index', [
            'bookings' => Booking::with('room')->latest('check_in')->paginate(10),
            'pendingBookingsCount' => Booking::where('status', 'pending')->count(),
            'todayCheckInsCount' => Booking::where('status', 'confirmed')->whereDate('check_in', $today)->count(),
            'todayCheckOutsCount' => Booking::where('status', 'confirmed')->whereDate('check_out', $today)->count(),
            'balanceDueCount' => Booking::where('status', 'confirmed')->whereColumn('advance_payment', '<', 'total_amount')->count(),
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

        Booking::create($validated);

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
                'title' => $booking->room->name_or_number.' — '.$booking->customer_name,
                'start' => $booking->check_in->toDateString(),
                'end' => $booking->check_out->toDateString(),
                'color' => self::STATUS_COLORS[$booking->status] ?? '#5278ff',
                'extendedProps' => [
                    'customer_name' => $booking->customer_name,
                    'customer_email' => $booking->customer_email,
                    'customer_phone' => $booking->customer_phone,
                    'room' => $booking->room->name_or_number,
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
     * Records that a click-to-send WhatsApp message was sent for this
     * booking, so the admin UI can show an "already sent" status badge.
     * Called via AJAX right after the wa.me link is opened in a new tab -
     * this only logs the click, since there's no WhatsApp API to confirm
     * actual delivery.
     */
    public function logWaSent(Request $request, Booking $booking): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(WhatsAppService::MESSAGE_TYPES))],
        ]);

        $column = WhatsAppService::MESSAGE_TYPES[$validated['type']];
        $booking->update([$column => now()]);

        return response()->json([
            'type' => $validated['type'],
            'sent_at' => $booking->{$column}->format('d M Y, h:i A'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateBooking(Request $request, ?Booking $booking = null): array
    {
        $validated = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'advance_payment' => ['required', 'numeric', 'min:0', 'lte:total_amount'],
            'status' => [Rule::in(['pending', 'confirmed', 'checked_out', 'cancelled'])],
        ]);

        $validated['status'] = $validated['status'] ?? 'confirmed';

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

        return $validated;
    }
}
