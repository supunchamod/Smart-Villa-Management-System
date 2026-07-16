<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The template ships Bootstrap 5, not Tailwind, so paginated views
        // (rooms, bookings) render with matching markup out of the box.
        Paginator::useBootstrapFive();
    }
}
