<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Support\IncomeLedger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfitController extends Controller
{
    /**
     * Display revenue, expenses, and net profit for the selected period,
     * plus a day-by-day income vs expenses series for the chart.
     */
    public function index(Request $request): View
    {
        $period = in_array($request->input('period'), ['daily', 'weekly', 'monthly'], true)
            ? $request->input('period')
            : 'monthly';

        [$start, $end] = match ($period) {
            'daily' => [now()->startOfDay(), now()->endOfDay()],
            'weekly' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };

        $incomeEvents = IncomeLedger::between($start, $end);
        $expenseRows = Expense::whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])->get();

        $totalRevenue = $incomeEvents->sum('amount');
        $totalExpenses = (float) $expenseRows->sum('amount');

        $labels = [];
        $incomeSeries = [];
        $expenseSeries = [];

        $cursor = $start->copy()->startOfDay();
        $lastDay = $end->copy()->startOfDay();

        while ($cursor->lte($lastDay)) {
            $dayKey = $cursor->toDateString();

            $labels[] = $cursor->format('d M');
            $incomeSeries[] = round(
                $incomeEvents->filter(fn (array $event) => $event['date']->toDateString() === $dayKey)->sum('amount'),
                2
            );
            $expenseSeries[] = round(
                (float) $expenseRows->filter(fn (Expense $expense) => $expense->expense_date->toDateString() === $dayKey)->sum('amount'),
                2
            );

            $cursor->addDay();
        }

        return view('profit.index', [
            'period' => $period,
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
            'netProfit' => $totalRevenue - $totalExpenses,
            'chartLabels' => $labels,
            'chartIncome' => $incomeSeries,
            'chartExpenses' => $expenseSeries,
        ]);
    }
}
