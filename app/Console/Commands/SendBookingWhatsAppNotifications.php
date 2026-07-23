<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Setting;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendBookingWhatsAppNotifications extends Command
{
    /**
     * @var string
     */
    protected $signature = 'bookings:send-whatsapp-notifications';

    /**
     * @var string
     */
    protected $description = 'Send the daily batch of scheduled WhatsApp messages: 24h pre-check-in reminders and check-in day instructions.';

    public function handle(WhatsAppService $whatsapp): int
    {
        $settings = Setting::current();

        $reminders = $this->sendPreCheckinReminders($whatsapp, $settings);
        $checkinInfo = $this->sendCheckinDayInstructions($whatsapp, $settings);

        $this->info("Sent {$reminders} pre-check-in reminder(s) and {$checkinInfo} check-in day instruction(s).");

        return self::SUCCESS;
    }

    /**
     * Bookings checking in tomorrow that haven't had a reminder sent yet.
     */
    private function sendPreCheckinReminders(WhatsAppService $whatsapp, Setting $settings): int
    {
        $bookings = Booking::with('room')
            ->where('status', 'confirmed')
            ->whereDate('check_in', now()->addDay()->toDateString())
            ->whereNull('reminder_sent_at')
            ->get();

        $sent = 0;

        foreach ($bookings as $booking) {
            if (! $booking->customer_phone) {
                continue;
            }

            if ($whatsapp->sendTextMessage($booking->customer_phone, $this->preCheckinMessage($booking, $settings))) {
                $booking->update(['reminder_sent_at' => now()]);
                $sent++;
            } else {
                Log::error('bookings:send-whatsapp-notifications: failed to send pre-check-in reminder', [
                    'booking_id' => $booking->id,
                ]);
            }
        }

        return $sent;
    }

    /**
     * Bookings checking in today that haven't had check-in instructions
     * sent yet.
     */
    private function sendCheckinDayInstructions(WhatsAppService $whatsapp, Setting $settings): int
    {
        $bookings = Booking::with('room')
            ->where('status', 'confirmed')
            ->whereDate('check_in', now()->toDateString())
            ->whereNull('checkin_info_sent_at')
            ->get();

        $sent = 0;

        foreach ($bookings as $booking) {
            if (! $booking->customer_phone) {
                continue;
            }

            if ($whatsapp->sendTextMessage($booking->customer_phone, $this->checkinDayMessage($booking, $settings))) {
                $booking->update(['checkin_info_sent_at' => now()]);
                $sent++;
            } else {
                Log::error('bookings:send-whatsapp-notifications: failed to send check-in day instructions', [
                    'booking_id' => $booking->id,
                ]);
            }
        }

        return $sent;
    }

    private function preCheckinMessage(Booking $booking, Setting $settings): string
    {
        return implode("\n", [
            "🌿 *{$settings->villa_name}*",
            '',
            "Hi {$booking->customer_name}, we are excited to welcome you tomorrow!",
            '',
            "🏡 Room: {$booking->room->name_or_number}",
            '📅 Check-in: '.$booking->check_in->format('d M Y'),
            '⏰ Check-in Time: 2:00 PM',
            '',
            'If you need any special arrangements or early check-in details, please let us know!',
        ]);
    }

    private function checkinDayMessage(Booking $booking, Setting $settings): string
    {
        $location = $settings->google_maps_link ?: $settings->address;

        $lines = [
            "🌿 *{$settings->villa_name}* - Today is Check-in Day!",
            '',
            "Hi {$booking->customer_name}, today is your check-in day!",
            '',
        ];

        if ($location) {
            $lines[] = "📍 Location / Google Maps: {$location}";
        }

        $lines[] = '🔑 Check-in Time: 2:00 PM';

        if ($settings->phone_number) {
            $lines[] = "📞 Front Desk / Manager: {$settings->phone_number}";
        }

        $lines[] = '';
        $lines[] = 'Safe travels! See you soon.';

        return implode("\n", $lines);
    }
}
