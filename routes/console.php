<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pre-check-in reminders and check-in day instructions - both are
// date-based (tomorrow's/today's check-ins), so one daily run covers
// both regardless of what time a booking happens to be viewed or edited.
Schedule::command('bookings:send-whatsapp-notifications')->dailyAt('08:00');
