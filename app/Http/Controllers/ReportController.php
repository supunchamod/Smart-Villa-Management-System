<?php

namespace App\Http\Controllers;

use App\Support\ExpenseLedger;
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

        $incomeRows = collect();
        $expenseRows = collect();
        $totalIncome = 0.0;
        $totalExpenses = 0.0;
        $generalExpenses = 0.0;
        $bookingExpenses = 0.0;

        if ($reportType !== 'expense') {
            $incomeRows = IncomeLedger::between($start, $end);
            $totalIncome = $incomeRows->sum('amount');
        }

        if ($reportType !== 'income') {
            // General expenses plus costs recorded against individual bookings.
            $expenseRows = ExpenseLedger::between($start, $end);
            $totalExpenses = ExpenseLedger::total($expenseRows);
            $generalExpenses = ExpenseLedger::total($expenseRows->where('source', ExpenseLedger::GENERAL));
            $bookingExpenses = ExpenseLedger::total($expenseRows->where('source', ExpenseLedger::BOOKING));
        }

        // The P&L statement also itemises net profit per booking.
        $bookingProfits = $reportType === 'profit_loss' ? IncomeLedger::bookingProfitability($start, $end) : collect();

        $pdf = Pdf::loadView('reports.pdf', [
            'reportType' => $reportType,
            'periodLabel' => $start->format('d M Y').' – '.$end->format('d M Y'),
            'incomeRows' => $incomeRows,
            'expenseRows' => $expenseRows,
            'bookingProfits' => $bookingProfits,
            'bookingProfitTotals' => IncomeLedger::profitabilityTotals($bookingProfits),
            'totalIncome' => $totalIncome,
            'totalExpenses' => $totalExpenses,
            'generalExpenses' => $generalExpenses,
            'bookingExpenses' => $bookingExpenses,
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
