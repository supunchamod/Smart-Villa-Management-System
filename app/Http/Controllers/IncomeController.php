<?php

namespace App\Http\Controllers;

use App\Support\IncomeLedger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncomeController extends Controller
{
    /**
     * Display the villa's incoming cash flows (advance payments and final
     * settlements), optionally filtered to a date range.
     */
    public function index(Request $request): View
    {
        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : null;
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : null;

        $rows = IncomeLedger::events()
            ->when($from, fn ($rows) => $rows->filter(fn (array $row) => $row['date']->gte($from)))
            ->when($to, fn ($rows) => $rows->filter(fn (array $row) => $row['date']->lte($to)))
            ->sortByDesc('date')
            ->values();

        return view('income.index', [
            'rows' => $rows,
            'total' => $rows->sum('amount'),
            'advanceTotal' => $rows->where('type', 'Advance Payment')->sum('amount'),
            'settlementTotal' => $rows->where('type', 'Final Settlement')->sum('amount'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ]);
    }
}
