<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A Full Package records the hotel the guests stay at (no room, no board
 * basis) plus pickup time and group size, with simple total/advance billing.
 */
class FullPackageBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create(['villa_name' => 'Star Moon Cabana', 'currency' => 'LKR', 'is_safari_enabled' => true]);
        $this->owner = User::factory()->create(['role' => 'owner']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'booking_type' => 'full_package',
            'stay_hotel_name' => 'Cinnamon Wild, Yala',
            'check_in' => today()->addDays(3)->toDateString(),
            'check_out' => today()->addDays(5)->toDateString(),
            'safari_tour_name' => 'Yala Full Day Safari',
            'safari_date' => today()->addDays(4)->toDateString(),
            'pickup_time' => '05:30',
            'group_pax_size' => 4,
            'customer_name' => 'Supun Chamod',
            'total_amount' => 90000,
            'advance_payment' => 30000,
            'status' => 'confirmed',
        ], $overrides);
    }

    public function test_a_full_package_saves_the_hotel_without_a_room_or_board_basis(): void
    {
        $room = Room::create(['name_or_number' => 'Cabana A', 'type' => 'Deluxe', 'price_per_night' => 15000]);

        $this->actingAs($this->owner)
            ->post(route('bookings.store'), $this->payload(['room_id' => $room->id, 'board_type' => 'full_board']))
            ->assertSessionHasNoErrors();

        $booking = Booking::firstOrFail();
        $this->assertSame('Cinnamon Wild, Yala', $booking->stay_hotel_name);
        $this->assertNull($booking->room_id);
        $this->assertNull($booking->board_type);
        $this->assertSame('05:30', $booking->pickup_time_short);
        $this->assertSame(4, $booking->group_pax_size);
        $this->assertSame('Cinnamon Wild, Yala', $booking->room_label);
        $this->assertSame(60000.0, $booking->remaining_balance);
    }

    public function test_the_hotel_name_and_group_size_are_required(): void
    {
        $this->actingAs($this->owner)
            ->post(route('bookings.store'), $this->payload(['stay_hotel_name' => '', 'group_pax_size' => '']))
            ->assertSessionHasErrors(['stay_hotel_name', 'group_pax_size']);
    }

    public function test_a_full_package_still_needs_its_stay_dates(): void
    {
        $this->actingAs($this->owner)
            ->post(route('bookings.store'), $this->payload(['check_in' => '', 'check_out' => '']))
            ->assertSessionHasErrors(['check_in', 'check_out']);
    }

    public function test_accommodation_bookings_ignore_the_package_fields(): void
    {
        $room = Room::create(['name_or_number' => 'Cabana A', 'type' => 'Deluxe', 'price_per_night' => 15000]);

        $this->actingAs($this->owner)->post(route('bookings.store'), [
            'booking_type' => 'accommodation_only',
            'room_id' => $room->id,
            'stay_hotel_name' => 'Some Hotel',
            'pickup_time' => '05:30',
            'group_pax_size' => 4,
            'check_in' => today()->addDays(3)->toDateString(),
            'check_out' => today()->addDays(5)->toDateString(),
            'customer_name' => 'Guest',
            'total_amount' => 30000,
            'advance_payment' => 0,
            'status' => 'confirmed',
            'board_type' => 'half_board',
        ])->assertSessionHasNoErrors();

        $booking = Booking::firstOrFail();
        $this->assertSame($room->id, $booking->room_id);
        $this->assertSame('half_board', $booking->board_type);
        $this->assertNull($booking->stay_hotel_name);
        $this->assertNull($booking->pickup_time);
        $this->assertNull($booking->group_pax_size);
    }

    public function test_the_details_page_shows_the_hotel(): void
    {
        $this->actingAs($this->owner)->post(route('bookings.store'), $this->payload())->assertSessionHasNoErrors();

        $this->actingAs($this->owner)
            ->get(route('bookings.show', Booking::firstOrFail()))
            ->assertOk()
            ->assertSee('Cinnamon Wild, Yala')
            ->assertSee('05:30');
    }
}
