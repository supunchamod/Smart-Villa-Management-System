<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * This is a single-tenant, whitelabel install, so both of these are
     * effectively singletons: one settings row for villa branding (so the
     * app never hits a null-branding error on first load) and one
     * pre-seeded owner account so there's always a way to log in.
     */
    public function run(): void
    {
        Setting::firstOrCreate([], [
            'villa_name' => 'My Villa',
            'currency' => 'LKR',
        ]);

        // Dev-only default credentials - change the password immediately
        // on a real deployment.
        User::firstOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => 'Villa Owner',
                'password' => Hash::make('password'),
                'role' => 'owner',
            ]
        );
    }
}
