<?php

namespace App\Http\Controllers;

use App\Models\Expense;
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
    ];

    private const LEDGER_PER_PAGE = 12;

    private const TREND_BUCKETS = 12;

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
        $expenseHistory = Expense::whereDate('expense_date', '>=', $historyStart->toDateString())->get();

        $rangeIncomeEvents = $allIncomeEvents->filter(fn (array $event) => $event['date']->between($start, $end));
        $rangeExpenses = $expenseHistory->filter(fn (Expense $expense) => $expense->expense_date->between($start, $end));

        $totalRevenue = round((float) $rangeIncomeEvents->sum('amount'), 2);
        $totalExpenses = round((float) $rangeExpenses->sum('amount'), 2);
        $directCosts = round((float) $rangeExpenses
            ->filter(fn (Expense $expense) => Str::contains(strtolower($expense->category), self::DIRECT_COST_KEYWORDS))
            ->sum('amount'), 2);
        $operationalCosts = round($totalExpenses - $directCosts, 2);
        $grossProfit = round($totalRevenue - $directCosts, 2);
        $netProfit = round($totalRevenue - $totalExpenses, 2);

        $expenseBreakdown = $rangeExpenses
            ->groupBy(fn (Expense $expense) => trim((string) $expense->category) !== '' ? trim($expense->category) : 'Uncategorized')
            ->map(fn (Collection $rows, string $category) => [
                'category' => $category,
                'amount' => round((float) $rows->sum('amount'), 2),
            ])
            ->sortByDesc('amount')
            ->values();

        [$weeklyLabels, $weeklyRevenue, $weeklyNetProfit] = $this->buildTrend($allIncomeEvents, $expenseHistory, 'weekly');
        [$monthlyLabels, $monthlyRevenue, $monthlyNetProfit] = $this->buildTrend($allIncomeEvents, $expenseHistory, 'monthly');

        $ledgerRows = $this->buildLedger($rangeIncomeEvents, $totalRevenue, $totalExpenses);
        $ledger = $this->paginateLedger($ledgerRows, $request);

        return view('profit.index', [
            'range' => $range,
            'rangeLabel' => $rangeLabel,
            'rangeFrom' => $start->toDateString(),
            'rangeTo' => $end->toDateString(),
            'granularity' => $granularity,
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
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
     * @param  Collection<int, Expense>  $expenses
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
                ->filter(fn (Expense $expense) => $expense->expense_date->between($bucketStart, $bucketEnd))
                ->sum('amount');

            $labels[] = $label;
            $revenueSeries[] = round($revenue, 2);
            $netProfitSeries[] = round($revenue - $expenseTotal, 2);
        }

        return [$labels, $revenueSeries, $netProfitSeries];
    }

    /**
     * One row per cash-in event (advance payment or final settlement),
     * newest first, with the period's total expenses allocated to each row
     * pro-rata by its share of period revenue - there's no per-booking
     * expense tracking, so this is a labelled allocation, not a logged fact.
     *
     * @param  Collection<int, array{type: string, date: CarbonInterface, amount: float, booking: \App\Models\Booking}>  $incomeEvents
     * @return Collection<int, array<string, mixed>>
     */
    private function buildLedger(Collection $incomeEvents, float $totalRevenue, float $totalExpenses): Collection
    {
        return $incomeEvents
            ->sortByDesc('date')
            ->values()
            ->map(function (array $event) use ($totalRevenue, $totalExpenses) {
                $booking = $event['booking'];
                $allocatedExpense = $totalRevenue > 0 ? ($event['amount'] / $totalRevenue) * $totalExpenses : 0.0;
                $netProfit = $event['amount'] - $allocatedExpense;
                $margin = $event['amount'] > 0 ? ($netProfit / $event['amount']) * 100 : 0.0;

                return [
                    'date' => $event['date'],
                    'transaction_id' => sprintf('%s-%04d', $event['type'] === 'Advance Payment' ? 'ADV' : 'SET', $booking->id),
                    'customer_name' => $booking->customer_name,
                    'source' => $booking->room?->type ?: 'Room Booking',
                    'gross_revenue' => round($event['amount'], 2),
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
