{{-- Expects $booking and $wa (App\Services\WhatsAppService instance) from the including view. --}}
@if ($booking->customer_phone)
  <div class="dropdown">
    <button type="button" class="mdash-action-btn mdash-action-btn-whatsapp dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="width:100%;">
      <i class="bi bi-whatsapp"></i> Send WhatsApp Message
    </button>
    <ul class="dropdown-menu wa-dropdown-menu">
      <li>
        <a class="dropdown-item wa-send-btn" href="{{ $wa->getConfirmationUrl($booking) }}" target="_blank" rel="noopener" data-booking-id="{{ $booking->id }}" data-wa-type="confirmation">
          <span>🟢 Send Confirmation</span>
          @if ($booking->wa_confirmation_sent_at)<span class="wa-sent-badge">Sent {{ $booking->wa_confirmation_sent_at->format('h:i A') }}</span>@endif
        </a>
      </li>
      <li>
        <a class="dropdown-item wa-send-btn" href="{{ $wa->getPreCheckinReminderUrl($booking) }}" target="_blank" rel="noopener" data-booking-id="{{ $booking->id }}" data-wa-type="reminder">
          <span>🔵 Send Pre-Checkin Reminder</span>
          @if ($booking->wa_reminder_sent_at)<span class="wa-sent-badge">Sent {{ $booking->wa_reminder_sent_at->format('h:i A') }}</span>@endif
        </a>
      </li>
      <li>
        <a class="dropdown-item wa-send-btn" href="{{ $wa->getCheckinDetailsUrl($booking) }}" target="_blank" rel="noopener" data-booking-id="{{ $booking->id }}" data-wa-type="checkin">
          <span>📍 Send Location &amp; Check-in Info</span>
          @if ($booking->wa_checkin_sent_at)<span class="wa-sent-badge">Sent {{ $booking->wa_checkin_sent_at->format('h:i A') }}</span>@endif
        </a>
      </li>
      <li>
        <a class="dropdown-item wa-send-btn" href="{{ $wa->getCheckoutThankYouUrl($booking) }}" target="_blank" rel="noopener" data-booking-id="{{ $booking->id }}" data-wa-type="thankyou">
          <span>⭐ Send Thank You &amp; Review Request</span>
          @if ($booking->wa_thankyou_sent_at)<span class="wa-sent-badge">Sent {{ $booking->wa_thankyou_sent_at->format('h:i A') }}</span>@endif
        </a>
      </li>
    </ul>
  </div>
@endif
