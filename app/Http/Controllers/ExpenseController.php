<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the villa's expenses.
     */
    public function index(): View
    {
        return view('expenses.index', [
            'expenses' => Expense::latest('expense_date')->paginate(10),
        ]);
    }

    /**
     * Store a newly created expense.
     */
    public function store(Request $request): RedirectResponse
    {
        Expense::create($this->validated($request));

        return redirect()->route('expenses.index')->with('status', 'Expense added successfully.');
    }

    /**
     * Update the given expense.
     */
    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $expense->update($this->validated($request));

        return redirect()->route('expenses.index')->with('status', 'Expense updated successfully.');
    }

    /**
     * Remove the given expense.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('status', 'Expense deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'category' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:1000'],
            'expense_date' => ['required', 'date'],
        ]);
    }
}
