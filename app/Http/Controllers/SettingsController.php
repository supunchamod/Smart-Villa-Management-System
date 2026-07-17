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
     */
    public function edit(): View
    {
        return view('settings', [
            'settings' => Setting::current(),
        ]);
    }

    /**
     * Update the villa's settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $settings = Setting::current();

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
