<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class TeamController extends Controller
{
    /**
     * Display the villa's staff (the owner plus every manager).
     */
    public function index(): View
    {
        return view('team.index', [
            'members' => User::orderByRaw("role = 'owner' desc")->orderBy('name')->get(),
        ]);
    }

    /**
     * Add a new manager to the villa.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(array_keys(User::PERMISSIONS))],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'manager',
            'permissions' => $validated['permissions'] ?? [],
        ]);

        return redirect()->route('team')->with('status', 'Manager added successfully.');
    }

    /**
     * Update a manager's details and permissions.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($user->role !== 'manager', 403, 'Only managers can be edited here.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(array_keys(User::PERMISSIONS))],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'permissions' => $validated['permissions'] ?? [],
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('team')->with('status', 'Manager updated successfully.');
    }

    /**
     * Remove a manager from the villa.
     */
    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->role !== 'manager', 403, 'Only managers can be removed here.');

        $user->delete();

        return redirect()->route('team')->with('status', 'Manager removed successfully.');
    }
}
