<?php

namespace Tests\Feature;

use App\Jobs\SendBookingWhatsAppJob;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Inclusions are printed on guest invoices; the special note is an internal
 * staff reminder and must never reach anything the guest sees.
 */
class BookingInclusionsAndInternalNotesTest extends TestCase
{
    use RefreshDatabase;

    private const NOTE = 'INTERNAL-ONLY: VIP guest, collect the balance in cash';

    private User $owner;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create(['villa_name' => 'Star Moon Cabana', 'currency' => 'LKR', 'is_safari_enabled' => true]);
        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->room = Room::create(['name_or_number' => 'Cabana A', 'type' => 'Deluxe', 'price_per_night' => 15000]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'booking_type' => 'accommodation_only',
            'room_id' => $this->room->id,
            'check_in' => today()->addDays(3)->toDateString(),
            'check_out' => today()->addDays(5)->toDateString(),
            'customer_name' => 'Supun Chamod',
            'customer_phone' => '+94 77 123 4567',
            'total_amount' => 30000,
            'advance_payment' => 10000,
            'status' => 'confirmed',
            'special_notes' => self::NOTE,
            'inclusions' => ['Breakfast', 'Dinner', '  Airport   transfer ', 'breakfast', ''],
        ], $overrides);
    }

    private function createBooking(array $overrides = []): Booking
    {
        $this->actingAs($this->owner)->post(route('bookings.store'), $this->payload($overrides))->assertSessionHasNoErrors();

        return Booking::latest('id')->firstOrFail();
    }

    public function test_inclusions_are_saved_trimmed_and_deduplicated(): void
    {
        $booking = $this->createBooking();

        $this->assertSame(['Breakfast', 'Dinner', 'Airport transfer'], $booking->inclusions);
        $this->assertSame(['Breakfast', 'Dinner', 'Airport transfer'], $booking->all_inclusions);
    }

    public function test_a_booking_without_inclusions_stores_none(): void
    {
        $booking = $this->createBooking(['inclusions' => []]);

        $this->assertNull($booking->inclusions);
        $this->assertSame([], $booking->all_inclusions);
    }

    public function test_inclusions_work_for_a_safari_only_booking_and_merge_with_its_priced_add_ons(): void
    {
        $booking = $this->createBooking([
            'booking_type' => 'safari_only',
            'room_id' => null,
            'safari_tour_name' => 'Yala Safari',
            'safari_date' => today()->addDays(4)->toDateString(),
            'group_pax_size' => 3,
            'include_lunch' => '1',
            'lunch_charge' => 4500,
            'inclusions' => ['Safari Tour', 'Lunch', 'Drone Videos'],
        ]);

        // Lunch comes from the priced toggle, so it isn't stored twice; Drone Videos was not toggled on.
        $this->assertSame(['Safari Tour'], $booking->inclusions);
        $this->assertSame(['Safari Tour', 'Lunch'], $booking->all_inclusions);
    }

    public function test_the_guest_invoice_lists_inclusions_but_never_the_internal_note(): void
    {
        $booking = $this->createBooking();

        $html = $this->actingAs($this->owner)
            ->get(route('bookings.invoice.view', [$booking, 'confirmation']))
            ->assertOk()
            ->assertSee('Includes / Services Provided')
            ->assertSee('Breakfast')
            ->assertSee('Airport transfer')
            ->getContent();

        $this->assertStringNotContainsString(self::NOTE, $html);
        $this->assertStringNotContainsString('INTERNAL-ONLY', $html);

        // The exact template the PDF is rendered from.
        $pdfHtml = view('invoices.pdf', ['booking' => $booking->load(['room', 'extras']), 'stage' => 'final'])->render();
        $this->assertStringContainsString('Includes / Services Provided', $pdfHtml);
        $this->assertStringNotContainsString('INTERNAL-ONLY', $pdfHtml);
    }

    public function test_the_whatsapp_caption_lists_inclusions_but_never_the_internal_note(): void
    {
        $booking = $this->createBooking()->load(['room', 'extras']);

        $job = new SendBookingWhatsAppJob($booking);
        $method = new ReflectionMethod($job, 'caption');
        $method->setAccessible(true);

        foreach (['confirmation', 'final'] as $stage) {
            $caption = $method->invoke($job, $booking, $stage);

            $this->assertStringContainsString('Includes: Breakfast, Dinner, Airport transfer', $caption);
            $this->assertStringNotContainsString('INTERNAL-ONLY', $caption);
        }
    }

    public function test_the_internal_note_is_visible_to_staff_in_the_admin_views(): void
    {
        $booking = $this->createBooking();

        $this->actingAs($this->owner)->get(route('bookings.show', $booking))->assertOk()->assertSee(self::NOTE)->assertSee('Internal');
        $this->actingAs($this->owner)->get(route('bookings.index'))->assertOk()->assertSee('INTERNAL-ONLY');
        $this->actingAs($this->owner)->getJson(route('bookings.calendar'))->assertOk()->assertJsonFragment(['special_notes' => self::NOTE]);
    }

    public function test_the_form_carries_the_internal_only_label_and_helper_text(): void
    {
        $this->actingAs($this->owner)
            ->get(route('bookings.create'))
            ->assertOk()
            ->assertSee('Internal Use Only - Not Visible to Guest')
            ->assertSee("will NEVER be shown on the guest's invoice or receipt", false)
            ->assertSee('Included Services / Features')
            ->assertSee('Add Custom Inclusion');
    }

    public function test_a_booking_without_inclusions_has_no_includes_section_on_any_guest_output(): void
    {
        $booking = $this->createBooking(['inclusions' => []]);

        $this->actingAs($this->owner)
            ->get(route('bookings.invoice.view', [$booking, 'confirmation']))
            ->assertOk()
            ->assertDontSee('Includes / Services Provided')
            ->assertDontSee('Services Provided');

        $pdfHtml = view('invoices.pdf', ['booking' => $booking->load(['room', 'extras']), 'stage' => 'final'])->render();
        $this->assertStringNotContainsString('Includes / Services Provided', $pdfHtml);

        $job = new SendBookingWhatsAppJob($booking);
        $method = new ReflectionMethod($job, 'caption');
        $method->setAccessible(true);
        $this->assertStringNotContainsString('Includes', $method->invoke($job, $booking, 'confirmation'));
    }

    public function test_clearing_the_inclusions_on_edit_removes_them_from_the_invoice(): void
    {
        $booking = $this->createBooking();
        $this->assertNotEmpty($booking->all_inclusions);

        // The form submits no inclusions[] at all when the optional section is switched off.
        $this->actingAs($this->owner)
            ->put(route('bookings.update', $booking), collect($this->payload())->except('inclusions')->all())
            ->assertSessionHasNoErrors();

        $this->assertNull($booking->fresh()->inclusions);

        $this->actingAs($this->owner)
            ->get(route('bookings.invoice.view', [$booking, 'confirmation']))
            ->assertDontSee('Includes / Services Provided');
    }

    public function test_the_inclusions_section_is_an_optional_switch_on_the_form(): void
    {
        $this->actingAs($this->owner)
            ->get(route('bookings.create'))
            ->assertOk()
            ->assertSee('Add Inclusions / Services Provided');
    }
}
