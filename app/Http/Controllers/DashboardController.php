<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingSafariTrip;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use App\Support\ExpenseLedger;
use App\Support\IncomeLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Build the villa's operational snapshot: booking/room KPIs, a trailing
     * 7-day income vs expenses series for the overview chart, the next
     * upcoming check-ins, the active booking list, and a room status
     * breakdown.
     */
    public function index(Request $request): View
    {
        // Money (revenue, expenses, profit, payments, balances) is only for users holding the
        // view_finance permission - the owner always does; managers only if the owner granted it.
        // For everyone else those figures are not even computed, so nothing financial reaches the page.
        $canViewFinance = (bool) $request->user()?->can('view_finance');

        $today = today();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $previousMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = now()->subMonthNoOverflow()->endOfMonth();

        $totalBookings = Booking::count();
        $confirmedBookings = Booking::where('status', 'confirmed')->count();
        $pendingBookingsCount = Booking::where('status', 'pending')->count();

        $villaSlug = Str::slug(Setting::current()->villa_name);
        $publicVillaUrl = $villaSlug !== '' ? url('/v/'.$villaSlug) : null;

        $totalRooms = Room::count();
        $availableRooms = Room::where('status', 'available')->count();
        $maintenanceRooms = Room::where('status', 'maintenance')->count();
        $occupiedRooms = Booking::where('status', 'confirmed')
            ->whereNotNull('room_id')
            ->whereDate('check_in', '<=', $today)
            ->whereDate('check_out', '>=', $today)
            ->pluck('room_id')
            ->unique()
            ->count();

        $monthlyExpenses = $monthlyRevenue = $expensesTrend = $revenueTrend = null;

        if ($canViewFinance) {
            // Both general expenses and per-booking direct costs count as spending.
            $monthlyExpenses = ExpenseLedger::total(ExpenseLedger::between($monthStart, $monthEnd));
            $previousMonthlyExpenses = ExpenseLedger::total(ExpenseLedger::between($previousMonthStart, $previousMonthEnd));

            $monthlyRevenue = (float) IncomeLedger::between($monthStart, $monthEnd)->sum('amount');
            $previousMonthlyRevenue = (float) IncomeLedger::between($previousMonthStart, $previousMonthEnd)->sum('amount');

            $expensesTrend = $this->percentChange($monthlyExpenses, $previousMonthlyExpenses);
            $revenueTrend = $this->percentChange($monthlyRevenue, $previousMonthlyRevenue);
        }

        $checkedOutToday = Booking::where(function ($query) use ($today) {
            $query->whereDate('check_out', $today)
                ->orWhere(function ($query) use ($today) {
                    $query->where('status', 'checked_out')->whereDate('checked_out_at', $today);
                });
        })->count();

        // Trailing 7-day income vs expenses series for the overview chart (finance users only).
        $chartLabels = [];
        $chartIncome = [];
        $chartExpenses = [];

        if ($canViewFinance) {
            $chartStart = $today->copy()->subDays(6);
            $incomeEvents = IncomeLedger::between($chartStart->copy()->startOfDay(), $today->copy()->endOfDay());
            $expenseRows = ExpenseLedger::between($chartStart->copy()->startOfDay(), $today->copy()->endOfDay());

            $cursor = $chartStart->copy();

            while ($cursor->lte($today)) {
                $dayKey = $cursor->toDateString();

                $chartLabels[] = $cursor->format('D');
                $chartIncome[] = round(
                    $incomeEvents->filter(fn (array $event) => $event['date']->toDateString() === $dayKey)->sum('amount'),
                    2
                );
                $chartExpenses[] = round(
                    (float) $expenseRows->filter(fn (array $expense) => $expense['date']->toDateString() === $dayKey)->sum('amount'),
                    2
                );

                $cursor->addDay();
            }
        }

        // Safari excursions scheduled for today (operational view for everyone).
        $todaysSafariTrips = BookingSafariTrip::with('booking')
            ->whereDate('safari_date', $today)
            ->whereHas('booking', fn ($booking) => $booking->where('status', '!=', 'cancelled'))
            ->orderByRaw('pickup_time IS NULL')
            ->orderBy('pickup_time')
            ->get();

        // Room turnover widgets only ever concern accommodation stays - a
        // safari-only booking has no room, and its safari_date (which
        // check_in/check_out mirror) isn't a physical arrival/departure.
        $upcomingCheckIns = Booking::with('room')
            ->where('status', 'confirmed')
            ->where('booking_type', '!=', 'safari_only')
            ->whereDate('check_in', '>=', $today)
            ->orderBy('check_in')
            ->limit(4)
            ->get();

        // Mobile "Today's Arrivals" and "Today's Check-outs" sections drive
        // the day-focused workflow: only bookings still 'confirmed' show up
        // here, so a booking drops off the moment it's checked out.
        $todaysBookings = Booking::with('room')
            ->where('status', 'confirmed')
            ->where('booking_type', '!=', 'safari_only')
            ->whereDate('check_in', $today)
            ->orderByDesc('created_at')
            ->get();

        $todaysCheckouts = Booking::with(['room', 'extras', 'expenses'])
            ->where('status', 'confirmed')
            ->where('booking_type', '!=', 'safari_only')
            ->whereDate('check_out', $today)
            ->orderBy('check_out')
            ->get();

        $checkInsToday = $todaysBookings->count();

        $tomorrow = $today->copy()->addDay();
        $tomorrowCheckIns = Booking::with('room')
            ->where('status', 'confirmed')
            ->where('booking_type', '!=', 'safari_only')
            ->whereDate('check_in', $tomorrow)
            ->orderBy('check_in')
            ->get();

        $activeBookings = Booking::with('room')
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('check_in')
            ->limit(6)
            ->get();

        $staffMembers = User::where('role', 'manager')
            ->orderBy('name')
            ->limit(5)
            ->get();

        // Advance payments (recorded at booking time) and final settlements
        // (recorded at checkout), most recent first - money, so finance users only.
        $recentPayments = $canViewFinance
            ? IncomeLedger::events()->sortByDesc('date')->take(6)->values()
            : collect();

        return view('dashboard', [
            'today' => $today,
            'tomorrow' => $tomorrow,
            'totalBookings' => $totalBookings,
            'confirmedBookings' => $confirmedBookings,
            'pendingBookingsCount' => $pendingBookingsCount,
            'publicVillaUrl' => $publicVillaUrl,
            'totalRooms' => $totalRooms,
            'availableRooms' => $availableRooms,
            'maintenanceRooms' => $maintenanceRooms,
            'occupiedRooms' => $occupiedRooms,
            'canViewFinance' => $canViewFinance,
            'monthlyExpenses' => $monthlyExpenses,
            'expensesTrend' => $expensesTrend,
            'monthlyRevenue' => $monthlyRevenue,
            'revenueTrend' => $revenueTrend,
            'todaysSafariTrips' => $todaysSafariTrips,
            'checkedOutToday' => $checkedOutToday,
            'checkInsToday' => $checkInsToday,
            'chartLabels' => $chartLabels,
            'chartIncome' => $chartIncome,
            'chartExpenses' => $chartExpenses,
            'upcomingCheckIns' => $upcomingCheckIns,
            'todaysBookings' => $todaysBookings,
            'todaysCheckouts' => $todaysCheckouts,
            'tomorrowCheckIns' => $tomorrowCheckIns,
            'activeBookings' => $activeBookings,
            'staffMembers' => $staffMembers,
            'recentPayments' => $recentPayments,
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
