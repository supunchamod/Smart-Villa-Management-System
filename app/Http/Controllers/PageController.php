<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\View\View;

class PageController extends Controller
{
    public function calendar(): View
    {
        return view('calendar', [
            'upcomingBookings' => Booking::with('room')->upcoming()->limit(4)->get(),
        ]);
    }

    public function projects(): View
    {
        return view('projects');
    }

    public function chat(): View
    {
        return view('chat');
    }

    public function inbox(): View
    {
        return view('inbox');
    }

    public function fileManager(): View
    {
        return view('file-manager');
    }

    public function products(): View
    {
        return view('products');
    }
}
