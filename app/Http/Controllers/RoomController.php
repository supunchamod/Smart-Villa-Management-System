<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    /**
     * Display a listing of the villa's rooms.
     *
     * $globalSettings (for currency display) is already available via the
     * view composer registered in AppServiceProvider.
     */
    public function index(): View
    {
        return view('rooms.index', [
            'rooms' => Room::latest()->paginate(10),
        ]);
    }

    /**
     * Show the form for creating a new room.
     */
    public function create(): View
    {
        return view('rooms.create');
    }

    /**
     * Store a newly created room.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRoom($request);

        Room::create($validated);

        return redirect()->route('rooms.index')->with('status', 'Room added successfully.');
    }

    /**
     * Show the form for editing the given room.
     */
    public function edit(Room $room): View
    {
        return view('rooms.edit', [
            'room' => $room,
        ]);
    }

    /**
     * Update the given room.
     */
    public function update(Request $request, Room $room): RedirectResponse
    {
        $validated = $this->validateRoom($request);

        $room->update($validated);

        return redirect()->route('rooms.index')->with('status', 'Room updated successfully.');
    }

    /**
     * Remove the given room.
     */
    public function destroy(Room $room): RedirectResponse
    {
        $room->delete();

        return redirect()->route('rooms.index')->with('status', 'Room deleted successfully.');
    }

    /**
     * Quickly flip a room between available and maintenance.
     */
    public function toggleStatus(Room $room): RedirectResponse
    {
        $room->update([
            'status' => $room->status === 'available' ? 'maintenance' : 'available',
        ]);

        return redirect()->route('rooms.index')->with('status', 'Room status updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateRoom(Request $request): array
    {
        $validated = $request->validate([
            'name_or_number' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'price_per_night' => ['required', 'numeric', 'min:0'],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'photo_url' => ['nullable', 'url', 'max:2048'],
            'photo_urls_text' => ['nullable', 'string'],
            'status' => ['required', 'in:available,maintenance'],
            'tier_2' => ['nullable', 'numeric', 'min:0'],
            'tier_4' => ['nullable', 'numeric', 'min:0'],
            'tier_6' => ['nullable', 'numeric', 'min:0'],
            'tier_8' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated['photo_urls'] = $this->parseGalleryUrls($validated['photo_urls_text'] ?? null);
        unset($validated['photo_urls_text']);

        $validated['pricing_tiers'] = $this->parsePricingTiers($validated);
        unset($validated['tier_2'], $validated['tier_4'], $validated['tier_6'], $validated['tier_8']);

        return $validated;
    }

    /**
     * One URL per line -> a clean, deduplicated list, or null when the
     * owner left it blank (falls back to just the single cover photo_url).
     */
    private function parseGalleryUrls(?string $text): ?array
    {
        if (! $text) {
            return null;
        }

        $urls = collect(preg_split('/\r\n|\r|\n/', $text))
            ->map(fn (string $url) => trim($url))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $urls ?: null;
    }

    /**
     * Builds the {"max guests for this rate": rate} tiered-pricing map from
     * the four optional tier rate inputs. Any left blank are simply
     * omitted; if none are filled in, the room stays on flat price_per_night
     * pricing (pricing_tiers null).
     */
    private function parsePricingTiers(array $validated): ?array
    {
        $tiers = [];

        foreach (['2', '4', '6', '8'] as $maxPax) {
            if (array_key_exists("tier_{$maxPax}", $validated) && $validated["tier_{$maxPax}"] !== null && $validated["tier_{$maxPax}"] !== '') {
                $tiers[$maxPax] = (float) $validated["tier_{$maxPax}"];
            }
        }

        return $tiers ?: null;
    }
}
