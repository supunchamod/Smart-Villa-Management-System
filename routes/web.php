<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PageController;
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
});

require __DIR__.'/auth.php';
