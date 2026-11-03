<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Support\ExpenseLedger;
use App\Support\IncomeLedger;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfitController extends Controller
{
    /**
     * Expense categories matched against this keyword list count as direct/
     * variable costs of running a stay (housekeeping, F&B, inventory, etc.)
     * and are deducted for Gross Profit. Everything else (salaries,
     * utilities, maintenance, marketing, ...) is treated as an operational/
     * fixed cost, deducted only for Net Profit. There is no separate
     * "direct cost" flag on the expenses table, so this is a transparent,
     * adjustable heuristic rather than a stored fact.
     */
    private const DIRECT_COST_KEYWORDS = [
        'housekeeping', 'laundry', 'food', 'beverage', 'f&b', 'fnb',
        'dining', 'kitchen', 'inventory', 'supplies', 'cabana', 'amenities',
        'safari', 'jeep', 'park ticket', 'guide',
    ];

    private const LEDGER_PER_PAGE = 12;

    private const TREND_BUCKETS = 12;

    private const UPCOMING_BOOKINGS_LIMIT = 4;

    private const BOOKING_PROFIT_ROWS = 25;

    /**
     * Villa Cabana's Profit Analyzer: top-line revenue/expense/profit
     * summary, an expense category breakdown, a trailing revenue-vs-net
     * profit trend (independent of the summary range, so it always shows
     * meaningful history), and a per-transaction ledger with pro-rated
     * expense allocation and a margin badge.
     */
    public function index(Request $request): View
    {
        $range = in_array($request->input('range'), ['today', 'week', 'month', 'custom'], true)
            ? $request->input('range')
            : 'month';

        [$start, $end, $rangeLabel] = $this->resolveRange($range, $request->input('from'), $request->input('to'));

        $granularity = $request->input('granularity') === 'weekly' ? 'weekly' : 'monthly';

        // Pulled once and reused for both the selected-range totals and the
        // trailing trend buckets, rather than re-querying per bucket.
        $allIncomeEvents = IncomeLedger::events();
        $historyStart = min($start->copy(), now()->subMonths(self::TREND_BUCKETS)->startOfMonth());
        // General expenses AND per-booking direct costs, so both reduce profit.
        $expenseHistory = ExpenseLedger::since($historyStart);

        $rangeIncomeEvents = $allIncomeEvents->filter(fn (array $event) => $event['date']->between($start, $end));
        $rangeExpenses = $expenseHistory->filter(fn (array $expense) => $expense['date']->between($start, $end));

        $totalRevenue = round((float) $rangeIncomeEvents->sum('amount'), 2);
        $totalExpenses = ExpenseLedger::total($rangeExpenses);
        $bookingExpenses = $rangeExpenses->where('source', ExpenseLedger::BOOKING);
        $bookingExpensesTotal = ExpenseLedger::total($bookingExpenses);
        $generalExpensesTotal = round($totalExpenses - $bookingExpensesTotal, 2);

        // Costs recorded against a specific booking are direct costs by definition; general
        // expenses count as direct only when their category matches the keyword list.
        $directCosts = round($bookingExpensesTotal + (float) $rangeExpenses
            ->where('source', ExpenseLedger::GENERAL)
            ->filter(fn (array $expense) => Str::contains(strtolower($expense['category']), self::DIRECT_COST_KEYWORDS))
            ->sum('amount'), 2);
        $operationalCosts = round($totalExpenses - $directCosts, 2);
        $grossProfit = round($totalRevenue - $directCosts, 2);
        $netProfit = round($totalRevenue - $totalExpenses, 2);

        $expenseBreakdown = $rangeExpenses
            ->groupBy(fn (array $expense) => $expense['source'] === ExpenseLedger::BOOKING ? $expense['category'].' (booking)' : $expense['category'])
            ->map(fn (Collection $rows, string $category) => [
                'category' => $category,
                'amount' => round((float) $rows->sum('amount'), 2),
            ])
            ->sortByDesc('amount')
            ->values();

        [$weeklyLabels, $weeklyRevenue, $weeklyNetProfit] = $this->buildTrend($allIncomeEvents, $expenseHistory, 'weekly');
        [$monthlyLabels, $monthlyRevenue, $monthlyNetProfit] = $this->buildTrend($allIncomeEvents, $expenseHistory, 'monthly');

        $ledgerRows = $this->buildLedger($rangeIncomeEvents, $totalRevenue, $generalExpensesTotal, $bookingExpenses);
        $ledger = $this->paginateLedger($ledgerRows, $request);

        // Net profit per booking: (room + extras) - that booking's direct costs.
        $bookingProfits = IncomeLedger::bookingProfitability($start, $end);

        // Independent of the summary range above, same reasoning as the
        // trend chart - "what's coming up" shouldn't disappear just because
        // the manager is looking at "Today" or a past custom range.
        $upcomingBookings = Booking::with('room')->upcoming()->limit(self::UPCOMING_BOOKINGS_LIMIT)->get();

        return view('profit.index', [
            'range' => $range,
            'rangeLabel' => $rangeLabel,
            'rangeFrom' => $start->toDateString(),
            'rangeTo' => $end->toDateString(),
            'granularity' => $granularity,
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
            'bookingExpensesTotal' => $bookingExpensesTotal,
            'directCosts' => $directCosts,
            'operationalCosts' => $operationalCosts,
            'grossProfit' => $grossProfit,
            'netProfit' => $netProfit,
            'expenseBreakdown' => $expenseBreakdown,
            'weeklyLabels' => $weeklyLabels,
            'weeklyRevenue' => $weeklyRevenue,
            'weeklyNetProfit' => $weeklyNetProfit,
            'monthlyLabels' => $monthlyLabels,
            'monthlyRevenue' => $monthlyRevenue,
            'monthlyNetProfit' => $monthlyNetProfit,
            'ledger' => $ledger,
            'upcomingBookings' => $upcomingBookings,
            'bookingProfits' => $bookingProfits->take(self::BOOKING_PROFIT_ROWS),
            'bookingProfitCount' => $bookingProfits->count(),
            'bookingProfitTotals' => IncomeLedger::profitabilityTotals($bookingProfits),
        ]);
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface, 2: string}
     */
    private function resolveRange(string $range, ?string $from, ?string $to): array
    {
        return match ($range) {
            'today' => [now()->startOfDay(), now()->endOfDay(), 'Today'],
            'week' => [now()->startOfWeek(), now()->endOfWeek(), 'This Week'],
            'custom' => $this->resolveCustomRange($from, $to),
            default => [now()->startOfMonth(), now()->endOfMonth(), 'This Month'],
        };
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface, 2: string}
     */
    private function resolveCustomRange(?string $from, ?string $to): array
    {
        try {
            $start = $from ? Carbon::parse($from)->startOfDay() : now()->startOfMonth();
            $end = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();
        } catch (\Exception) {
            $start = now()->startOfMonth();
            $end = now()->endOfDay();
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end, $start->format('d M Y').' – '.$end->format('d M Y')];
    }

    /**
     * Revenue-vs-net-profit trend over the trailing 12 weeks/months. This is
     * intentionally decoupled from the summary date-range filter above so
     * the trend chart always has enough history to be useful, even when
     * the manager is looking at "Today".
     *
     * @param  Collection<int, array{type: string, date: CarbonInterface, amount: float, booking: \App\Models\Booking}>  $incomeEvents
     * @param  Collection<int, array<string, mixed>>  $expenses
     * @return array{0: array<int, string>, 1: array<int, float>, 2: array<int, float>}
     */
    private function buildTrend(Collection $incomeEvents, Collection $expenses, string $unit): array
    {
        $labels = [];
        $revenueSeries = [];
        $netProfitSeries = [];

        for ($stepsAgo = self::TREND_BUCKETS - 1; $stepsAgo >= 0; $stepsAgo--) {
            if ($unit === 'weekly') {
                $bucketStart = now()->copy()->subWeeks($stepsAgo)->startOfWeek();
                $bucketEnd = $bucketStart->copy()->endOfWeek();
                $label = $bucketStart->format('d M');
            } else {
                $bucketStart = now()->copy()->subMonthsNoOverflow($stepsAgo)->startOfMonth();
                $bucketEnd = $bucketStart->copy()->endOfMonth();
                $label = $bucketStart->format('M Y');
            }

            $revenue = $incomeEvents
                ->filter(fn (array $event) => $event['date']->between($bucketStart, $bucketEnd))
                ->sum('amount');

            $expenseTotal = (float) $expenses
                ->filter(fn (array $expense) => $expense['date']->between($bucketStart, $bucketEnd))
                ->sum('amount');

            $labels[] = $label;
            $revenueSeries[] = round($revenue, 2);
            $netProfitSeries[] = round($revenue - $expenseTotal, 2);
        }

        return [$labels, $revenueSeries, $netProfitSeries];
    }

    /**
     * One row per cash-in event (advance payment or final settlement),
     * newest first. Expenses are attributed two ways:
     *
     *  - Direct costs recorded against a booking are charged to that
     *    booking's own rows (split by each row's share of the booking's
     *    revenue in the period) - a logged fact, not an estimate.
     *  - General expenses (rent, salaries...) have no booking, so they are
     *    still allocated pro-rata by each row's share of period revenue.
     *
     * A booking's direct costs recorded in a period where it has no cash-in
     * event stay in the period totals but can't be shown on any row.
     *
     * @param  Collection<int, array{type: string, date: CarbonInterface, amount: float, booking: \App\Models\Booking}>  $incomeEvents
     * @param  Collection<int, array<string, mixed>>  $bookingExpenses
     * @return Collection<int, array<string, mixed>>
     */
    private function buildLedger(Collection $incomeEvents, float $totalRevenue, float $generalExpenses, Collection $bookingExpenses): Collection
    {
        $directCostByBooking = $bookingExpenses
            ->groupBy(fn (array $expense) => $expense['booking']?->id)
            ->map(fn (Collection $rows) => (float) $rows->sum('amount'));

        $revenueByBooking = $incomeEvents
            ->groupBy(fn (array $event) => $event['booking']->id)
            ->map(fn (Collection $rows) => (float) $rows->sum('amount'));

        return $incomeEvents
            ->sortByDesc('date')
            ->values()
            ->map(function (array $event) use ($totalRevenue, $generalExpenses, $directCostByBooking, $revenueByBooking) {
                $booking = $event['booking'];

                $bookingRevenue = (float) ($revenueByBooking[$booking->id] ?? 0);
                $directExpense = $bookingRevenue > 0
                    ? ($event['amount'] / $bookingRevenue) * (float) ($directCostByBooking[$booking->id] ?? 0)
                    : 0.0;
                $generalShare = $totalRevenue > 0 ? ($event['amount'] / $totalRevenue) * $generalExpenses : 0.0;

                $allocatedExpense = $generalShare + $directExpense;
                $netProfit = $event['amount'] - $allocatedExpense;
                $margin = $event['amount'] > 0 ? ($netProfit / $event['amount']) * 100 : 0.0;

                return [
                    'date' => $event['date'],
                    'transaction_id' => sprintf('%s-%04d', $event['type'] === 'Advance Payment' ? 'ADV' : 'SET', $booking->id),
                    'customer_name' => $booking->customer_name,
                    'source' => $booking->room?->type ?: 'Room Booking',
                    'gross_revenue' => round($event['amount'], 2),
                    'direct_expense' => round($directExpense, 2),
                    'expense_allocated' => round($allocatedExpense, 2),
                    'net_profit' => round($netProfit, 2),
                    'margin' => round($margin, 2),
                    'status' => $margin >= 50 ? 'high' : ($margin >= 25 ? 'standard' : 'low'),
                ];
            });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginateLedger(Collection $rows, Request $request): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $rows->forPage($page, self::LEDGER_PER_PAGE)->values(),
            $rows->count(),
            self::LEDGER_PER_PAGE,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );
    }
}
