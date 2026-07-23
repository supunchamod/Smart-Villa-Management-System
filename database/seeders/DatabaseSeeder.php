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
            'villa_name' => 'Ceylon Cabana & Villa',
            'villa_logo' => null,
            'address' => '123, Galle Road, Hikkaduwa',
            'phone_number' => '+94771234567',
            'public_whatsapp_number' => '+94771234567',
            'email' => 'info@ceylonvilla.com',
            'currency' => 'LKR',
            'website_hero_title' => 'Ceylon Cabana & Villa',
            'website_hero_subtitle' => "A private cabana escape on Sri Lanka's coast - handpicked rooms, a personalised meal plan, and the best rate you'll find anywhere, guaranteed direct.",
            'half_board_rate' => 3500,
            'full_board_rate' => 6000,
        ]);

        // Dev-only default credentials - change the password immediately
        // on a real deployment.
        User::firstOrCreate(
            ['email' => 'admin@villa.com'],
            [
                'name' => 'Supun Chamod',
                'password' => Hash::make('admin123'),
                'role' => 'owner',
            ]
        );

        // Sales-demo rooms, bookings, and expenses - see DemoDataSeeder for
        // details. Safe to re-run: it only clears out the demo data it
        // manages, never a blanket truncate.
        $this->call(DemoDataSeeder::class);
    }
}
