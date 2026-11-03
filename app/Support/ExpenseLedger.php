<?php

namespace App\Support;

use App\Models\BookingExpense;
use App\Models\Expense;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Every cost the villa has recorded, from both sources, in one shape:
 *
 *  - 'general': the Expenses page (rent, salaries, utilities...), dated by expense_date.
 *  - 'booking': direct costs recorded against a single booking (external
 *    meals, transport...), dated by the day the cost was incurred (expense_date).
 *
 * The profit analyzer, dashboard and reports read this instead of querying
 * Expense directly, so booking-specific costs always reduce net profit.
 *
 * Each item: source, date (Carbon), category, description, amount (float),
 * booking (Booking|null - only for 'booking' items).
 */
class ExpenseLedger
{
    public const GENERAL = 'general';

    public const BOOKING = 'booking';

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function since(CarbonInterface $from): Collection
    {
        return self::load($from, null);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function between(CarbonInterface $start, CarbonInterface $end): Collection
    {
        return self::load($start, $end);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function load(CarbonInterface $from, ?CarbonInterface $to): Collection
    {
        $general = Expense::query()
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->when($to, fn ($query) => $query->whereDate('expense_date', '<=', $to->toDateString()))
            ->get()
            ->map(fn (Expense $expense) => [
                'source' => self::GENERAL,
                'date' => $expense->expense_date->copy(),
                'category' => trim((string) $expense->category) !== '' ? trim($expense->category) : 'Uncategorized',
                'description' => $expense->description,
                'amount' => (float) $expense->amount,
                'booking' => null,
            ]);

        $booking = BookingExpense::query()
            ->with('booking')
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->when($to, fn ($query) => $query->whereDate('expense_date', '<=', $to->toDateString()))
            ->get()
            ->map(fn (BookingExpense $expense) => [
                'source' => self::BOOKING,
                'date' => $expense->incurred_on,
                'category' => trim((string) $expense->category) !== '' ? trim($expense->category) : 'Uncategorized',
                'description' => $expense->description,
                'amount' => (float) $expense->amount,
                'booking' => $expense->booking,
            ]);

        return $general->concat($booking)->sortBy('date')->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     */
    public static function total(Collection $items): float
    {
        return round((float) $items->sum('amount'), 2);
    }
}
