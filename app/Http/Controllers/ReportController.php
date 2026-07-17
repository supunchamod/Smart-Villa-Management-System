<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Setting;
use App\Support\IncomeLedger;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    /**
     * Display the report type + date range selector.
     */
    public function index(): View
    {
        return view('reports.index');
    }

    /**
     * Generate and stream the requested financial statement as a PDF.
     */
    public function generate(Request $request): Response
    {
        $validated = $request->validate([
            'report_type' => ['required', Rule::in(['income', 'expense', 'profit_loss'])],
            'period' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'custom'])],
            'from' => ['required_if:period,custom', 'nullable', 'date'],
            'to' => ['required_if:period,custom', 'nullable', 'date', 'after_or_equal:from'],
        ]);

        [$start, $end] = $this->resolvePeriod(
            $validated['period'],
            $validated['from'] ?? null,
            $validated['to'] ?? null
        );

        $reportType = $validated['report_type'];
        $settings = Setting::current();

        $incomeRows = collect();
        $expenseRows = collect();
        $totalIncome = 0.0;
        $totalExpenses = 0.0;

        if ($reportType !== 'expense') {
            $incomeRows = IncomeLedger::between($start, $end);
            $totalIncome = $incomeRows->sum('amount');
        }

        if ($reportType !== 'income') {
            $expenseRows = Expense::whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
                ->orderBy('expense_date')
                ->get();
            $totalExpenses = (float) $expenseRows->sum('amount');
        }

        $pdf = Pdf::loadView('reports.pdf', [
            'settings' => $settings,
            'reportType' => $reportType,
            'periodLabel' => $start->format('d M Y').' – '.$end->format('d M Y'),
            'incomeRows' => $incomeRows,
            'expenseRows' => $expenseRows,
            'totalIncome' => $totalIncome,
            'totalExpenses' => $totalExpenses,
            'netProfit' => $totalIncome - $totalExpenses,
        ])->setPaper('a4', 'portrait');

        $filename = str_replace('_', '-', $reportType).'-report-'.now()->format('Ymd-His').'.pdf';

        return $pdf->stream($filename);
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function resolvePeriod(string $period, ?string $from, ?string $to): array
    {
        return match ($period) {
            'daily' => [now()->startOfDay(), now()->endOfDay()],
            'weekly' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
            'custom' => [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()],
        };
    }
}
