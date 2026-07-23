<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Setting;

/**
 * Builds "click-to-send" wa.me deep links for the four guest-messaging
 * templates (booking confirmation, pre-checkin reminder, check-in
 * details, checkout thank-you). There is no WhatsApp Business API
 * integration here - a link just opens wa.me with the message
 * pre-filled, and the admin's own WhatsApp app/web sends it.
 */
class WhatsAppService
{
    /**
     * Maps the "type" query/log parameter used across the AJAX log-sent
     * endpoint and the admin UI to the booking column it timestamps.
     *
     * @var array<string, string>
     */
    public const MESSAGE_TYPES = [
        'confirmation' => 'wa_confirmation_sent_at',
        'reminder' => 'wa_reminder_sent_at',
        'checkin' => 'wa_checkin_sent_at',
        'thankyou' => 'wa_thankyou_sent_at',
    ];

    /**
     * Standard check-in time quoted in the pre-checkin reminder. Not a
     * per-booking or per-villa setting (out of this feature's scope),
     * so it's a single shared constant instead.
     */
    private const STANDARD_CHECKIN_TIME = '2:00 PM';

    /**
     * Booking Confirmation & Advance Invoice - sent on a new booking or
     * on receipt of the advance payment.
     */
    public function getConfirmationUrl(Booking $booking): ?string
    {
        $settings = Setting::current();

        $lines = [
            "Hi {$booking->customer_name}, thank you for booking with {$settings->villa_name}! ✅",
            '',
            'Booking Confirmation:',
            "🏡 Cabana/Villa: {$booking->room->name_or_number}",
            "📅 Check-in: {$booking->check_in->format('d M Y')}",
            "📅 Check-out: {$booking->check_out->format('d M Y')}",
            "💰 Advance Paid: {$settings->currency} ".number_format((float) $booking->advance_payment, 2),
            "💳 Balance Due: {$settings->currency} ".number_format($booking->remaining_balance, 2),
            '',
            'We look forward to hosting you!',
        ];

        return $this->buildUrl($booking->customer_phone, implode("\n", $lines));
    }

    /**
     * Pre-Checkin Reminder - sent the day before arrival.
     */
    public function getPreCheckinReminderUrl(Booking $booking): ?string
    {
        $settings = Setting::current();

        $lines = [
            "Hi {$booking->customer_name}, this is a friendly reminder from {$settings->villa_name}! 🔔",
            '',
            "Your check-in is tomorrow, {$booking->check_in->format('d M Y')}.",
            '🕒 Standard check-in time: '.self::STANDARD_CHECKIN_TIME,
            "💳 Balance to settle on arrival: {$settings->currency} ".number_format($booking->remaining_balance, 2),
            '',
            'See you soon!',
        ];

        return $this->buildUrl($booking->customer_phone, implode("\n", $lines));
    }

    /**
     * Check-in Welcome & Location Details - sent on check-in day morning.
     */
    public function getCheckinDetailsUrl(Booking $booking): ?string
    {
        $settings = Setting::current();

        $lines = [
            "Good morning {$booking->customer_name}! Welcome to {$settings->villa_name} 🎉 Today's the day!",
            '',
        ];

        if ($settings->google_map_link) {
            $lines[] = "📍 Location: {$settings->google_map_link}";
        }

        if ($settings->wifi_name) {
            $lines[] = "📶 WiFi Network: {$settings->wifi_name}";
        }

        if ($settings->wifi_password) {
            $lines[] = "🔑 WiFi Password: {$settings->wifi_password}";
        }

        $hostContact = $settings->public_whatsapp_number ?: $settings->phone_number;

        if ($hostContact) {
            $lines[] = "📞 Host Contact: {$hostContact}";
        }

        $lines[] = '';
        $lines[] = "Safe travels, we can't wait to welcome you!";

        return $this->buildUrl($booking->customer_phone, implode("\n", $lines));
    }

    /**
     * Checkout Thank You, Full Payment Receipt & Social Review Request -
     * sent on checkout/full payment settlement.
     */
    public function getCheckoutThankYouUrl(Booking $booking): ?string
    {
        $settings = Setting::current();

        $lines = [
            "Dear {$booking->customer_name}, thank you for staying with {$settings->villa_name}! 🙏",
            '',
            "✅ Full Payment Confirmed: {$settings->currency} ".number_format((float) $booking->total_amount, 2),
            '',
            'We hope you had a wonderful stay. It would mean a lot if you could leave us a review:',
        ];

        if ($settings->google_review_link) {
            $lines[] = "⭐ Google Review: {$settings->google_review_link}";
        }

        if ($settings->tripadvisor_link) {
            $lines[] = "⭐ TripAdvisor: {$settings->tripadvisor_link}";
        }

        if ($settings->social_media_link) {
            $lines[] = "📱 Follow us: {$settings->social_media_link}";
        }

        $lines[] = '';
        $lines[] = 'Hope to host you again soon!';

        return $this->buildUrl($booking->customer_phone, implode("\n", $lines));
    }

    /**
     * Formats a phone number to the international digits-only format
     * wa.me expects (e.g. a Sri Lankan local number "0771234567"
     * becomes "94771234567"). Returns null for a blank/unusable number.
     */
    public function formatPhoneNumber(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '94'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '94')) {
            $digits = '94'.$digits;
        }

        return $digits;
    }

    /**
     * Builds a wa.me deep link for the given raw phone number and
     * message, or null if the phone number can't be formatted.
     */
    private function buildUrl(?string $phone, string $message): ?string
    {
        $formatted = $this->formatPhoneNumber($phone);

        if (! $formatted) {
            return null;
        }

        return 'https://wa.me/'.$formatted.'?text='.rawurlencode($message);
    }
}
