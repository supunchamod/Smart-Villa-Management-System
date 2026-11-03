<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingExpense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Add / remove the direct costs recorded against a single booking. Reached
 * from the booking details page (plain form posts) and the checkout modal
 * (JSON, to delete an existing cost without leaving the modal).
 */
class BookingExpenseController extends Controller
{
    public function store(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        if ($booking->status === 'cancelled') {
            return $this->reply($request, false, 'Expenses cannot be added to a cancelled booking.', 422);
        }

        $expense = $booking->bookingExpenses()->create($request->validate([
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
        ]));

        return $this->reply($request, true, 'Booking expense added.', 200, ['expense' => $expense]);
    }

    public function destroy(Request $request, BookingExpense $bookingExpense): RedirectResponse|JsonResponse
    {
        $bookingExpense->delete();

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
