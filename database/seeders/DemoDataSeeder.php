<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Expense;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Demo data for Star Moon Cabana - Kalupahana: the two real cabana
 * products, a local + foreign guest mix, bookings spanning past/present/
 * future, and an expense history varied enough for the Profit Analyzer's
 * charts to show real up-and-down trends instead of flat lines.
 *
 * Re-runnable: every `php artisan db:seed` first retires any older
 * generic demo rooms left over from before this villa was Star Moon
 * Cabana (cascade-deleting their bookings), then clears out this
 * seeder's own bookings/expenses and reseeds a fresh, internally
 * consistent dataset against just the two Star Moon cabanas.
 *
 * Schema note: the app has no separate Guest, "booking channel", or
 * revenue-category (Restaurant/Spa/Safari) table - guests live directly on
 * Booking (customer_name/email/phone), and all revenue is derived from
 * Booking.advance_payment/final_settlement_amount via IncomeLedger. This
 * seeder works within that real schema rather than inventing columns the
 * UI has nowhere to display; the Sri Lankan/foreign guest mix is carried
 * entirely through guest names, emails, and phone formats.
 */
class DemoDataSeeder extends Seeder
{
    /**
     * Sri Lankan local guests and foreign tourists, each with an
     * email/phone format matching their origin.
     */
    private const GUESTS = [
        ['name' => 'Chaminda Silva', 'email' => 'chaminda.silva@gmail.com', 'phone' => '+94 77 123 4567'],
        ['name' => 'Anura Perera', 'email' => 'anura.perera@yahoo.com', 'phone' => '+94 71 234 5678'],
        ['name' => 'Priyantha Fernando', 'email' => 'priyantha.fernando@gmail.com', 'phone' => '+94 76 345 6789'],
        ['name' => 'Nadeeka Jayasuriya', 'email' => 'nadeeka.jayasuriya@gmail.com', 'phone' => '+94 70 456 7890'],
        ['name' => 'Kumari Wickramasinghe', 'email' => 'kumari.wickramasinghe@hotmail.com', 'phone' => '+94 77 567 8901'],
        ['name' => 'John Doe', 'email' => 'john.doe@outlook.co.uk', 'phone' => '+44 7911 123456'],
        ['name' => 'Elena Rostova', 'email' => 'elena.rostova@web.de', 'phone' => '+49 151 2345678'],
        ['name' => 'Yuki Tanaka', 'email' => 'yuki.tanaka@docomo.ne.jp', 'phone' => '+81 90 1234 5678'],
        ['name' => 'Michael Thompson', 'email' => 'michael.thompson@gmail.com', 'phone' => '+1 415 555 0134'],
        ['name' => 'Sophie Martin', 'email' => 'sophie.martin@laposte.net', 'phone' => '+33 6 12 34 56 78'],
    ];

    /**
     * Generic placeholder cabanas seeded before this villa was rebranded to
     * Star Moon Cabana - Kalupahana. Retired on every seed run (see
     * retireLegacyRooms()) so only the two real cabanas below exist
     * anywhere in the app, including the public booking page.
     */
    private const LEGACY_ROOM_NAMES = [
        'Luxury Ocean-View Cabana A',
        'Premium Garden Cabana B',
        'Deluxe Family Villa Suite',
        'Standard Honeymoon Cabana C',
        'Premium Lakeside Cabana D',
        'Eco-Wooden Cabana E',
    ];

    /**
     * The two real Star Moon Cabana - Kalupahana products (see
     * PublicBookingController and Room::rateForGuests()). The Family
     * Two-Story Cabana's pricing_tiers key is "max guests for this rate".
     */
    private const ROOMS = [
        [
            'name_or_number' => 'Vintage Couple Cabana',
            'type' => 'Romantic Cabana',
            'price_per_night' => 12500,
            'pricing_tiers' => null,
            'capacity' => 2,
            'status' => 'available',
        ],
        [
            'name_or_number' => 'Family Two-Story Cabana',
            'type' => 'Family Cabana',
            'price_per_night' => 12500,
            'pricing_tiers' => ['2' => 12500, '4' => 15000, '6' => 20000, '8' => 25000],
            'capacity' => 8,
            'status' => 'available',
        ],
    ];

    /**
     * [room index, guest index, check-in offset from today in days,
     * nights, pax (guest count - drives the Family Two-Story Cabana's
     * tiered rate), advance-payment ratio, status]
     *
     * Every room's date ranges are checked to never overlap another
     * *non-cancelled* booking on the same room, so the calendar never
     * shows a double-booking.
     */
    private const BOOKING_PLAN = [
        // Completed stays over the past ~2 months (mixed advance ratios so
        // both Advance Payment and Final Settlement income events exist).
        [0, 5, -58, 3, 2, 0.5, 'checked_out'],
        [1, 6, -50, 4, 6, 0.6, 'checked_out'],
        [0, 0, -44, 2, 2, 1.0, 'checked_out'],
        [1, 7, -37, 5, 8, 0.4, 'checked_out'],
        [0, 1, -30, 2, 2, 0.7, 'checked_out'],
        [1, 8, -21, 3, 4, 0.5, 'checked_out'],
        [0, 2, -16, 2, 2, 0.6, 'checked_out'],
        // Currently in-house (checked in, still "confirmed" - checkout
        // hasn't happened yet).
        [1, 9, -2, 5, 6, 1.0, 'confirmed'],
        [0, 3, -1, 3, 2, 0.6, 'confirmed'],
        // Upcoming, paid in full ahead of arrival.
        [1, 4, 4, 3, 4, 1.0, 'confirmed'],
        [0, 5, 9, 2, 2, 1.0, 'confirmed'],
        // Upcoming with only a deposit paid ("pending payment" flavour -
        // there's no separate status for this, so it's carried by a low
        // advance-to-total ratio instead).
        [1, 6, 14, 4, 8, 0.3, 'confirmed'],
        [0, 7, 20, 2, 2, 0.25, 'confirmed'],
        // Further-out schedule (next couple of months).
        [1, 8, 34, 3, 5, 0.5, 'confirmed'],
        [0, 0, 48, 5, 2, 0.4, 'confirmed'],
        [1, 1, 61, 2, 3, 1.0, 'confirmed'],
        // Cancelled, for status diversity - cancelled bookings are
        // excluded from the overlap check everywhere else in the app, so
        // these are allowed to sit inside another booking's date range.
        [0, 2, 12, 3, 2, 0.2, 'cancelled'],
        [1, 3, -10, 2, 7, 1.0, 'cancelled'],
    ];

    /**
     * Category name choice matters: ProfitController classifies "direct/
     * variable" costs by matching these keywords - housekeeping, laundry,
     * food, beverage, f&b, fnb, dining, kitchen, inventory, supplies,
     * cabana, amenities - so the first four categories below land in
     * Gross Profit's deduction and the rest only affect Net Profit,
     * giving the two figures a real, visible gap.
     */
    private const EXPENSE_CATEGORIES = [
        ['category' => 'Housekeeping & Laundry Setup', 'min' => 8000, 'max' => 15000, 'monthly' => false, 'description' => 'Linen, detergent & housekeeping supplies restock'],
        ['category' => 'Kitchen Inventory & Groceries', 'min' => 20000, 'max' => 45000, 'monthly' => false, 'description' => 'Weekly market run for kitchen & pantry stock'],
        ['category' => 'Food & Beverage Supplies', 'min' => 15000, 'max' => 30000, 'monthly' => false, 'description' => 'Beverage & dining ingredient restock'],
        ['category' => 'Guest Amenities & Toiletries', 'min' => 6000, 'max' => 12000, 'monthly' => false, 'description' => 'Guest welcome kits & bathroom amenities'],
        ['category' => 'CEB Electricity Bill', 'min' => 35000, 'max' => 60000, 'monthly' => true, 'description' => 'Ceylon Electricity Board - monthly bill'],
        ['category' => 'NWSDB Water Bill', 'min' => 8000, 'max' => 15000, 'monthly' => true, 'description' => 'National Water Supply & Drainage Board - monthly bill'],
        ['category' => 'Staff Wages & Salaries', 'min' => 150000, 'max' => 220000, 'monthly' => true, 'description' => 'Monthly staff wages & salaries'],
        ['category' => 'Marketing & Promotions', 'min' => 10000, 'max' => 25000, 'monthly' => true, 'description' => 'Social media promotion & local advertising'],
        ['category' => 'Maintenance & Repairs', 'min' => 10000, 'max' => 40000, 'monthly' => false, 'description' => 'General property maintenance & repairs'],
        ['category' => 'OTA Commission (Booking.com / Airbnb)', 'min' => 5000, 'max' => 20000, 'monthly' => false, 'description' => 'Commission on OTA-referred bookings'],
    ];

    public function run(): void
    {
        $this->retireLegacyRooms();

        $rooms = collect(self::ROOMS)->map(
            fn (array $room) => Room::firstOrCreate(['name_or_number' => $room['name_or_number']], $room)
        );

        $this->seedBookings($rooms);
        $this->seedExpenses();
    }

    /**
     * Deletes any of the old generic placeholder cabanas (see
     * LEGACY_ROOM_NAMES) still sitting in the database from before this
     * villa was rebranded to Star Moon Cabana - Kalupahana. rooms.id is
     * cascadeOnDelete() on bookings, so each room's booking history goes
     * with it - an accepted trade-off so only the two real cabanas exist
     * anywhere in the app, including the public booking page.
     */
    private function retireLegacyRooms(): void
    {
        Room::whereIn('name_or_number', self::LEGACY_ROOM_NAMES)->delete();
    }

    /**
     * @param  Collection<int, Room>  $rooms
     */
    private function seedBookings(Collection $rooms): void
    {
        Booking::whereIn('room_id', $rooms->pluck('id'))->delete();

        $today = Carbon::today();

        foreach (self::BOOKING_PLAN as $index => [$roomIdx, $guestIdx, $offset, $nights, $pax, $advanceRatio, $status]) {
            $room = $rooms[$roomIdx];
            $guest = self::GUESTS[$guestIdx];
            $checkIn = $today->copy()->addDays($offset);
            $checkOut = $checkIn->copy()->addDays($nights);
            $total = round($room->rateForGuests($pax) * $nights, 2);
            $advance = round($total * $advanceRatio, 2);

            // The booking itself can't have been made in the future: for an
            // upcoming stay it was made recently, for a past/current stay
            // it was made some lead time before arrival.
            $madeAt = $offset > 0
                ? $today->copy()->subDays(($index % 10) + 1)
                : $checkIn->copy()->subDays(5 + ($index % 4) * 5);
            $madeAt->setTime(9 + ($index % 8), 15);

            $attributes = [
                'room_id' => $room->id,
                'customer_name' => $guest['name'],
                'customer_email' => $guest['email'],
                'customer_phone' => $guest['phone'],
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'total_amount' => $total,
                'advance_payment' => $advance,
                'status' => $status,
                'guests_adults' => $pax,
                'guests_children' => 0,
            ];

            if ($status === 'checked_out') {
                $attributes['final_settlement_amount'] = round($total - $advance, 2);
                $attributes['checked_out_at'] = $checkOut->copy()->setTime(11, 0);
            }

            // created_at drives the "Advance Payment" income event's date
            // (see IncomeLedger::events()), so it's set directly rather
            // than left to Eloquent's auto "now" timestamp - otherwise
            // every seeded booking's advance would land on the same
            // instant instead of spreading across the demo's date range.
            $booking = new Booking($attributes);
            $booking->created_at = $madeAt;
            $booking->updated_at = $madeAt;
            $booking->save();
        }
    }

    private function seedExpenses(): void
    {
        $categories = collect(self::EXPENSE_CATEGORIES)->pluck('category');
        Expense::whereIn('category', $categories)->delete();

        $today = Carbon::today();
        $cursor = $today->copy()->subMonths(2)->startOfMonth();
        $week = 0;

        while ($cursor->lte($today)) {
            foreach (self::EXPENSE_CATEGORIES as $expenseCategory) {
                // Utilities/wages/marketing post roughly once a month
                // (the first week the drifting weekly cursor lands in);
                // day-to-day costs post every other week - this keeps the
                // expense trend from looking like an identical repeat.
                if ($expenseCategory['monthly'] && $cursor->day > 7) {
                    continue;
                }

                if (! $expenseCategory['monthly'] && $week % 2 !== 0) {
                    continue;
                }

                Expense::create([
                    'category' => $expenseCategory['category'],
                    'amount' => fake()->numberBetween($expenseCategory['min'], $expenseCategory['max']),
                    'description' => $expenseCategory['description'],
                    'expense_date' => $cursor->toDateString(),
                ]);
            }

            $cursor->addDays(7);
            $week++;
        }
    }
}
