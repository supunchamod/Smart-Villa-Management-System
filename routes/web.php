<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfitController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/projects', [PageController::class, 'projects'])->name('projects.index');
    Route::get('/chat', [PageController::class, 'chat'])->name('chat');
    Route::get('/inbox', [PageController::class, 'inbox'])->name('inbox');
    Route::get('/file-manager', [PageController::class, 'fileManager'])->name('file-manager');
    Route::get('/products', [PageController::class, 'products'])->name('products.index');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/details', [InvoiceController::class, 'show'])->name('invoices.show');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/details', [CustomerController::class, 'show'])->name('customers.show');

    Route::resource('rooms', RoomController::class)->except('show');
    Route::patch('/rooms/{room}/toggle-status', [RoomController::class, 'toggleStatus'])->name('rooms.toggle-status');

    // Bookings and the calendar are both booking-management surfaces, so
    // both sit behind the same permission.
    Route::middleware('can:manage_bookings')->group(function () {
        Route::get('/calendar', [PageController::class, 'calendar'])->name('calendar');
        Route::get('/api/bookings', [BookingController::class, 'calendarFeed'])->name('bookings.calendar');
        Route::resource('bookings', BookingController::class);
        Route::post('/bookings/{booking}/checkout', [BookingController::class, 'checkout'])->name('bookings.checkout');
        Route::get('/bookings/{booking}/invoice/confirmation', [BookingController::class, 'confirmationInvoice'])->name('bookings.invoice.confirmation');
        Route::get('/bookings/{booking}/invoice/final', [BookingController::class, 'finalInvoice'])->name('bookings.invoice.final');
    });

    Route::middleware('can:manage_expenses')->group(function () {
        Route::resource('expenses', ExpenseController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    // Income, Profit Analyzer, and Reports (including generating a report
    // PDF) are all gated by the single view_finance permission.
    Route::middleware('can:view_finance')->group(function () {
        Route::get('/income', [IncomeController::class, 'index'])->name('income.index');
        Route::get('/profit', [ProfitController::class, 'index'])->name('profit.index');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::get('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
    });

    // Managing staff and their permissions is owner-only.
    Route::middleware('can:manage-team')->group(function () {
        Route::get('/team', [TeamController::class, 'index'])->name('team');
        Route::post('/team', [TeamController::class, 'store'])->name('team.store');
        Route::put('/team/{user}', [TeamController::class, 'update'])->name('team.update');
        Route::delete('/team/{user}', [TeamController::class, 'destroy'])->name('team.destroy');
    });
});

require __DIR__.'/auth.php';
