<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Where uploaded villa logos live, relative to the public/ directory
     * (rendered via Setting::logo_url, which wraps this in asset()).
     * Written directly under public/ rather than through the
     * storage/app/public disk + public/storage symlink, so the logo
     * displays without that symlink needing to exist.
     */
    private const LOGO_DIRECTORY = 'images/villa-logos';

    /**
     * Display the villa settings form.
     *
     * $globalSettings is already available here (and in every other view)
     * via the view composer registered in AppServiceProvider, so there's
     * nothing extra to pass.
     */
    public function edit(): View
    {
        return view('settings.index');
    }

    /**
     * Update the villa's settings. The form is split into two independent
     * sections (general workspace settings, and the public website/mini-CMS
     * fields) that each post here with their own hidden "section" marker,
     * so a submission only validates and saves the fields that section
     * actually renders - otherwise saving the website section alone would
     * fail validation on required general fields (like villa_name) it
     * never included.
     *
     * Cabana pricing (previously a single villa-wide half/full board rate
     * here) has moved entirely to the Landing Page section's per-cabana,
     * per-pax-tier pricing - see LandingPageController.
     */
    public function update(Request $request): RedirectResponse
    {
        $settings = Setting::current();

        if ($request->input('section') === 'website') {
            $validated = $request->validate([
                'website_logo_url' => ['nullable', 'url', 'max:2048'],
                'website_hero_image_url' => ['nullable', 'url', 'max:2048'],
                'website_hero_title' => ['nullable', 'string', 'max:255'],
                'website_hero_subtitle' => ['nullable', 'string', 'max:500'],
                'public_whatsapp_number' => ['nullable', 'string', 'max:30'],
                'review_link' => ['nullable', 'url', 'max:2048'],
            ]);

            $settings->fill($validated)->save();

            return back()->with('status', 'Public website settings updated successfully.');
        }

        $validated = $request->validate([
            'villa_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'google_maps_link' => ['nullable', 'url', 'max:2048'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'currency' => ['required', 'string', 'max:8'],
            'villa_logo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('villa_logo')) {
            $this->deleteLogo($settings->villa_logo);
            $validated['villa_logo'] = $this->storeLogo($request);
        }

        // Setting::current() returns an unsaved default instance if no row
        // exists yet - save() inserts it the first time, updates it after.
        $settings->fill($validated)->save();

        return back()->with('status', 'Villa settings updated successfully.');
    }

    /**
     * Moves an uploaded villa logo into public/images/villa-logos and
     * returns the relative path stored on the model - a timestamped
     * filename avoids any collision with the original upload's name.
     */
    private function storeLogo(Request $request): string
    {
        $file = $request->file('villa_logo');
        $filename = 'logo_'.time().'.'.$file->getClientOriginalExtension();

        $file->move(public_path(self::LOGO_DIRECTORY), $filename);

        return self::LOGO_DIRECTORY.'/'.$filename;
    }

    /**
     * Removes a previously uploaded villa logo from disk, if it exists.
     * Silently does nothing for a null/blank path or a file that's
     * already gone, since the caller doesn't need to distinguish those cases.
     */
    private function deleteLogo(?string $logoPath): void
    {
        if (! $logoPath) {
            return;
        }

        $path = public_path($logoPath);

        if (File::exists($path)) {
            File::delete($path);
        }
    }
}
