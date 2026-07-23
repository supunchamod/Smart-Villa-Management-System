<?php

namespace App\Http\Controllers;

use App\Models\CabanaType;
use App\Models\LandingPageMenu;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'rooms' => Room::orderBy('name_or_number')->get(),
        ]);
    }

    public function storeCabanaType(Request $request): RedirectResponse
    {
        [$attributes, $tiers] = $this->validateCabanaType($request);

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
            'rooms' => Room::orderBy('name_or_number')->get(),
        ]);
    }

    public function updateCabanaType(Request $request, CabanaType $cabanaType): RedirectResponse
    {
        [$attributes, $tiers] = $this->validateCabanaType($request);

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
            'image_url' => ['nullable', 'url', 'max:2048'],
            'description' => ['nullable', 'string', 'max:2000'],
            'max_capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'is_active' => ['nullable', 'boolean'],
            'tiers' => ['array'],
            'tiers.*.cabana_only_price' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.half_board_price' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.full_board_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $attributes = [
            'name' => $validated['name'],
            'image_url' => $validated['image_url'] ?? null,
            'description' => $validated['description'] ?? null,
            'max_capacity' => $validated['max_capacity'],
            'room_id' => $validated['room_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
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
}
