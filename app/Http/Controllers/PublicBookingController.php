<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicBookingController extends Controller
{
    /**
     * Board type -> label, included meals, and the per-guest-per-night
     * supplement added on top of the room rate. There's no admin-managed
     * pricing table for this yet, so these are a documented starting point
     * a real deployment would want to move into Settings.
     */
    private const BOARD_TYPES = [
        'cabana_only' => ['label' => 'Cabana Only', 'meals' => [], 'supplement' => 0],
        'half_board' => ['label' => 'Half Board (Breakfast & Dinner)', 'meals' => ['breakfast', 'dinner'], 'supplement' => 3500],
        'full_board' => ['label' => 'Full Board (Breakfast, Lunch & Dinner)', 'meals' => ['breakfast', 'lunch', 'dinner'], 'supplement' => 6000],
    ];

    /**
     * Meal -> selectable menu choices for that sitting.
     */
    private const MENU_OPTIONS = [
        'breakfast' => ['Continental Breakfast', 'Sri Lankan Rice & Curry Breakfast', 'Kids Specials'],
        'lunch' => ['Sri Lankan Rice & Curry', 'Seafood Platter', 'BBQ Lunch', 'Kids Specials'],
        'dinner' => ['Sri Lankan Rice & Curry', 'Seafood Platter', 'BBQ Dinner', 'Kids Specials'],
    ];

    /**
     * The public villa landing page: hero/branding, available cabanas, and
     * the interactive booking + meal plan calculator.
     */
    public function show(string $slug): View
    {
        $settings = $this->resolveSettings($slug);

        $rooms = Room::where('status', 'available')
            ->orderBy('price_per_night')
            ->get();

        return view('public.villa', [
            'settings' => $settings,
            'slug' => $slug,
            'rooms' => $rooms,
            'boardTypes' => self::BOARD_TYPES,
            'menuOptions' => self::MENU_OPTIONS,
        ]);
    }

    /**
     * Submits a direct-booking enquiry from the public page. Creates a
     * 'pending' booking (never auto-confirmed - the owner reviews and
     * confirms it from the admin bookings list) and hands back a WhatsApp
     * deep link pre-filled with the booking summary for instant owner
     * notification, since there's no other real-time alerting here.
     */
    public function store(Request $request, string $slug): RedirectResponse
    {
        $settings = $this->resolveSettings($slug);

        $validated = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'board_type' => ['required', Rule::in(array_keys(self::BOARD_TYPES))],
            'menu_items' => ['nullable', 'array'],
            'menu_items.*' => ['nullable', 'string', 'max:255'],
        ]);

        $room = Room::where('status', 'available')->findOrFail($validated['room_id']);

        $adults = (int) $validated['adults'];
        $children = (int) ($validated['children'] ?? 0);
        $guests = max(1, $adults + $children);

        if ($guests > $room->capacity) {
            throw ValidationException::withMessages([
                'adults' => "{$room->name_or_number} sleeps up to {$room->capacity} guests - please choose a larger cabana or reduce your guest count.",
            ]);
        }

        $checkIn = Carbon::parse($validated['check_in'])->startOfDay();
        $checkOut = Carbon::parse($validated['check_out'])->startOfDay();
        $nights = max(1, $checkIn->diffInDays($checkOut));

        $board = self::BOARD_TYPES[$validated['board_type']];
        $selectedMenuItems = $this->sanitizeMenuItems($validated['menu_items'] ?? [], $board['meals']);

        $roomTotal = round((float) $room->price_per_night * $nights, 2);
        $mealTotal = round($board['supplement'] * $guests * $nights, 2);
        $total = round($roomTotal + $mealTotal, 2);

        $booking = Booking::create([
            'room_id' => $room->id,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'] ?? null,
            'customer_phone' => $validated['customer_phone'],
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'total_amount' => $total,
            'advance_payment' => 0,
            'status' => 'pending',
            'board_type' => $validated['board_type'],
            'selected_menu_items' => $selectedMenuItems,
            'guests_adults' => $adults,
            'guests_children' => $children,
        ]);

        $reference = 'INQ-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT);
        $whatsappUrl = $this->buildWhatsAppUrl(
            $settings, $booking, $room, $board, $selectedMenuItems, $nights, $adults, $children, $total, $reference
        );

        return redirect(route('public.villa', $slug).'#booking-confirmation')
            ->with('bookingSubmitted', true)
            ->with('bookingReference', $reference)
            ->with('whatsappUrl', $whatsappUrl);
    }

    /**
     * This app is a single-tenant install - one villa, one Settings row -
     * so the slug isn't a lookup key into a table of villas, just a
     * human-readable identifier the URL is checked against. A visitor with
     * the wrong (or no) slug gets a 404 rather than silently seeing
     * someone's private draft branding.
     */
    private function resolveSettings(string $slug): Setting
    {
        $settings = Setting::current();

        abort_unless($settings->exists && Str::slug($settings->villa_name) === $slug, 404);

        return $settings;
    }

    /**
     * Keeps only menu selections that belong to a meal actually included
     * in the chosen board type, and only values that are real options for
     * that meal - never trusts the submitted keys/values as-is.
     *
     * @param  array<string, mixed>  $submitted
     * @param  list<string>  $includedMeals
     * @return array<string, string>
     */
    private function sanitizeMenuItems(array $submitted, array $includedMeals): array
    {
        $selected = [];

        foreach ($includedMeals as $meal) {
            $choice = $submitted[$meal] ?? null;

            if (is_string($choice) && in_array($choice, self::MENU_OPTIONS[$meal] ?? [], true)) {
                $selected[$meal] = $choice;
            }
        }

        return $selected;
    }

    /**
     * @param  array{label: string, meals: list<string>, supplement: int}  $board
     * @param  array<string, string>  $menuItems
     */
    private function buildWhatsAppUrl(
        Setting $settings,
        Booking $booking,
        Room $room,
        array $board,
        array $menuItems,
        int $nights,
        int $adults,
        int $children,
        float $total,
        string $reference
    ): string {
        $lines = [
            "New Booking Enquiry - {$settings->villa_name}",
            '',
            "Reference: {$reference}",
            "Cabana: {$room->name_or_number}",
            'Check-in: '.$booking->check_in->format('d M Y'),
            'Check-out: '.$booking->check_out->format('d M Y')." ({$nights} ".Str::plural('night', $nights).')',
            "Guests: {$adults} Adult".($adults === 1 ? '' : 's').($children > 0 ? ", {$children} Child".($children === 1 ? '' : 'ren') : ''),
            '',
            "Board Type: {$board['label']}",
        ];

        foreach ($menuItems as $meal => $choice) {
            $lines[] = ucfirst($meal).": {$choice}";
        }

        $lines[] = '';
        $lines[] = "Estimated Total: {$settings->currency} ".number_format($total, 2);
        $lines[] = '';
        $lines[] = "Guest: {$booking->customer_name}";
        $lines[] = "Phone: {$booking->customer_phone}";
        $lines[] = '';
        $lines[] = 'Please confirm availability and next steps. Thank you!';

        $phone = preg_replace('/\D+/', '', (string) $settings->phone_number) ?: '';

        return 'https://wa.me/'.$phone.'?text='.rawurlencode(implode("\n", $lines));
    }
}
