<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    /**
     * Display a listing of the villa's rooms.
     */
    public function index(): View
    {
        return view('rooms.index', [
            'rooms' => Room::latest()->paginate(10),
            'settings' => Setting::current(),
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
        return $request->validate([
            'name_or_number' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'price_per_night' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:available,maintenance'],
        ]);
    }
}
