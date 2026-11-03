<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The central Expenses section: general operating expenses (no booking) and
 * direct costs linked to a specific booking live in the same table and are
 * managed here together.
 */
class ExpenseController extends Controller
{
    private const PER_PAGE = 15;

    /**
     * Expense list with search / type / category / date filters. The summary
     * figures describe the filtered set, not just the visible page.
     */
    public function index(Request $request): View
    {
        $type = in_array($request->query('type'), ['general', 'booking'], true) ? $request->query('type') : 'all';

        $filtered = fn (): Builder => $this->filteredQuery($request, $type);

        $total = (float) $filtered()->sum('amount');
        $bookingTotal = (float) $filtered()->whereNotNull('booking_id')->sum('amount');

        return view('expenses.index', [
            'expenses' => $filtered()
                ->with('booking.room')
                ->orderByDesc('expense_date')
                ->orderByDesc('id')
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
            'filters' => [
                'q' => trim((string) $request->query('q', '')),
                'type' => $type,
                'category' => (string) $request->query('category', ''),
                'from' => (string) $request->query('from', ''),
                'to' => (string) $request->query('to', ''),
            ],
            'summary' => [
                'total' => round($total, 2),
                'general' => round($total - $bookingTotal, 2),
                'booking' => round($bookingTotal, 2),
                'count' => $filtered()->count(),
            ],
            'categories' => Expense::query()->select('category')->distinct()->orderBy('category')->pluck('category'),
            'generalCategories' => Expense::GENERAL_CATEGORIES,
            'bookingCategories' => Expense::BOOKING_CATEGORIES,
        ]);
    }

    /**
     * Store a newly created expense (optionally linked to a booking).
     */
    public function store(Request $request): RedirectResponse
    {
        Expense::create($this->validated($request));

        return redirect()->route('expenses.index', $this->keepFilters($request))->with('status', 'Expense added successfully.');
    }

    /**
     * Update the given expense.
     */
    public function update(Request $request, Expense $expense): RedirectResponse
    {
        if ($expense->isPartnerPayment()) {
            return back()->with('error', 'This is a partner resort payment. Change it in Partner Resort Bookings.');
        }

        $expense->update($this->validated($request));

        return redirect()->route('expenses.index', $this->keepFilters($request))->with('status', 'Expense updated successfully.');
    }

    /**
     * Remove the given expense.
     */
    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        if ($expense->isPartnerPayment()) {
            return back()->with('error', 'This is a partner resort payment. Change it in Partner Resort Bookings.');
        }

        $expense->delete();

        return redirect()->route('expenses.index', $this->keepFilters($request))->with('status', 'Expense deleted successfully.');
    }

    /**
     * Typeahead for the "link to a booking" picker: bookings matching a
     * guest name, phone, room or booking number (newest first). An empty
     * query returns the most recent bookings.
     */
    public function searchBookings(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        $bookings = Booking::query()
            ->with('room')
            ->when($term !== '', function (Builder $query) use ($term) {
                $like = '%'.addcslashes($term, '%_\\').'%';

                $query->where(function (Builder $query) use ($like, $term) {
                    $query->where('customer_name', 'like', $like)
                        ->orWhere('customer_phone', 'like', $like)
                        ->orWhere('safari_tour_name', 'like', $like)
                        ->orWhereHas('room', fn (Builder $room) => $room->where('name_or_number', 'like', $like));

                    if (ctype_digit(ltrim($term, '#'))) {
                        $query->orWhere('id', (int) ltrim($term, '#'));
                    }
                });
            })
            ->latest('check_in')
            ->latest('id')
            ->limit(15)
            ->get();

        return response()->json($bookings->map(fn (Booking $booking) => self::bookingOption($booking))->values());
    }

    /**
     * The shape the booking picker shows and stores.
     *
     * @return array{id: int, label: string, sub: string}
     */
    public static function bookingOption(Booking $booking): array
    {
        $dates = $booking->booking_type === 'safari_only'
            ? optional($booking->safari_date)->format('d M Y')
            : $booking->check_in->format('d M').' – '.$booking->check_out->format('d M Y');

        return [
            'id' => $booking->id,
            'label' => '#'.$booking->id.' · '.$booking->customer_name,
            'sub' => trim($booking->room_label.' · '.$dates.' · '.ucfirst(str_replace('_', ' ', $booking->status)), ' ·'),
        ];
    }

    /**
     * @return Builder<Expense>
     */
    private function filteredQuery(Request $request, string $type): Builder
    {
        $query = Expense::query();

        if ($type === 'general') {
            $query->whereNull('booking_id');
        } elseif ($type === 'booking') {
            $query->whereNotNull('booking_id');
        }

        if ($category = trim((string) $request->query('category', ''))) {
            $query->where('category', $category);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('expense_date', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('expense_date', '<=', $to);
        }

        if ($term = trim((string) $request->query('q', ''))) {
            $like = '%'.addcslashes($term, '%_\\').'%';

            $query->where(function (Builder $query) use ($like) {
                $query->where('category', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('booking', fn (Builder $booking) => $booking->where('customer_name', 'like', $like));
            });
        }

        return $query;
    }

    /**
     * Carry the list's filters through the redirect after a save/delete.
     *
     * @return array<string, string>
     */
    private function keepFilters(Request $request): array
    {
        return array_filter($request->only(['q', 'type', 'category', 'from', 'to']), fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'description' => ['nullable', 'string', 'max:1000'],
            'expense_date' => ['required', 'date'],
            // Blank = a general villa expense; set = a direct cost for that booking.
            'expense_type' => ['nullable', 'in:general,booking'],
            'booking_id' => ['nullable', 'required_if:expense_type,booking', 'integer', 'exists:bookings,id'],
        ], [
            'booking_id.required_if' => 'Choose the booking this cost belongs to, or switch to a general expense.',
        ]);

        // A "general" expense is never linked, even if a stale booking id was submitted.
        $bookingId = ($validated['expense_type'] ?? null) === 'general' ? null : ($validated['booking_id'] ?? null);

        unset($validated['expense_type']);
        $validated['booking_id'] = $bookingId;

        return $validated;
    }
}
