<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CabanaPricingTier;
use App\Models\CabanaType;
use App\Models\LandingPageMenu;
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
     * Board type -> display label. Rates themselves are never hardcoded
     * here - they come entirely from the selected CabanaType's
     * CabanaPricingTier rows, set by the owner from the Landing Page
     * admin section.
     */
    private const BOARD_TYPE_LABELS = [
        'cabana_only' => 'Cabana Only',
        'half_board' => 'Half Board',
        'full_board' => 'Full Board',
    ];

    /**
     * Board type -> which meal sittings are offered. Fixed by the
     * business rule (Half Board = breakfast & dinner, Full Board = all
     * three, Cabana Only = none) rather than owner-configurable.
     */
    private const BOARD_TYPE_MEALS = [
        'cabana_only' => [],
        'half_board' => ['breakfast', 'dinner'],
        'full_board' => ['breakfast', 'lunch', 'dinner'],
    ];

    /**
     * The public villa landing page: hero/branding, admin-configured
     * cabana types, and the interactive booking + meal plan calculator.
     */
    public function show(string $slug): View
    {
        $settings = $this->resolveSettings($slug);

        // Only cabana types the owner has both activated and linked to a
        // real bookable Room ever reach the public page - an unlinked one
        // has nowhere for a resulting booking's room_id to point to.
        $cabanaTypes = CabanaType::where('is_active', true)
            ->whereNotNull('room_id')
            ->with('pricingTiers')
            ->orderBy('name')
            ->get();

        $menuOptions = $this->menuOptions();

        return view('public.villa', [
            'settings' => $settings,
            'slug' => $slug,
            'cabanaTypes' => $cabanaTypes,
            'cabanaTypesForCalculator' => $this->cabanaTypesForCalculator($cabanaTypes),
            'boardTypeLabels' => self::BOARD_TYPE_LABELS,
            'boardTypeMeals' => self::BOARD_TYPE_MEALS,
            'menuOptions' => $menuOptions,
            'oldBookingInput' => $this->oldBookingInput(),
        ]);
    }

    /**
     * Re-populates the Alpine booking calculator's fields from the previous
     * submission after a validation failure. Built here (rather than an
     * inline array literal inside @json() in the view) so the x-data
     * attribute stays a flat list of variable references.
     *
     * @return array<string, mixed>
     */
    private function oldBookingInput(): array
    {
        return [
            'check_in' => old('check_in'),
            'check_out' => old('check_out'),
            'adults' => old('adults'),
            'children' => old('children'),
            'board_type' => old('board_type'),
            'cabana_type_id' => old('cabana_type_id'),
            'bbq_addon' => old('bbq_addon'),
            'safari_jeep_addon' => old('safari_jeep_addon'),
            'outdoor_dining_preference' => old('outdoor_dining_preference'),
        ];
    }

    /**
     * Shapes the public cabana types into the plain array the Alpine
     * booking calculator needs. Kept out of the Blade view (rather than
     * an inline arrow-function map inside @json()) so the x-data
     * attribute stays a flat list of variable references.
     *
     * @param  \Illuminate\Support\Collection<int, CabanaType>  $cabanaTypes
     * @return list<array<string, mixed>>
     */
    private function cabanaTypesForCalculator($cabanaTypes): array
    {
        return $cabanaTypes->map(fn (CabanaType $cabanaType) => [
            'id' => $cabanaType->id,
            'name' => $cabanaType->name,
            'description' => $cabanaType->description,
            'image_url' => $cabanaType->image_url,
            'max_capacity' => $cabanaType->max_capacity,
            'tiers' => $cabanaType->pricingTiers->map(fn (CabanaPricingTier $tier) => [
                'min_pax' => $tier->min_pax,
                'max_pax' => $tier->max_pax,
                'cabana_only_price' => $tier->cabana_only_price !== null ? (float) $tier->cabana_only_price : null,
                'half_board_price' => $tier->half_board_price !== null ? (float) $tier->half_board_price : null,
                'full_board_price' => $tier->full_board_price !== null ? (float) $tier->full_board_price : null,
            ])->values()->all(),
        ])->all();
    }

    /**
     * Selectable menu items grouped by meal sitting, sourced from the
     * Landing Page admin section (LandingPageMenu) rather than a
     * hardcoded list - always returns all three keys, even if the owner
     * hasn't added items for one of them yet.
     *
     * @return array<string, list<string>>
     */
    private function menuOptions(): array
    {
        $grouped = LandingPageMenu::orderBy('item_name')->get()->groupBy('meal_type');

        return collect(['breakfast', 'lunch', 'dinner'])
            ->mapWithKeys(fn (string $meal) => [
                $meal => $grouped->get($meal, collect())->pluck('item_name')->values()->all(),
            ])
            ->all();
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
            'cabana_type_id' => ['required', 'integer'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'board_type' => ['required', Rule::in(array_keys(self::BOARD_TYPE_LABELS))],
            'menu_items' => ['nullable', 'array'],
            'menu_items.*' => ['nullable', 'string', 'max:255'],
            'bbq_addon' => ['nullable', 'boolean'],
            'safari_jeep_addon' => ['nullable', 'boolean'],
            'outdoor_dining_preference' => ['nullable', 'boolean'],
        ]);

        $cabanaType = CabanaType::where('is_active', true)
            ->whereNotNull('room_id')
            ->with('pricingTiers')
            ->find($validated['cabana_type_id']);

        abort_if($cabanaType === null, 404);

        $adults = (int) $validated['adults'];
        $children = (int) ($validated['children'] ?? 0);
        $guests = max(1, $adults + $children);

        if ($guests > $cabanaType->max_capacity) {
            throw ValidationException::withMessages([
                'adults' => "{$cabanaType->name} sleeps up to {$cabanaType->max_capacity} guests - please choose a larger cabana or reduce your guest count.",
            ]);
        }

        $boardType = $validated['board_type'];
        $nightlyRate = $cabanaType->rateForGuests($guests, $boardType);

        if ($nightlyRate === null) {
            throw ValidationException::withMessages([
                'board_type' => self::BOARD_TYPE_LABELS[$boardType]." isn't available for {$guests} guests on {$cabanaType->name} - please choose a different board type or guest count.",
            ]);
        }

        $checkIn = Carbon::parse($validated['check_in'])->startOfDay();
        $checkOut = Carbon::parse($validated['check_out'])->startOfDay();
        $nights = max(1, $checkIn->diffInDays($checkOut));

        $includedMeals = self::BOARD_TYPE_MEALS[$boardType];
        $menuOptions = $this->menuOptions();
        $selectedMenuItems = $this->sanitizeMenuItems($validated['menu_items'] ?? [], $includedMeals, $menuOptions);

        // Tier prices are already complete nightly rates per board type
        // (not a base rate plus a computed meal supplement), so the total
        // is simply that rate times the number of nights.
        $total = round($nightlyRate * $nights, 2);

        $booking = Booking::create([
            'room_id' => $cabanaType->room_id,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'] ?? null,
            'customer_phone' => $validated['customer_phone'],
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'total_amount' => $total,
            'advance_payment' => 0,
            'status' => 'pending',
            'board_type' => $boardType,
            'selected_menu_items' => $selectedMenuItems,
            'guests_adults' => $adults,
            'guests_children' => $children,
            'bbq_addon' => $request->boolean('bbq_addon'),
            'safari_jeep_addon' => $request->boolean('safari_jeep_addon'),
            'outdoor_dining_preference' => $request->boolean('outdoor_dining_preference'),
        ]);

        $reference = 'INQ-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT);
        $whatsappUrl = $this->buildWhatsAppUrl(
            $settings, $booking, $cabanaType, $boardType, $selectedMenuItems, $nights, $adults, $children, $nightlyRate, $total, $reference
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
     * in the chosen board type, and only values that are real, currently
     * configured options for that meal - never trusts the submitted
     * keys/values as-is.
     *
     * @param  array<string, mixed>  $submitted
     * @param  list<string>  $includedMeals
     * @param  array<string, list<string>>  $menuOptions
     * @return array<string, string>
     */
    private function sanitizeMenuItems(array $submitted, array $includedMeals, array $menuOptions): array
    {
        $selected = [];

        foreach ($includedMeals as $meal) {
            $choice = $submitted[$meal] ?? null;

            if (is_string($choice) && in_array($choice, $menuOptions[$meal] ?? [], true)) {
                $selected[$meal] = $choice;
            }
        }

        return $selected;
    }

    /**
     * @param  array<string, string>  $menuItems
     */
    private function buildWhatsAppUrl(
        Setting $settings,
        Booking $booking,
        CabanaType $cabanaType,
        string $boardType,
        array $menuItems,
        int $nights,
        int $adults,
        int $children,
        float $nightlyRate,
        float $total,
        string $reference
    ): string {
        $guests = $adults + $children;

        $lines = [
            "New Booking Enquiry - {$settings->villa_name}",
            '',
            "Reference: {$reference}",
            "Cabana: {$cabanaType->name} ({$guests} Pax)",
            'Board Type: '.self::BOARD_TYPE_LABELS[$boardType],
            "Rate: {$settings->currency} ".number_format($nightlyRate, 2).'/night',
            'Check-in: '.$booking->check_in->format('d M Y'),
            'Check-out: '.$booking->check_out->format('d M Y')." ({$nights} ".Str::plural('night', $nights).')',
            "Guests: {$adults} Adult".($adults === 1 ? '' : 's').($children > 0 ? ", {$children} Child".($children === 1 ? '' : 'ren') : ''),
        ];

        if ($menuItems !== []) {
            $lines[] = '';
            foreach ($menuItems as $meal => $choice) {
                $lines[] = ucfirst($meal).": {$choice}";
            }
        }

        $addons = array_filter([
            $booking->bbq_addon ? 'BBQ & Campfire Experience' : null,
            $booking->safari_jeep_addon ? 'Safari Jeep Arrangement' : null,
            $booking->outdoor_dining_preference ? 'Outdoor Dining Preference' : null,
        ]);

        if ($addons !== []) {
            $lines[] = '';
            $lines[] = 'Add-ons Requested: '.implode(', ', $addons);
        }

        $lines[] = '';
        $lines[] = "Estimated Total: {$settings->currency} ".number_format($total, 2);
        $lines[] = '';
        $lines[] = "Guest: {$booking->customer_name}";
        $lines[] = "Phone: {$booking->customer_phone}";
        $lines[] = '';
        $lines[] = 'Please confirm availability and next steps. Thank you!';

        $phone = preg_replace('/\D+/', '', (string) ($settings->public_whatsapp_number ?: $settings->phone_number)) ?: '';

        return 'https://wa.me/'.$phone.'?text='.rawurlencode(implode("\n", $lines));
    }
}
