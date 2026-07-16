<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(): View
    {
        return view('invoices.index');
    }

    public function show(): View
    {
        return view('invoices.show');
    }
}
