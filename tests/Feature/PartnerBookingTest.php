<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Expense;
use App\Models\PartnerBooking;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Partner Resort Bookings: every Full Package starts as Pending Advance; the
 * advance and the final settlement are mirrored as direct expenses on the
 * guest booking, so its net profit is right.
 */
class PartnerBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create(['villa_name' => 'Star Moon Cabana', 'currency' => 'LKR', 'is_safari_enabled' => true]);
        $this->owner = User::factory()->create(['role' => 'owner']);
    }

    private function createPackage(array $overrides = []): Booking
    {
        $this->actingAs($this->owner)->post(route('bookings.store'), array_merge([
            'booking_type' => 'full_package',
            'stay_hotel_name' => 'Cinnamon Wild, Yala',
            'check_in' => today()->addDays(3)->toDateString(),
            'check_out' => today()->addDays(5)->toDateString(),
            'safari_tour_name' => 'Yala Full Day Safari',
            'safari_date' => today()->addDays(4)->toDateString(),
            'group_pax_size' => 4,
            'customer_name' => 'Supun Chamod',
            'total_amount' => 150000,
            'advance_payment' => 50000,
            'status' => 'confirmed',
        ], $overrides))->assertSessionHasNoErrors();

        return Booking::latest('id')->firstOrFail();
    }

    private function recordAdvance(PartnerBooking $entry, array $overrides = [])
    {
        return $this->actingAs($this->owner)->post(route('partner-bookings.advance', $entry), array_merge([
            'agreed_cost' => 90000,
            'advance_amount' => 30000,
        ], $overrides));
    }

    public function test_a_full_package_registers_as_pending_advance_automatically(): void
    {
        $booking = $this->createPackage();

        $entry = $booking->partnerBooking;
        $this->assertNotNull($entry);
        $this->assertSame(PartnerBooking::PENDING_ADVANCE, $entry->status);
        $this->assertSame('pending_advance', $entry->stage);

        $this->actingAs($this->owner)->get(route('partner-bookings.index'))
            ->assertOk()
            ->assertSee('Partner Resort Bookings')
            ->assertSee('Pending Advance')
            ->assertSee('Supun Chamod');
    }

    public function test_other_booking_types_do_not_register(): void
    {
        $room = Room::create(['name_or_number' => 'Cabana A', 'type' => 'Deluxe', 'price_per_night' => 15000]);

        $this->actingAs($this->owner)->post(route('bookings.store'), [
            'booking_type' => 'accommodation_only', 'room_id' => $room->id,
            'check_in' => today()->addDays(3)->toDateString(), 'check_out' => today()->addDays(5)->toDateString(),
            'customer_name' => 'Guest', 'total_amount' => 30000, 'advance_payment' => 0, 'status' => 'confirmed',
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, PartnerBooking::count());
    }

    public function test_recording_the_advance_confirms_it_and_books_a_direct_expense(): void
    {
        $booking = $this->createPackage();
        $entry = $booking->partnerBooking;

        $this->recordAdvance($entry)->assertSessionHasNoErrors();

        $entry = $entry->fresh();
        $this->assertSame(PartnerBooking::ADVANCE_PAID, $entry->status);
        // The partner is the booking's stay hotel - there is no separate name input.
        $this->assertSame('Cinnamon Wild, Yala', $entry->partner_label);
        $this->assertSame('Cinnamon Wild, Yala', $entry->partner_name);
        $this->assertSame('advance_paid', $entry->stage);
        $this->assertSame(60000.0, $entry->remaining_cost);

        $this->assertDatabaseHas('expenses', [
            'booking_id' => $booking->id,
            'partner_booking_id' => $entry->id,
            'partner_stage' => 'advance',
            'category' => PartnerBooking::ADVANCE_CATEGORY,
            'amount' => 30000,
        ]);
        $this->assertSame(30000.0, $booking->fresh()->expenses_total);
    }

    public function test_correcting_the_advance_updates_the_same_expense_instead_of_adding_another(): void
    {
        $entry = $this->createPackage()->partnerBooking;

        $this->recordAdvance($entry);
        $this->recordAdvance($entry, ['advance_amount' => 40000, 'agreed_cost' => 100000])->assertSessionHasNoErrors();

        $this->assertSame(1, Expense::where('partner_booking_id', $entry->id)->count());
        $this->assertDatabaseHas('expenses', ['partner_booking_id' => $entry->id, 'amount' => 40000]);
    }

    public function test_the_advance_cannot_exceed_the_agreed_cost(): void
    {
        $entry = $this->createPackage()->partnerBooking;

        $this->recordAdvance($entry, ['advance_amount' => 95000])->assertSessionHasErrors('advance_amount');
        $this->assertSame(0, Expense::count());
        $this->assertSame(PartnerBooking::PENDING_ADVANCE, $entry->fresh()->status);
    }

    public function test_checkout_flags_pending_final_settlement_and_settling_books_the_remainder(): void
    {
        $booking = $this->createPackage();
        $entry = $booking->partnerBooking;
        $this->recordAdvance($entry);

        // Not allowed until the guest has checked out.
        $this->actingAs($this->owner)->post(route('partner-bookings.settle', $entry), ['final_amount' => 60000])
            ->assertSessionHas('error');
        $this->assertSame('advance_paid', $entry->fresh()->stage);

        $this->actingAs($this->owner)->post(route('bookings.checkout', $booking), ['payment_method' => 'cash'])->assertSessionHasNoErrors();

        $entry = $entry->fresh();
        $this->assertSame('pending_final', $entry->stage);
        $this->actingAs($this->owner)->get(route('partner-bookings.index'))->assertSee('Pending Final Settlement')->assertSee('Settle Remaining');

        $this->actingAs($this->owner)->post(route('partner-bookings.settle', $entry), ['final_amount' => 60000])->assertSessionHasNoErrors();

        $entry = $entry->fresh();
        $this->assertSame(PartnerBooking::SETTLED, $entry->status);
        $this->assertSame('settled', $entry->stage);
        $this->assertDatabaseHas('expenses', ['partner_booking_id' => $entry->id, 'partner_stage' => 'final', 'category' => PartnerBooking::FINAL_CATEGORY, 'amount' => 60000]);

        // Net profit on the guest booking: 150,000 billed - (30,000 advance + 60,000 final) partner costs.
        $booking = $booking->fresh();
        $this->assertSame(90000.0, $booking->expenses_total);
        $this->assertSame(60000.0, $booking->booking_profit);

        // Fully settled entries cannot be changed or settled twice.
        $this->recordAdvance($entry)->assertSessionHas('error');
        $this->actingAs($this->owner)->post(route('partner-bookings.settle', $entry), ['final_amount' => 1])->assertSessionHas('error');
        $this->assertSame(2, Expense::where('booking_id', $booking->id)->count());
    }

    public function test_partner_payment_expenses_are_protected_from_edits_and_deletes_elsewhere(): void
    {
        $booking = $this->createPackage();
        $entry = $booking->partnerBooking;
        $this->recordAdvance($entry);
        $expense = Expense::where('partner_booking_id', $entry->id)->firstOrFail();

        $this->actingAs($this->owner)->deleteJson(route('bookings.expenses.destroy', $expense))->assertStatus(422);
        $this->actingAs($this->owner)->delete(route('expenses.destroy', $expense))->assertSessionHas('error');
        $this->actingAs($this->owner)->put(route('expenses.update', $expense), [
            'category' => 'Hacked', 'amount' => 1, 'expense_date' => today()->toDateString(),
        ])->assertSessionHas('error');

        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'amount' => 30000]);
    }

    public function test_meals_and_other_direct_costs_can_still_be_added_to_a_full_package(): void
    {
        $booking = $this->createPackage();

        $this->actingAs($this->owner)->post(route('bookings.expenses.store', $booking), [
            'category' => 'External Meals', 'amount' => 6500, 'description' => 'Dinner',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('expenses', ['booking_id' => $booking->id, 'category' => 'External Meals', 'partner_booking_id' => null]);
    }

    public function test_deleting_the_booking_removes_its_partner_entry(): void
    {
        $booking = $this->createPackage();

        $this->actingAs($this->owner)->delete(route('bookings.destroy', $booking))->assertRedirect();

        $this->assertSame(0, PartnerBooking::count());
    }

    public function test_the_partner_name_comes_from_the_bookings_stay_hotel_and_follows_edits(): void
    {
        $booking = $this->createPackage(['stay_hotel_name' => 'Cinnamon Wild, Yala']);
        $this->assertSame('Cinnamon Wild, Yala', $booking->partnerBooking->partner_label);

        // A partner_name submitted with the form is ignored.
        $this->recordAdvance($booking->partnerBooking, ['partner_name' => 'Someone Else'])->assertSessionHasNoErrors();
        $this->assertSame('Cinnamon Wild, Yala', $booking->partnerBooking->fresh()->partner_name);
        $this->assertDatabaseHas('expenses', ['booking_id' => $booking->id, 'description' => 'Advance to Cinnamon Wild, Yala']);

        $this->actingAs($this->owner)->put(route('bookings.update', $booking), [
            'booking_type' => 'full_package', 'stay_hotel_name' => 'Wild Coast Tented Lodge',
            'check_in' => today()->addDays(3)->toDateString(), 'check_out' => today()->addDays(5)->toDateString(),
            'safari_tour_name' => 'Yala Full Day Safari', 'safari_date' => today()->addDays(4)->toDateString(),
            'group_pax_size' => 4, 'customer_name' => 'Supun Chamod', 'total_amount' => 150000, 'advance_payment' => 50000, 'status' => 'confirmed',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Wild Coast Tented Lodge', $booking->partnerBooking->fresh()->partner_label);
    }

    public function test_the_initial_advance_can_be_saved_without_the_total_agreed_cost(): void
    {
        $booking = $this->createPackage();
        $entry = $booking->partnerBooking;

        $this->actingAs($this->owner)
            ->post(route('partner-bookings.advance', $entry), ['advance_amount' => 25000])
            ->assertSessionHasNoErrors();

        $entry = $entry->fresh();
        $this->assertSame(PartnerBooking::ADVANCE_PAID, $entry->status);
        $this->assertNull($entry->agreed_cost);
        $this->assertNull($entry->remaining_cost);
        $this->assertSame('advance_partial', $entry->stage);
        $this->assertSame('Pending Advance / Partial', $entry->stage_meta['label']);

        $this->assertDatabaseHas('expenses', ['booking_id' => $booking->id, 'partner_booking_id' => $entry->id, 'partner_stage' => 'advance', 'amount' => 25000]);

        $this->actingAs($this->owner)->get(route('partner-bookings.index'))->assertOk()->assertSee('Pending Advance / Partial');

        // Adding the agreed cost later confirms it.
        $this->recordAdvance($entry, ['advance_amount' => 25000, 'agreed_cost' => 90000])->assertSessionHasNoErrors();
        $this->assertSame('advance_paid', $entry->fresh()->stage);
        $this->assertSame(65000.0, $entry->fresh()->remaining_cost);
        $this->assertSame(1, Expense::where('partner_booking_id', $entry->id)->count());
    }

    public function test_an_advance_amount_is_still_required(): void
    {
        $entry = $this->createPackage()->partnerBooking;

        $this->actingAs($this->owner)->post(route('partner-bookings.advance', $entry), [])->assertSessionHasErrors('advance_amount');
        $this->assertSame(0, Expense::count());
    }

    public function test_settling_without_a_known_total_asks_for_it_and_then_books_the_remainder(): void
    {
        $booking = $this->createPackage();
        $entry = $booking->partnerBooking;
        $this->actingAs($this->owner)->post(route('partner-bookings.advance', $entry), ['advance_amount' => 25000])->assertSessionHasNoErrors();
        $this->actingAs($this->owner)->post(route('bookings.checkout', $booking), ['payment_method' => 'cash'])->assertSessionHasNoErrors();

        $this->assertSame('pending_final', $entry->fresh()->stage);

        // The total agreed cost was never entered, so settlement must supply it (and it must cover the advance).
        $this->actingAs($this->owner)->post(route('partner-bookings.settle', $entry), ['final_amount' => 65000])->assertSessionHasErrors('agreed_cost');
        $this->actingAs($this->owner)->post(route('partner-bookings.settle', $entry), ['agreed_cost' => 20000, 'final_amount' => 0])->assertSessionHasErrors('agreed_cost');

        $this->actingAs($this->owner)
            ->post(route('partner-bookings.settle', $entry), ['agreed_cost' => 90000, 'final_amount' => 65000])
            ->assertSessionHasNoErrors();

        $entry = $entry->fresh();
        $this->assertSame('settled', $entry->stage);
        $this->assertSame(90000.0, (float) $entry->agreed_cost);
        $this->assertSame(90000.0, $booking->fresh()->expenses_total); // 25,000 advance + 65,000 final
    }
}
