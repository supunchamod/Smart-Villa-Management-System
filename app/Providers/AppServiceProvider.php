<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

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

        // One Gate per granular manager permission - backs both @can(...)
        // in Blade and the can:<permission> route middleware, so an
        // unauthorized request gets a 403 automatically with no extra
        // plumbing. Owners always pass, via User::hasPermission().
        foreach (array_keys(User::PERMISSIONS) as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }

        // Managing managers (adding/editing/removing staff and toggling
        // their permissions) is an owner-only capability, not one of the
        // granular permissions above - a manager must never be able to
        // grant themselves more access.
        Gate::define('manage-team', fn (User $user) => $user->isOwner());

        // Every view (including partials rendered via @include, like the
        // sidebar) gets $globalSettings for free - villa branding never
        // needs to be fetched or passed manually. The static cache keeps
        // this to a single query per request even though the composer
        // fires once per rendered view/partial.
        View::composer('*', function (ViewInstance $view) {
            static $settings;
            $settings ??= Setting::current();
            $view->with('globalSettings', $settings);
        });
    }
}
