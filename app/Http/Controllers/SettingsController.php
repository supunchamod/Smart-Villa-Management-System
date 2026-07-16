<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Display the villa settings form.
     */
    public function edit(Request $request): View
    {
        return view('settings', [
            'villa' => $request->user()->villa,
        ]);
    }

    /**
     * Update the authenticated user's villa settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $villa = $request->user()->villa;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'currency' => ['required', 'string', 'max:8'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            if ($villa->logo) {
                Storage::disk('public')->delete($villa->logo);
            }

            $validated['logo'] = $request->file('logo')->store('villa-logos', 'public');
        }

        $villa->update($validated);

        return back()->with('status', 'Villa settings updated successfully.');
    }
}
