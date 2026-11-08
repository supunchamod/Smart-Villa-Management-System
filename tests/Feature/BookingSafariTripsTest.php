<?php

namespace Tests\Feature;

use App\Jobs\SendBookingWhatsAppJob;
use App\Models\Booking;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * A safari-only booking or full package can have several safari trips on
 * different days; each is stored, scheduled on the calendar, and shown on the
 * booking details and the invoice.
 */
class BookingSafariTripsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create(['villa_name' => 'Star Moon Cabana', 'currency' => 'LKR', 'is_safari_enabled' => true]);
        $this->owner = User::factory()->create(['role' => 'owner']);
    }

    private function trip(int $daysFromToday, string $tour, array $extra = []): array
    {
        return array_merge([
            'safari_date' => today()->addDays($daysFromToday)->toDateString(),
            'tour_name' => $tour,
            'pickup_time' => '05:30',
            'pax_count' => 4,
        ], $extra);
    }

    private function packagePayload(array $overrides = []): array
    {
        return array_merge([
            'booking_type' => 'full_package',
            'stay_hotel_name' => 'Cinnamon Wild, Yala',
            'check_in' => today()->addDays(2)->toDateString(),
            'check_out' => today()->addDays(5)->toDateString(),
            'customer_name' => 'Supun Chamod',
            'total_amount' => 150000,
            'advance_payment' => 50000,
            'status' => 'confirmed',
            // Submitted out of order on purpose.
            'trips' => [$this->trip(3, "Devil's Staircase", ['pickup_time' => '06:00', 'notes' => 'Early start']), $this->trip(2, "Baker's Bend")],
        ], $overrides);
    }

    private function createPackage(array $overrides = []): Booking
    {
        $this->actingAs($this->owner)->post(route('bookings.store'), $this->packagePayload($overrides))->assertSessionHasNoErrors();

        return Booking::latest('id')->firstOrFail();
    }

    public function test_a_full_package_saves_multiple_safari_trips_in_date_order(): void
    {
        $booking = $this->createPackage();

        $trips = $booking->safariTrips;
        $this->assertCount(2, $trips);
        $this->assertSame(["Baker's Bend", "Devil's Staircase"], $trips->pluck('tour_name')->all());
        $this->assertSame([today()->addDays(2)->toDateString(), today()->addDays(3)->toDateString()], $trips->map(fn ($t) => $t->safari_date->toDateString())->all());
        $this->assertSame('06:00', $trips[1]->pickup_time_short);
        $this->assertSame('Early start', $trips[1]->notes);
        $this->assertSame(4, $trips[0]->pax_count);

        // The booking-level fields summarise the trips (so lists and search keep working).
        $booking = $booking->fresh();
        $this->assertSame("Baker's Bend + Devil's Staircase", $booking->safari_tour_name);
        $this->assertSame(today()->addDays(2)->toDateString(), $booking->safari_date->toDateString());
    }

    public function test_safari_only_bookings_need_at_least_one_valid_trip(): void
    {
        $payload = ['booking_type' => 'safari_only', 'customer_name' => 'Guest', 'total_amount' => 30000, 'advance_payment' => 0, 'status' => 'confirmed'];

        $this->actingAs($this->owner)->post(route('bookings.store'), $payload)->assertSessionHasErrors('trips');
        $this->actingAs($this->owner)->post(route('bookings.store'), $payload + ['trips' => [['safari_date' => '', 'tour_name' => '', 'pax_count' => 0]]])
            ->assertSessionHasErrors(['trips.0.safari_date', 'trips.0.tour_name', 'trips.0.pax_count']);

        $this->actingAs($this->owner)->post(route('bookings.store'), $payload + ['trips' => [$this->trip(4, 'Yala Safari'), $this->trip(5, 'Wilpattu Safari')]])
            ->assertSessionHasNoErrors();

        $booking = Booking::firstOrFail();
        $this->assertCount(2, $booking->safariTrips);
        $this->assertSame(today()->addDays(4)->toDateString(), $booking->check_in->toDateString()); // first trip stands in for the dates
    }

    public function test_a_full_package_trip_must_fall_within_the_stay(): void
    {
        $this->actingAs($this->owner)
            ->post(route('bookings.store'), $this->packagePayload(['trips' => [$this->trip(9, 'Too Late Safari')]]))
            ->assertSessionHasErrors('trips.0.safari_date');

        // The check-in and check-out days themselves are allowed.
        $this->actingAs($this->owner)
            ->post(route('bookings.store'), $this->packagePayload(['trips' => [$this->trip(2, 'Arrival Day'), $this->trip(5, 'Departure Day')]]))
            ->assertSessionHasNoErrors();
    }

    public function test_editing_replaces_the_trips(): void
    {
        $booking = $this->createPackage();

        $this->actingAs($this->owner)
            ->put(route('bookings.update', $booking), $this->packagePayload(['trips' => [$this->trip(4, 'Wilpattu Safari')]]))
            ->assertSessionHasNoErrors();

        $this->assertSame(['Wilpattu Safari'], $booking->fresh()->safariTrips->pluck('tour_name')->all());
        $this->assertSame(1, \App\Models\BookingSafariTrip::count());
    }

    public function test_accommodation_bookings_store_no_trips(): void
    {
        $room = \App\Models\Room::create(['name_or_number' => 'Cabana A', 'type' => 'Deluxe', 'price_per_night' => 15000]);

        $this->actingAs($this->owner)->post(route('bookings.store'), [
            'booking_type' => 'accommodation_only', 'room_id' => $room->id,
            'check_in' => today()->addDays(2)->toDateString(), 'check_out' => today()->addDays(4)->toDateString(),
            'customer_name' => 'Guest', 'total_amount' => 30000, 'advance_payment' => 0, 'status' => 'confirmed',
            'trips' => [$this->trip(3, 'Ignored')],
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, \App\Models\BookingSafariTrip::count());
    }

    public function test_every_trip_date_is_plotted_on_the_calendar(): void
    {
        $this->createPackage();

        $events = collect($this->actingAs($this->owner)->getJson(route('bookings.calendar'))->assertOk()->json());

        // The stay plus one event per safari trip, on their own dates.
        $this->assertCount(3, $events);

        $bakers = $events->firstWhere('title', "Safari: Baker's Bend — Supun Chamod");
        $devils = $events->firstWhere('title', "Safari: Devil's Staircase — Supun Chamod");

        $this->assertNotNull($bakers);
        $this->assertNotNull($devils);
        $this->assertSame(today()->addDays(2)->toDateString(), $bakers['start']);
        $this->assertSame(today()->addDays(3)->toDateString(), $devils['start']);
        $this->assertSame("Devil's Staircase", $devils['extendedProps']['selected_trip']['tour']);
        $this->assertCount(2, $devils['extendedProps']['trips']);
        $this->assertNotSame($bakers['color'], $events->firstWhere('title', 'Cinnamon Wild, Yala — Supun Chamod')['color']);
    }

    public function test_a_safari_only_booking_appears_on_the_calendar_only_through_its_trips(): void
    {
        $this->actingAs($this->owner)->post(route('bookings.store'), [
            'booking_type' => 'safari_only', 'customer_name' => 'Guest', 'total_amount' => 30000, 'advance_payment' => 0, 'status' => 'confirmed',
            'trips' => [$this->trip(4, 'Yala Safari'), $this->trip(6, 'Wilpattu Safari')],
        ])->assertSessionHasNoErrors();

        $events = collect($this->actingAs($this->owner)->getJson(route('bookings.calendar'))->json());

        $this->assertSame(['Safari: Yala Safari — Guest', 'Safari: Wilpattu Safari — Guest'], $events->pluck('title')->all());
        $this->assertSame([today()->addDays(4)->toDateString(), today()->addDays(6)->toDateString()], $events->pluck('start')->all());
    }

    public function test_the_details_page_and_invoice_list_every_trip(): void
    {
        $booking = $this->createPackage();

        $this->actingAs($this->owner)->get(route('bookings.show', $booking))
            ->assertOk()->assertSee("Baker's Bend", false)->assertSee("Devil's Staircase", false)->assertSee('Early start')->assertSee('06:00');

        $html = $this->actingAs($this->owner)->get(route('bookings.invoice.view', [$booking, 'confirmation']))->assertOk()->assertSee('Safari Schedule')->getContent();
        $this->assertStringContainsString(today()->addDays(2)->format('D, d M Y'), $html);
        $this->assertStringContainsString(today()->addDays(3)->format('D, d M Y'), $html);
        $this->assertStringContainsString('Baker', $html);
        $this->assertStringContainsString('Staircase', $html);

        $pdfHtml = view('invoices.pdf', ['booking' => $booking->load(['room', 'extras', 'safariTrips']), 'stage' => 'final'])->render();
        $this->assertStringContainsString('Safari Schedule', $pdfHtml);
        $this->assertStringContainsString('Staircase', $pdfHtml);
    }

    public function test_the_whatsapp_caption_lists_each_trip(): void
    {
        $booking = $this->createPackage()->load(['room', 'extras', 'safariTrips']);

        $job = new SendBookingWhatsAppJob($booking);
        $method = new ReflectionMethod($job, 'caption');
        $method->setAccessible(true);
        $caption = $method->invoke($job, $booking, 'confirmation');

        $this->assertStringContainsString("Baker's Bend · pickup 05:30 · 4 pax", $caption);
        $this->assertStringContainsString("Devil's Staircase · pickup 06:00 · 4 pax", $caption);
    }

    public function test_an_invoice_without_trips_has_no_safari_schedule(): void
    {
        $room = \App\Models\Room::create(['name_or_number' => 'Cabana A', 'type' => 'Deluxe', 'price_per_night' => 15000]);
        $this->actingAs($this->owner)->post(route('bookings.store'), [
            'booking_type' => 'accommodation_only', 'room_id' => $room->id,
            'check_in' => today()->addDays(2)->toDateString(), 'check_out' => today()->addDays(4)->toDateString(),
            'customer_name' => 'Guest', 'total_amount' => 30000, 'advance_payment' => 0, 'status' => 'confirmed',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->owner)->get(route('bookings.invoice.view', [Booking::firstOrFail(), 'confirmation']))->assertDontSee('Safari Schedule');
    }
}
