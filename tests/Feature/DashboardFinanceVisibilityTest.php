<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingSafariTrip;
use App\Models\Expense;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Money on the dashboard (revenue, expenses, payments, balances, chart data) is
 * only for users who hold view_finance; everyone else gets operational cards.
 */
class DashboardFinanceVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create(['villa_name' => 'Star Moon Cabana', 'currency' => 'LKR', 'is_safari_enabled' => true]);
        $this->owner = User::factory()->create(['role' => 'owner']);

        $room = Room::create(['name_or_number' => 'Cabana A', 'type' => 'Deluxe', 'price_per_night' => 15000]);

        // Checking out today with a balance: total 50,000, advance 12,345 -> 37,655 outstanding.
        Booking::create([
            'room_id' => $room->id, 'customer_name' => 'Nimal Fernando',
            'check_in' => today()->subDays(2)->toDateString(), 'check_out' => today()->toDateString(),
            'total_amount' => 50000, 'advance_payment' => 12345, 'status' => 'confirmed',
        ]);

        $safari = Booking::create([
            'booking_type' => 'safari_only', 'customer_name' => 'Elena Rostova', 'safari_tour_name' => "Baker's Bend",
            'check_in' => today()->toDateString(), 'check_out' => today()->toDateString(), 'safari_date' => today()->toDateString(),
            'total_amount' => 20000, 'advance_payment' => 0, 'status' => 'confirmed',
        ]);
        BookingSafariTrip::create(['booking_id' => $safari->id, 'safari_date' => today()->toDateString(), 'tour_name' => "Baker's Bend", 'pickup_time' => '05:30', 'pax_count' => 3]);

        Expense::create(['category' => 'Utilities', 'amount' => 777.77, 'expense_date' => today()->toDateString()]);
    }

    private function manager(array $permissions = ['manage_bookings']): User
    {
        return User::factory()->create(['role' => 'manager', 'permissions' => $permissions]);
    }

    public function test_the_owner_sees_the_financial_metrics(): void
    {
        $this->actingAs($this->owner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Monthly Revenue')
            ->assertSee('Monthly Expenses')
            ->assertSee('Income &amp; Expenses Overview', false)
            ->assertSee('Recent Payments')
            ->assertSee('37,655.00')
            ->assertSee('777.77');
    }

    public function test_a_manager_without_finance_access_sees_no_money_at_all(): void
    {
        $html = $this->actingAs($this->manager())->get(route('dashboard'))->assertOk()->getContent();

        foreach (['Monthly Revenue', 'Monthly Expenses', 'Income &amp; Expenses Overview', 'Recent Payments', '37,655.00', '12,345.00', '777.77', 'dashboardChart('] as $hidden) {
            $this->assertStringNotContainsString($hidden, $html, "Dashboard leaked: {$hidden}");
        }

        // Still tells them a balance is owed - just not how much.
        $this->assertStringContainsString('Balance due', $html);
    }

    public function test_a_manager_gets_operational_cards_and_todays_safari_trips(): void
    {
        $this->actingAs($this->manager())->get(route('dashboard'))
            ->assertOk()
            ->assertSee("Today's Operations")
            ->assertSee('Check-ins Today')
            ->assertSee('Occupied Rooms')
            ->assertSee('Active safari trips today')
            ->assertSee("Baker's Bend")
            ->assertSee('Elena Rostova')
            ->assertSee('05:30')
            ->assertSee('Room Status Analysis')
            ->assertSee('Upcoming Check-ins');
    }

    public function test_a_manager_granted_view_finance_sees_the_metrics(): void
    {
        $this->actingAs($this->manager(['manage_bookings', 'view_finance']))->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Monthly Revenue')
            ->assertSee('Recent Payments');
    }
}
