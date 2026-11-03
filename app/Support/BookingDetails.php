<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\BookingExtra;
use App\Models\Setting;

/**
 * One place that turns a Booking into the plain array the calendar's
 * booking popup (and its downloadable bill) is built from, so the
 * FullCalendar feed and the "Upcoming Bookings" agenda cards always show
 * the same real data. Amounts are pre-formatted with the villa's currency.
 *
 * Expects $booking->room and $booking->extras to be loaded.
 */
class BookingDetails
{
    private const STATUS_LABELS = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'checked_out' => 'Checked Out',
        'cancelled' => 'Cancelled',
    ];

    private const METHOD_LABELS = [
        'cash' => 'Cash',
        'card' => 'Card',
        'bank_transfer' => 'Bank Transfer',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function for(Booking $booking, ?Setting $settings = null): array
    {
        $settings ??= Setting::current();
        $currency = $settings->currency ?: 'LKR';
        $money = fn ($amount) => $currency.' '.number_format((float) $amount, 2);

        $isStay = $booking->booking_type !== 'safari_only';
        $isCheckedOut = $booking->status === 'checked_out';
        $cancelled = $booking->status === 'cancelled';
        $guests = (int) $booking->guests_adults + (int) $booking->guests_children;
        $stage = $isCheckedOut ? 'final' : 'confirmation';

        return [
            'id' => $booking->id,
            'reference' => 'BKG-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT),
            'customer_name' => $booking->customer_name,
            'initials' => $booking->customer_initials,
            'customer_phone' => $booking->customer_phone,
            'customer_email' => $booking->customer_email,
            // Staff-only: shown in the admin popup, never in the bill built from this payload.
            'special_notes' => $booking->special_notes,
            'inclusions' => $booking->all_inclusions,
            'room' => $booking->room_label,
            'room_type' => $booking->room?->type,
            'stay_label' => $booking->room ? 'Room / Cabana' : ($booking->stay_hotel_name ? 'Stay hotel' : 'Tour'),
            'is_stay' => $isStay,
            'check_in' => $booking->check_in?->format('D, d M Y'),
            'check_out' => $booking->check_out?->format('D, d M Y'),
            'check_in_short' => $booking->check_in?->format('d M Y'),
            'check_out_short' => $booking->check_out?->format('d M Y'),
            'nights' => $booking->nights,
            'booking_type' => $booking->booking_type_label,
            'board' => $isStay && $booking->room ? $booking->board_type_label : null,
            'guests' => $isStay && $guests > 0
                ? $guests.' '.($guests === 1 ? 'guest' : 'guests')
                : null,
            'safari' => $booking->booking_type !== 'accommodation_only' && $booking->safari_tour_name ? [
                'tour' => $booking->safari_tour_name,
                'date' => $booking->safari_date?->format('D, d M Y'),
                'vehicle' => $booking->safari_vehicle_details,
                'pickup_location' => $booking->pickup_location,
                'pickup_time' => $booking->pickup_time_short,
                'group_pax' => $booking->group_pax_size,
                'lunch' => (bool) $booking->include_lunch,
                'drone' => (bool) $booking->include_drone_videos,
                'only' => $booking->booking_type === 'safari_only',
            ] : null,
            'status' => $booking->status,
            'status_label' => self::STATUS_LABELS[$booking->status] ?? ucfirst($booking->status),
            'room_charges_label' => $booking->charge_label,
            'room_charges_detail' => match (true) {
                $booking->booking_type === 'full_package' => $booking->nights.' '.($booking->nights === 1 ? 'night' : 'nights').($booking->stay_hotel_name ? ' · '.$booking->stay_hotel_name : ''),
                $isStay => $booking->nights.' '.($booking->nights === 1 ? 'night' : 'nights').' × '.$money((float) $booking->total_amount / max(1, $booking->nights)),
                default => null,
            },
            'room_charges' => $money($booking->total_amount),
            'extras' => $booking->extras->map(fn (BookingExtra $extra) => [
                'name' => $extra->item_name,
                'detail' => $extra->quantity.' × '.$money($extra->unit_price),
                'amount' => $money($extra->line_total),
            ])->values()->all(),
            'total_amount' => $money($booking->grand_total),
            'advance_payment' => $money($booking->advance_payment),
            'balance' => $money($booking->remaining_balance),
            'balance_due' => $booking->remaining_balance > 0,
            'settlement' => $isCheckedOut ? $money($booking->final_settlement_amount) : null,
            'payment_status' => $booking->payment_status_label,
            'payment_method' => $isCheckedOut && $booking->payment_method
                ? (self::METHOD_LABELS[$booking->payment_method] ?? ucfirst(str_replace('_', ' ', $booking->payment_method)))
                : null,
            'doc_title' => $isCheckedOut ? 'FINAL BILL' : 'BOOKING STATEMENT',
            'doc_date' => ($booking->checked_out_at ?? now())->format('d M Y'),
            'file_name' => 'bill-'.$booking->id.'-'.\Illuminate\Support\Str::slug($booking->customer_name).'.png',
            'urls' => [
                'show' => route('bookings.show', $booking),
                'edit' => route('bookings.edit', $booking),
                'bill' => $cancelled ? null : route('bookings.invoice.view', [$booking, $stage]),
                'pdf' => $cancelled ? null : route('bookings.invoice.'.$stage, [$booking, 'download' => 1]),
            ],
        ];
    }
}
