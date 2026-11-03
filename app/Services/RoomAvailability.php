<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for "can this room be booked for these dates?",
 * shared by the admin BookingController and the public enquiry form.
 *
 * Callers should run the check and the write inside withLockedRoom() so
 * two simultaneous requests for the same room are serialised and can't
 * both pass the overlap check before either has inserted its booking.
 */
class RoomAvailability
{
    /**
     * Statuses that hold a room for their dates. A pending enquiry counts
     * (it is a hold the owner has yet to accept or decline); only
     * cancelled bookings free the room up.
     */
    public const BLOCKING_STATUSES = ['pending', 'confirmed', 'checked_out'];

    /**
     * Statuses that are already accepted. Used when confirming a pending
     * request: other pending enquiries don't block it, but an accepted
     * booking does.
     */
    public const ACCEPTED_STATUSES = ['confirmed', 'checked_out'];

    /**
     * Run $callback inside a transaction holding a write lock on the room
     * row. The callback receives the freshly locked Room.
     *
     * lockForUpdate() takes a row lock on MySQL/PostgreSQL. SQLite has no
     * row locks, but it serialises writers at the database level, which
     * gives the same protection for this single-villa install.
     *
     * @template T
     *
     * @param  Closure(Room): T  $callback
     * @return T
     */
    public function withLockedRoom(int $roomId, Closure $callback): mixed
    {
        return DB::transaction(function () use ($roomId, $callback) {
            $room = Room::query()->whereKey($roomId)->lockForUpdate()->first();

            if (! $room) {
                throw ValidationException::withMessages([
                    'room_id' => 'The selected room no longer exists.',
                ]);
            }

            return $callback($room);
        });
    }

    /**
     * Throws a ValidationException (keyed to the check_in field) unless the
     * room is open for the stay: not under maintenance, and with no
     * overlapping booking in $blockingStatuses.
     *
     * @param  array<int, string>  $blockingStatuses
     */
    public function assertAvailable(
        Room $room,
        CarbonInterface|string $checkIn,
        CarbonInterface|string $checkOut,
        ?int $ignoreBookingId = null,
        array $blockingStatuses = self::BLOCKING_STATUSES,
    ): void {
        if ($room->status === 'maintenance') {
            throw ValidationException::withMessages([
                'check_in' => "{$room->name_or_number} is currently under maintenance and can't be booked.",
            ]);
        }

        if ($this->hasOverlap($room->id, $checkIn, $checkOut, $ignoreBookingId, $blockingStatuses)) {
            throw ValidationException::withMessages([
                'check_in' => 'This room is already booked for the selected dates.',
            ]);
        }
    }

    /**
     * Half-open interval overlap: a guest checking out on the day another
     * checks in is fine.
     *
     * @param  array<int, string>  $blockingStatuses
     */
    public function hasOverlap(
        int $roomId,
        CarbonInterface|string $checkIn,
        CarbonInterface|string $checkOut,
        ?int $ignoreBookingId = null,
        array $blockingStatuses = self::BLOCKING_STATUSES,
    ): bool {
        return Booking::query()
            ->where('room_id', $roomId)
            ->whereIn('status', $blockingStatuses)
            ->when($ignoreBookingId, fn ($query) => $query->where('id', '!=', $ignoreBookingId))
            ->whereDate('check_in', '<', $this->date($checkOut))
            ->whereDate('check_out', '>', $this->date($checkIn))
            ->exists();
    }

    private function date(CarbonInterface|string $value): string
    {
        return $value instanceof CarbonInterface ? $value->toDateString() : date('Y-m-d', strtotime($value));
    }
}
