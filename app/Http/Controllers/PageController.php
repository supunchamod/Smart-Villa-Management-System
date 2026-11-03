<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Support\BookingDetails;
use Illuminate\View\View;

class PageController extends Controller
{
    public function calendar(): View
    {
        // Real confirmed bookings, soonest check-in first.
        $upcoming = Booking::with(['room', 'extras'])->upcoming()->limit(6)->get();

        return view('calendar', [
            'upcomingBookings' => $upcoming,
            'upcomingPayloads' => $upcoming->mapWithKeys(fn (Booking $booking) => [$booking->id => BookingDetails::for($booking)]),
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
