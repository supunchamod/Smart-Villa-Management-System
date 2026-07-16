<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [PageController::class, 'dashboard'])->name('dashboard');
    Route::get('/calendar', [PageController::class, 'calendar'])->name('calendar');
    Route::get('/projects', [PageController::class, 'projects'])->name('projects.index');
    Route::get('/chat', [PageController::class, 'chat'])->name('chat');
    Route::get('/inbox', [PageController::class, 'inbox'])->name('inbox');
    Route::get('/file-manager', [PageController::class, 'fileManager'])->name('file-manager');
    Route::get('/products', [PageController::class, 'products'])->name('products.index');
    Route::get('/reports', [PageController::class, 'reports'])->name('reports');
    Route::get('/team', [PageController::class, 'team'])->name('team');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/details', [InvoiceController::class, 'show'])->name('invoices.show');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/details', [CustomerController::class, 'show'])->name('customers.show');

    Route::resource('rooms', RoomController::class)->except('show');
    Route::patch('/rooms/{room}/toggle-status', [RoomController::class, 'toggleStatus'])->name('rooms.toggle-status');

    Route::get('/api/bookings', [BookingController::class, 'calendarFeed'])->name('bookings.calendar');
    Route::resource('bookings', BookingController::class);
    Route::post('/bookings/{booking}/checkout', [BookingController::class, 'checkout'])->name('bookings.checkout');
    Route::get('/bookings/{booking}/invoice/confirmation', [BookingController::class, 'confirmationInvoice'])->name('bookings.invoice.confirmation');
    Route::get('/bookings/{booking}/invoice/final', [BookingController::class, 'finalInvoice'])->name('bookings.invoice.final');
});

require __DIR__.'/auth.php';
