<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Expense;
use App\Models\Room;
use App\Support\IncomeLedger;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Build the villa's operational snapshot: booking/room KPIs, a trailing
     * 7-day income vs expenses series for the overview chart, the next
     * upcoming check-ins, the active booking list, and a room status
     * breakdown.
     */
    public function index(): View
    {
        $today = today();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $previousMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = now()->subMonthNoOverflow()->endOfMonth();

        $totalBookings = Booking::count();
        $confirmedBookings = Booking::where('status', 'confirmed')->count();

        $totalRooms = Room::count();
        $availableRooms = Room::where('status', 'available')->count();
        $maintenanceRooms = Room::where('status', 'maintenance')->count();
        $occupiedRooms = Booking::where('status', 'confirmed')
            ->whereDate('check_in', '<=', $today)
            ->whereDate('check_out', '>=', $today)
            ->pluck('room_id')
            ->unique()
            ->count();

        $monthlyExpenses = (float) Expense::whereBetween('expense_date', [$monthStart->toDateString(), $monthEnd->toDateString()])->sum('amount');
        $previousMonthlyExpenses = (float) Expense::whereBetween('expense_date', [$previousMonthStart->toDateString(), $previousMonthEnd->toDateString()])->sum('amount');

        $monthlyRevenue = (float) IncomeLedger::between($monthStart, $monthEnd)->sum('amount');
        $previousMonthlyRevenue = (float) IncomeLedger::between($previousMonthStart, $previousMonthEnd)->sum('amount');

        $checkedOutToday = Booking::where(function ($query) use ($today) {
            $query->whereDate('check_out', $today)
                ->orWhere(function ($query) use ($today) {
                    $query->where('status', 'checked_out')->whereDate('checked_out_at', $today);
                });
        })->count();

        // Trailing 7-day income vs expenses series for the overview chart.
        $chartStart = $today->copy()->subDays(6);
        $incomeEvents = IncomeLedger::between($chartStart->copy()->startOfDay(), $today->copy()->endOfDay());
        $expenseRows = Expense::whereBetween('expense_date', [$chartStart->toDateString(), $today->toDateString()])->get();

        $chartLabels = [];
        $chartIncome = [];
        $chartExpenses = [];
        $cursor = $chartStart->copy();

        while ($cursor->lte($today)) {
            $dayKey = $cursor->toDateString();

            $chartLabels[] = $cursor->format('D');
            $chartIncome[] = round(
                $incomeEvents->filter(fn (array $event) => $event['date']->toDateString() === $dayKey)->sum('amount'),
                2
            );
            $chartExpenses[] = round(
                (float) $expenseRows->filter(fn (Expense $expense) => $expense->expense_date->toDateString() === $dayKey)->sum('amount'),
                2
            );

            $cursor->addDay();
        }

        $upcomingCheckIns = Booking::with('room')
            ->where('status', 'confirmed')
            ->whereDate('check_in', '>=', $today)
            ->orderBy('check_in')
            ->limit(4)
            ->get();

        $activeBookings = Booking::with('room')
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('check_in')
            ->limit(6)
            ->get();

        return view('dashboard', [
            'totalBookings' => $totalBookings,
            'confirmedBookings' => $confirmedBookings,
            'totalRooms' => $totalRooms,
            'availableRooms' => $availableRooms,
            'maintenanceRooms' => $maintenanceRooms,
            'occupiedRooms' => $occupiedRooms,
            'monthlyExpenses' => $monthlyExpenses,
            'expensesTrend' => $this->percentChange($monthlyExpenses, $previousMonthlyExpenses),
            'monthlyRevenue' => $monthlyRevenue,
            'revenueTrend' => $this->percentChange($monthlyRevenue, $previousMonthlyRevenue),
            'checkedOutToday' => $checkedOutToday,
            'chartLabels' => $chartLabels,
            'chartIncome' => $chartIncome,
            'chartExpenses' => $chartExpenses,
            'upcomingCheckIns' => $upcomingCheckIns,
            'activeBookings' => $activeBookings,
        ]);
    }

    /**
     * Percentage change from $previous to $current, or null when there's no
     * previous-period baseline to compare against (avoids a division by
     * zero and avoids implying a trend that isn't real).
     */
    private function percentChange(float $current, float $previous): ?float
    {
        if ($previous <= 0.0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}
