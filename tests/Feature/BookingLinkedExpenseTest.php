<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Expense;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use App\Support\IncomeLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Expenses can be general (no booking) or linked to a booking. A linked
 * expense must be saved with its booking_id, show up in the Expenses list
 * with its booking reference, and reduce that booking's net profit.
 */
class BookingLinkedExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create(['villa_name' => 'Star Moon Cabana', 'currency' => 'LKR']);
        $this->owner = User::factory()->create(['role' => 'owner']);

        $room = Room::create(['name_or_number' => 'Cabana A', 'type' => 'Deluxe', 'price_per_night' => 15000]);

        $this->booking = Booking::create([
            'room_id' => $room->id,
            'customer_name' => 'Supun Chamod',
            'check_in' => today()->subDays(3)->toDateString(),
            'check_out' => today()->toDateString(),
            'total_amount' => 45000,
            'advance_payment' => 15000,
            'status' => 'confirmed',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'category' => 'Transport',
            'amount' => 1500,
            'expense_date' => today()->toDateString(),
            'description' => 'Tuk-tuk to the station',
        ], $overrides);
    }

    public function test_booking_linked_expense_is_saved_with_its_booking(): void
    {
        $this->actingAs($this->owner)
            ->post(route('expenses.store'), $this->payload(['expense_type' => 'booking', 'booking_id' => $this->booking->id]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('expenses', ['category' => 'Transport', 'booking_id' => $this->booking->id]);
    }

    public function test_saved_booking_expense_appears_in_the_list_with_its_booking_reference(): void
    {
        Expense::create($this->payload(['booking_id' => $this->booking->id, 'category' => 'External Meals']));
        Expense::create($this->payload(['category' => 'Utilities']));

        $this->actingAs($this->owner)
            ->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('External Meals')
            ->assertSee('Utilities')
            ->assertSee('#'.$this->booking->id.' · Supun Chamod')
            ->assertSee('General');

        $this->actingAs($this->owner)
            ->get(route('expenses.index', ['type' => 'booking']))
            ->assertSee('External Meals')
            ->assertDontSee('Utilities');
    }

    public function test_a_booking_type_expense_without_a_booking_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->post(route('expenses.store'), $this->payload(['expense_type' => 'booking']))
            ->assertSessionHasErrors('booking_id');

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_a_general_expense_is_never_linked_to_a_booking(): void
    {
        $this->actingAs($this->owner)
            ->post(route('expenses.store'), $this->payload(['expense_type' => 'general', 'booking_id' => $this->booking->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('expenses', ['category' => 'Transport', 'booking_id' => null]);
    }

    public function test_an_unknown_booking_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->post(route('expenses.store'), $this->payload(['expense_type' => 'booking', 'booking_id' => 9999]))
            ->assertSessionHasErrors('booking_id');
    }

    public function test_the_expense_form_opts_out_of_the_demo_submit_shim(): void
    {
        // main.js cancels the submit of any form without a real action at load time; the
        // Expenses form sets its action through Alpine, so it must carry data-live-form.
        $this->actingAs($this->owner)
            ->get(route('expenses.index'))
            ->assertSee('data-live-form', false);
    }

    public function test_booking_net_profit_subtracts_only_its_linked_expenses(): void
    {
        Expense::create($this->payload(['booking_id' => $this->booking->id, 'amount' => 1500]));
        Expense::create($this->payload(['booking_id' => $this->booking->id, 'amount' => 6500, 'category' => 'External Meals']));
        Expense::create($this->payload(['amount' => 99999, 'category' => 'Utilities'])); // general: must not affect the booking

        $booking = $this->booking->fresh();

        $this->assertSame(8000.0, $booking->expenses_total);
        $this->assertSame(37000.0, $booking->booking_profit); // 45,000 room charges - 8,000 direct costs
    }

    public function test_profitability_report_and_profit_analyzer_use_the_linked_expenses(): void
    {
        Expense::create($this->payload(['booking_id' => $this->booking->id, 'amount' => 8000]));

        $rows = IncomeLedger::bookingProfitability(now()->startOfMonth(), now()->endOfMonth());
        $row = $rows->firstWhere(fn (array $row) => $row['booking']->id === $this->booking->id);

        $this->assertNotNull($row);
        $this->assertSame(45000.0, $row['revenue']);
        $this->assertSame(8000.0, $row['costs']);
        $this->assertSame(37000.0, $row['net_profit']);

        $this->actingAs($this->owner)
            ->get(route('profit.index'))
            ->assertOk()
            ->assertSee('Net Profit per Booking')
            ->assertSee('Supun Chamod');
    }

    public function test_booking_details_page_shows_the_linked_expense_and_net_profit(): void
    {
        Expense::create($this->payload(['booking_id' => $this->booking->id, 'amount' => 8000, 'description' => 'Catered dinner']));

        $this->actingAs($this->owner)
            ->get(route('bookings.show', $this->booking))
            ->assertOk()
            ->assertSee('Catered dinner')
            ->assertSee('37,000.00');
    }
}
