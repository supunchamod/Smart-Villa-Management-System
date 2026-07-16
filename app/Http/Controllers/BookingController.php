<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * Status -> calendar color, kept in one place so the list badges and
     * the FullCalendar feed always agree.
     */
    private const STATUS_COLORS = [
        'confirmed' => '#5278ff',
        'checked_out' => '#2fa84f',
        'cancelled' => '#dc2626',
    ];

    /**
     * Display a listing of the villa's bookings.
     */
    public function index(): View
    {
        return view('bookings.index', [
            'bookings' => Booking::with('room')->latest('check_in')->paginate(10),
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
                    'balance' => number_format((float) $booking->total_amount - (float) $booking->advance_payment, 2),
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
        $validated = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'advance_payment' => ['required', 'numeric', 'min:0', 'lte:total_amount'],
            'status' => [Rule::in(['confirmed', 'checked_out', 'cancelled'])],
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
