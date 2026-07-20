<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Support\IncomeLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Cap per result type so a broad query (e.g. a single common letter)
     * can't return the entire table into one page.
     */
    private const PER_TYPE_LIMIT = 20;

    /**
     * Global topbar search across Bookings/Guests, Cabanas/Rooms, and
     * Financial Records. Each category is only searched (and only shown)
     * when the current user actually holds the matching permission, so a
     * manager without view_finance can't discover transaction data through
     * search that the rest of the app already hides from them.
     */
    public function index(Request $request): View
    {
        $query = trim((string) $request->input('query'));
        $user = $request->user();

        if ($query === '') {
            return view('search.results', [
                'query' => $query,
                'bookings' => collect(),
                'rooms' => collect(),
                'transactions' => collect(),
                'totalCount' => 0,
            ]);
        }

        $bookings = $user->can('manage_bookings') ? $this->searchBookings($query) : collect();
        $rooms = $this->searchRooms($query);
        $transactions = $user->can('view_finance') ? $this->searchTransactions($query) : collect();

        return view('search.results', [
            'query' => $query,
            'bookings' => $bookings,
            'rooms' => $rooms,
            'transactions' => $transactions,
            'totalCount' => $bookings->count() + $rooms->count() + $transactions->count(),
        ]);
    }

    /**
     * Guest name/email/phone, or the booking's numeric reference (its ID,
     * with or without a "BKG-" prefix - there's no separate booking
     * reference column, so the primary key is the reference token).
     *
     * @return Collection<int, Booking>
     */
    private function searchBookings(string $query): Collection
    {
        $like = $this->likePattern($query);
        $bookingId = $this->extractReferenceId($query);

        return Booking::with('room')
            ->where(function ($q) use ($like, $bookingId) {
                $q->where('customer_name', 'like', $like)
                    ->orWhere('customer_email', 'like', $like)
                    ->orWhere('customer_phone', 'like', $like);

                if ($bookingId !== null) {
                    $q->orWhere('id', $bookingId);
                }
            })
            ->orderByDesc('check_in')
            ->limit(self::PER_TYPE_LIMIT)
            ->get();
    }

    /**
     * Room/cabana name, category (type), or availability status.
     *
     * @return Collection<int, Room>
     */
    private function searchRooms(string $query): Collection
    {
        $like = $this->likePattern($query);

        return Room::where('name_or_number', 'like', $like)
            ->orWhere('type', 'like', $like)
            ->orWhere('status', 'like', $like)
            ->orderBy('name_or_number')
            ->limit(self::PER_TYPE_LIMIT)
            ->get();
    }

    /**
     * Cash-in events (advance payments / final settlements) matched by
     * their synthetic transaction ID (e.g. "ADV-0007") or the guest name
     * on the underlying booking. There's no ledger table to query directly
     * - IncomeLedger derives these from bookings - so this filters in
     * memory the same way the Profit Analyzer's ledger does. Each row gets
     * its transaction_id precomputed so the view never has to re-derive it.
     *
     * @return Collection<int, array{type: string, date: \Illuminate\Support\Carbon, amount: float, booking: Booking, transaction_id: string}>
     */
    private function searchTransactions(string $query): Collection
    {
        $needle = strtolower($query);

        return IncomeLedger::events()
            ->map(function (array $event) {
                $event['transaction_id'] = sprintf('%s-%04d', $event['type'] === 'Advance Payment' ? 'ADV' : 'SET', $event['booking']->id);

                return $event;
            })
            ->filter(fn (array $event) => Str::contains(strtolower($event['transaction_id']), $needle)
                || Str::contains(strtolower($event['booking']->customer_name), $needle))
            ->sortByDesc('date')
            ->take(self::PER_TYPE_LIMIT)
            ->values();
    }

    /**
     * Escapes LIKE wildcard characters so a literal "%" or "_" typed by the
     * user filters instead of matching everything, then wraps the value for
     * a contains-style match. The value itself is still passed as a bound
     * query parameter, so this is about matching correctness, not injection
     * safety (the query builder already parameterizes it).
     */
    private function likePattern(string $value): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value).'%';
    }

    /**
     * Pulls a booking ID out of a bare number ("123") or a "BKG-000123"
     * style reference token, or null if the query doesn't look like one.
     */
    private function extractReferenceId(string $query): ?int
    {
        if (preg_match('/^(?:bkg-)?0*(\d+)$/i', trim($query), $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }
}
