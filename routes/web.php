<?php

use App\Http\Controllers\AuthPageController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', [PageController::class, 'dashboard'])->name('dashboard');
Route::get('/calendar', [PageController::class, 'calendar'])->name('calendar');
Route::get('/projects', [PageController::class, 'projects'])->name('projects.index');
Route::get('/chat', [PageController::class, 'chat'])->name('chat');
Route::get('/inbox', [PageController::class, 'inbox'])->name('inbox');
Route::get('/file-manager', [PageController::class, 'fileManager'])->name('file-manager');
Route::get('/products', [PageController::class, 'products'])->name('products.index');
Route::get('/settings', [PageController::class, 'settings'])->name('settings');
Route::get('/reports', [PageController::class, 'reports'])->name('reports');
Route::get('/team', [PageController::class, 'team'])->name('team');

Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
Route::get('/invoices/details', [InvoiceController::class, 'show'])->name('invoices.show');

Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
Route::get('/customers/details', [CustomerController::class, 'show'])->name('customers.show');

Route::get('/login', [AuthPageController::class, 'login'])->name('login');
Route::get('/register', [AuthPageController::class, 'register'])->name('register');
