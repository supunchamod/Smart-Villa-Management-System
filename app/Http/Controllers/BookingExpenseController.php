<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Quick add / remove of the direct costs linked to a single booking, from
 * the booking pages (details page, checkout modal, "View Details" popup).
 * They are ordinary rows of the central expenses table with booking_id set;
 * the full Expenses section manages the same records.
 */
class BookingExpenseController extends Controller
{
    public function store(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        if ($booking->status === 'cancelled') {
            return $this->reply($request, false, 'Expenses cannot be added to a cancelled booking.', 422);
        }

        $validated = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'expense_date' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $expense = $booking->expenses()->create([
            ...$validated,
            'expense_date' => $validated['expense_date'] ?? today()->toDateString(),
        ]);

        return $this->reply($request, true, 'Booking expense added.', 200, ['expense' => $expense->toListItem()]);
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse|JsonResponse
    {
        // This endpoint only manages booking-linked costs.
        abort_if($expense->booking_id === null, 404);

        if ($expense->isPartnerPayment()) {
            return $this->reply($request, false, 'This is a partner resort payment. Manage it in Partner Resort Bookings.', 422);
        }

        $expense->delete();

        return $this->reply($request, true, 'Booking expense removed.');
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function reply(Request $request, bool $ok, string $message, int $status = 200, array $extra = []): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => $ok, 'message' => $message] + $extra, $status);
        }

        return back()->with($ok ? 'status' : 'error', $message);
    }
}
