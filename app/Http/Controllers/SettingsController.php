<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
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
            ]);

            $settings->fill($validated)->save();

            return back()->with('status', 'Public website settings updated successfully.');
        }

        $validated = $request->validate([
            'villa_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'currency' => ['required', 'string', 'max:8'],
            'villa_logo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('villa_logo')) {
            if ($settings->villa_logo) {
                Storage::disk('public')->delete($settings->villa_logo);
            }

            $validated['villa_logo'] = $request->file('villa_logo')->store('villa-logos', 'public');
        }

        // Setting::current() returns an unsaved default instance if no row
        // exists yet - save() inserts it the first time, updates it after.
        $settings->fill($validated)->save();

        return back()->with('status', 'Villa settings updated successfully.');
    }
}
