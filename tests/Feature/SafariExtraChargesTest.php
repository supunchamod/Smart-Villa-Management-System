<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Safari-only add-ons (lunch / drone videos) carry a price that is saved as
 * itemised extra charges, so they show on every bill and count in the balance.
 */
class SafariExtraChargesTest extends TestCase
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
            'booking_type' => 'safari_only',
            'safari_tour_name' => 'Yala Full-Day Safari',
            'safari_date' => today()->addDays(5)->toDateString(),
            'customer_name' => 'Supun Chamod',
            'total_amount' => 45000,
            'advance_payment' => 15000,
            'status' => 'confirmed',
            'group_pax_size' => 4,
            'include_lunch' => '1',
            'lunch_charge' => 4500,
            'include_drone_videos' => '1',
            'drone_charge' => 12000,
        ], $overrides);
    }

    private function createSafari(array $overrides = []): Booking
    {
        $this->actingAs($this->owner)->post(route('bookings.store'), $this->payload($overrides))->assertSessionHasNoErrors();

        return Booking::latest('id')->firstOrFail();
    }

    public function test_lunch_and_drone_charges_are_saved_as_itemised_extra_charges(): void
    {
        $booking = $this->createSafari();

        $this->assertDatabaseHas('booking_extras', ['booking_id' => $booking->id, 'item_name' => 'Lunch Service', 'quantity' => 1, 'unit_price' => 4500]);
        $this->assertDatabaseHas('booking_extras', ['booking_id' => $booking->id, 'item_name' => 'Drone Video Shooting', 'quantity' => 1, 'unit_price' => 12000]);

        $booking = $booking->fresh();
        $this->assertSame(61500.0, $booking->grand_total);
        $this->assertSame(46500.0, $booking->remaining_balance); // 61,500 - 15,000 advance
    }

    public function test_a_ticked_add_on_requires_its_charge(): void
    {
        $this->actingAs($this->owner)
            ->post(route('bookings.store'), $this->payload(['lunch_charge' => '']))
            ->assertSessionHasErrors('lunch_charge');
    }

    public function test_unticked_add_ons_create_no_extra_charges(): void
    {
        $booking = $this->createSafari(['include_lunch' => null, 'lunch_charge' => null, 'include_drone_videos' => null, 'drone_charge' => null]);

        $this->assertSame(0, $booking->extras()->count());
        $this->assertSame(30000.0, $booking->fresh()->remaining_balance);
    }

    public function test_the_advance_may_cover_the_extras_but_not_more_than_the_whole_bill(): void
    {
        $this->actingAs($this->owner)
            ->post(route('bookings.store'), $this->payload(['advance_payment' => 61500]))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->owner)
            ->post(route('bookings.store'), $this->payload(['advance_payment' => 61501]))
            ->assertSessionHasErrors('advance_payment');
    }

    public function test_editing_updates_and_removes_the_extra_charges(): void
    {
        $booking = $this->createSafari();

        $this->actingAs($this->owner)
            ->put(route('bookings.update', $booking), $this->payload(['lunch_charge' => 5000, 'include_drone_videos' => null, 'drone_charge' => null]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('booking_extras', ['booking_id' => $booking->id, 'item_name' => 'Lunch Service', 'unit_price' => 5000]);
        $this->assertDatabaseMissing('booking_extras', ['booking_id' => $booking->id, 'item_name' => 'Drone Video Shooting']);
        $this->assertSame(35000.0, $booking->fresh()->remaining_balance); // 45,000 + 5,000 - 15,000
    }

    public function test_checkout_settles_the_balance_including_the_safari_extras(): void
    {
        $booking = $this->createSafari();

        $this->actingAs($this->owner)
            ->post(route('bookings.checkout', $booking), ['payment_method' => 'cash'])
            ->assertSessionHasNoErrors();

        $booking = $booking->fresh();
        $this->assertSame('checked_out', $booking->status);
        $this->assertSame(46500.0, (float) $booking->final_settlement_amount);
    }

    public function test_the_balance_due_tab_counts_the_extras(): void
    {
        // Advance covers the 45,000 total but not the 16,500 of extras.
        $this->createSafari(['advance_payment' => 45000]);

        $this->actingAs($this->owner)
            ->get(route('bookings.index', ['tab' => 'balance_due']))
            ->assertOk()
            ->assertSee('Supun Chamod');
    }

    public function test_the_bill_lists_the_extra_charges(): void
    {
        $booking = $this->createSafari();

        $this->actingAs($this->owner)
            ->get(route('bookings.invoice.view', [$booking, 'confirmation']))
            ->assertOk()
            ->assertSee('Lunch Service')
            ->assertSee('Drone Video Shooting')
            ->assertSee('61,500.00');
    }
}
