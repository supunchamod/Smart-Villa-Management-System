<?php

namespace App\Http\Controllers;

use App\Models\CabanaType;
use App\Models\LandingPageMenu;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    /**
     * The four guest-count brackets the pricing form always shows, per
     * the Star Moon Cabana rate structure (2 / 3-4 / 5-6 / 7-8 pax). An
     * owner who only fills in some of them just leaves the others blank -
     * see CabanaType::rateForGuests() and parsePricingTiers() below.
     *
     * @var list<array{min: int, max: int, label: string}>
     */
    private const TIER_BRACKETS = [
        ['min' => 1, 'max' => 2, 'label' => '2 Pax (Couple)'],
        ['min' => 3, 'max' => 4, 'label' => '3-4 Pax'],
        ['min' => 5, 'max' => 6, 'label' => '5-6 Pax'],
        ['min' => 7, 'max' => 8, 'label' => '7-8 Pax'],
    ];

    private const MEAL_TYPES = ['breakfast', 'lunch', 'dinner'];

    /**
     * Where uploaded cabana images live, relative to the public/ directory
     * (rendered via asset() in the views).
     */
    private const IMAGE_DIRECTORY = 'assets/images/cabanas';

    /**
     * The Landing Page manager: cabana types (with their pricing tiers)
     * and the meal menu items, all editable from one page.
     */
    public function index(): View
    {
        return view('admin.landing-page.index', [
            'cabanaTypes' => CabanaType::with(['pricingTiers', 'room'])->latest()->get(),
            'menusByType' => LandingPageMenu::orderBy('item_name')->get()->groupBy('meal_type'),
            'mealTypes' => self::MEAL_TYPES,
        ]);
    }

    /**
     * Show the form for creating a new cabana type.
     */
    public function createCabanaType(): View
    {
        return view('admin.landing-page.create', [
            'tierBrackets' => self::TIER_BRACKETS,
        ]);
    }

    public function storeCabanaType(Request $request): RedirectResponse
    {
        [$attributes, $tiers] = $this->validateCabanaType($request);

        if ($request->hasFile('image')) {
            $attributes['image_url'] = $this->storeImage($request);
        }

        // A cabana type needs a real bookable Room behind it, since a
        // resulting booking always needs a room_id to point to. Previously
        // this required the admin to manually link an existing room - an
        // easy step to miss, which silently left a cabana type invisible
        // on the public page (it was filtered out until linked). Now one
        // is created and kept in sync automatically instead.
        $attributes['room_id'] = $this->syncBackingRoom(null, $attributes, $tiers)->id;

        $cabanaType = CabanaType::create($attributes);
        $cabanaType->pricingTiers()->createMany($tiers);

        return redirect()->route('landing-page.index')->with('status', 'Cabana type added successfully.');
    }

    /**
     * Show the form for editing the given cabana type.
     */
    public function editCabanaType(CabanaType $cabanaType): View
    {
        return view('admin.landing-page.edit', [
            'cabanaType' => $cabanaType->load('pricingTiers'),
            'tierBrackets' => self::TIER_BRACKETS,
        ]);
    }

    public function updateCabanaType(Request $request, CabanaType $cabanaType): RedirectResponse
    {
        [$attributes, $tiers] = $this->validateCabanaType($request);

        if ($request->hasFile('image')) {
            $this->deleteImage($cabanaType->image_url);
            $attributes['image_url'] = $this->storeImage($request);
        }

        // Self-healing: a cabana type saved before this fix (or otherwise
        // missing its backing room) gets one created here too.
        $attributes['room_id'] = $this->syncBackingRoom($cabanaType->room, $attributes, $tiers)->id;

        $cabanaType->update($attributes);

        // Simplest correct sync: the form always submits all four brackets
        // (some possibly all-blank), so replacing every tier row is safe
        // and avoids having to match submitted rows back to existing
        // tier IDs.
        $cabanaType->pricingTiers()->delete();
        $cabanaType->pricingTiers()->createMany($tiers);

        return redirect()->route('landing-page.index')->with('status', 'Cabana type updated successfully.');
    }

    public function destroyCabanaType(CabanaType $cabanaType): RedirectResponse
    {
        $this->deleteImage($cabanaType->image_url);
        $cabanaType->delete();

        return redirect()->route('landing-page.index')->with('status', 'Cabana type deleted successfully.');
    }

    public function storeMenuItem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meal_type' => ['required', 'in:'.implode(',', self::MEAL_TYPES)],
            'item_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        LandingPageMenu::create($validated);

        return back()->with('status', 'Menu item added successfully.');
    }

    public function destroyMenuItem(LandingPageMenu $menuItem): RedirectResponse
    {
        $menuItem->delete();

        return back()->with('status', 'Menu item removed.');
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<array<string, mixed>>}
     */
    private function validateCabanaType(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'description' => ['nullable', 'string', 'max:2000'],
            'max_capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'tiers' => ['array'],
            'tiers.*.cabana_only_price' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.half_board_price' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.full_board_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $attributes = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'max_capacity' => $validated['max_capacity'],
            // Defaults to true whenever the field is missing from the
            // request entirely (not just unchecked) - the checkbox is
            // always rendered checked by default, but this keeps a
            // programmatic submission that omits it from silently
            // creating an inactive, invisible cabana type.
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ];

        $tiers = $this->parsePricingTiers($validated['tiers'] ?? []);

        return [$attributes, $tiers];
    }

    /**
     * Builds one CabanaPricingTier row per fixed bracket that has at
     * least one price filled in - brackets left entirely blank are
     * skipped rather than saved as an all-null row.
     *
     * @param  array<int, array<string, mixed>>  $submittedTiers
     * @return list<array<string, mixed>>
     */
    private function parsePricingTiers(array $submittedTiers): array
    {
        $tiers = [];

        foreach (self::TIER_BRACKETS as $index => $bracket) {
            $row = $submittedTiers[$index] ?? [];

            $cabanaOnly = $row['cabana_only_price'] ?? null;
            $halfBoard = $row['half_board_price'] ?? null;
            $fullBoard = $row['full_board_price'] ?? null;

            if ($cabanaOnly === null && $halfBoard === null && $fullBoard === null) {
                continue;
            }

            $tiers[] = [
                'min_pax' => $bracket['min'],
                'max_pax' => $bracket['max'],
                'cabana_only_price' => $cabanaOnly !== null ? (float) $cabanaOnly : null,
                'half_board_price' => $halfBoard !== null ? (float) $halfBoard : null,
                'full_board_price' => $fullBoard !== null ? (float) $fullBoard : null,
            ];
        }

        return $tiers;
    }

    /**
     * Moves an uploaded cabana image into public/assets/images/cabanas and
     * returns the relative web path stored on the model (rendered via
     * asset() in the views) - a UUID filename avoids any collision with
     * the original upload's name.
     */
    private function storeImage(Request $request): string
    {
        $file = $request->file('image');
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();

        $file->move(public_path(self::IMAGE_DIRECTORY), $filename);

        return self::IMAGE_DIRECTORY.'/'.$filename;
    }

    /**
     * Removes a previously uploaded cabana image from disk, if it exists.
     * Silently does nothing for a null/blank path or a file that's already
     * gone, since the caller doesn't need to distinguish those cases.
     */
    private function deleteImage(?string $imageUrl): void
    {
        if (! $imageUrl) {
            return;
        }

        $path = public_path($imageUrl);

        if (File::exists($path)) {
            File::delete($path);
        }
    }

    /**
     * Keeps a bookable Room in sync with its CabanaType, creating one the
     * first time a cabana type is saved (or if one is unexpectedly
     * missing). Room stays the record bookings actually point to and its
     * name/capacity/rate mirror the cabana type so internal Room-based
     * views (dashboard, calendar, bookings list) show something sensible -
     * but CabanaPricingTier stays the pricing source of truth for the
     * public page. status is only set on creation, never overwritten on
     * later syncs, so a "maintenance" toggle set from the Rooms admin page
     * isn't silently undone by editing the cabana type.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<array<string, mixed>>  $tiers
     */
    private function syncBackingRoom(?Room $room, array $attributes, array $tiers): Room
    {
        $roomAttributes = [
            'name_or_number' => $attributes['name'],
            'type' => $attributes['name'],
            'price_per_night' => $this->estimateNightlyRate($tiers),
            'capacity' => $attributes['max_capacity'],
        ];

        if ($room) {
            $room->update($roomAttributes);

            return $room;
        }

        return Room::create([...$roomAttributes, 'status' => 'available']);
    }

    /**
     * A representative flat rate for the backing Room record - used only
     * by internal Room-based views, never by the public page's pricing
     * (which always reads CabanaPricingTier directly). The lowest price
     * configured across every tier and board type, or 0 if none are set yet.
     *
     * @param  list<array<string, mixed>>  $tiers
     */
    private function estimateNightlyRate(array $tiers): float
    {
        $prices = collect($tiers)
            ->flatMap(fn (array $tier) => [
                $tier['cabana_only_price'],
                $tier['half_board_price'],
                $tier['full_board_price'],
            ])
            ->filter(fn ($price) => $price !== null);

        return $prices->isNotEmpty() ? (float) $prices->min() : 0.0;
    }
}
