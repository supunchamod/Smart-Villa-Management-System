@extends('layouts.app')

@section('title', 'Booking Details | ' . ($globalSettings->villa_name ?? config('app.name')))

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li><a href="{{ route('bookings.index') }}">Bookings</a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>{{ $booking->customer_name }}</h1></li>
            </ol>
          </nav>
          <div class="page-actions booking-detail-actions" x-data="bookingActions('{{ route('bookings.send-whatsapp', $booking) }}')">
            <a class="btn btn-light" href="{{ route('bookings.edit', $booking) }}"><i class="bi bi-pencil"></i> Edit</a>
            @if ($booking->status !== 'cancelled')
              <a class="btn btn-light" href="{{ route('bookings.invoice.confirmation', $booking) }}" target="_blank"><i class="bi bi-file-earmark-pdf"></i> Confirmation Invoice</a>
            @endif
            @if ($booking->status === 'checked_out')
              <a class="btn btn-light" href="{{ route('bookings.invoice.view', [$booking, 'final']) }}" target="_blank"><i class="bi bi-receipt"></i> View Bill</a>
              <a class="btn btn-light" href="{{ route('bookings.invoice.final', [$booking, 'download' => 1]) }}"><i class="bi bi-file-earmark-pdf"></i> Download Final Invoice (PDF)</a>
            @endif
            @if (in_array($booking->status, ['confirmed', 'checked_out'], true) && $booking->customer_phone)
              <button type="button" class="btn btn-outline-success" @click="sendWhatsApp()" :disabled="waSending">
                <i class="bi bi-whatsapp"></i> <span x-text="waSending ? 'Sending…' : 'Send WhatsApp Message'"></span>
              </button>
            @endif
            @if ($booking->status === 'confirmed')
              <button type="button" class="btn btn-primary" @click="bootstrap.Modal.getOrCreateInstance(document.getElementById('checkoutModal')).show()"><i class="bi bi-box-arrow-right"></i> Checkout</button>
            @endif
          </div>
        </div>

        @if (session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row g-4 booking-detail-grid">
          <div class="col-xl-7">
            <div class="panel">
              <div class="panel-head"><div><h2>Booking Details</h2><p>Customer and stay information</p></div></div>
              <div class="row g-3 booking-info-grid">
                <div class="col-md-6"><small class="text-muted d-block">Customer</small><strong>{{ $booking->customer_name }}</strong></div>
                <div class="col-md-6">
                  <small class="text-muted d-block">Booking Type</small>
                  @php
                    $typeBadge = ['accommodation_only' => 'type-badge-cabana', 'safari_only' => 'type-badge-safari', 'full_package' => 'type-badge-combo'][$booking->booking_type] ?? 'type-badge-cabana';
                  @endphp
                  <span class="type-badge {{ $typeBadge }}">{{ $booking->booking_type_label }}</span>
                </div>
                @if ($booking->room)
                  <div class="col-md-6"><small class="text-muted d-block">Room</small><strong>{{ $booking->room->name_or_number }} ({{ $booking->room->type }})</strong></div>
                  <div class="col-md-6">
                    <small class="text-muted d-block">Board Basis / Meal Plan</small>
                    <span class="board-badge {{ $booking->board_type_badge_class }}">{{ $booking->board_type_label }}</span>
                  </div>
                @endif
                <div class="col-md-6"><small class="text-muted d-block">Email</small><strong>{{ $booking->customer_email ?: '—' }}</strong></div>
                <div class="col-md-6"><small class="text-muted d-block">Phone</small><strong>{{ $booking->customer_phone ?: '—' }}</strong></div>
                @if ($booking->booking_type !== 'safari_only')
                  <div class="col-md-6"><small class="text-muted d-block">Check-in</small><strong>{{ $booking->check_in->format('d M Y') }}</strong></div>
                  <div class="col-md-6"><small class="text-muted d-block">Check-out</small><strong>{{ $booking->check_out->format('d M Y') }}</strong></div>
                @endif
                @if ($booking->booking_type !== 'accommodation_only')
                  <div class="col-md-6"><small class="text-muted d-block">Safari Tour</small><strong>{{ $booking->safari_tour_name }}</strong></div>
                  <div class="col-md-6"><small class="text-muted d-block">Safari Date</small><strong>{{ optional($booking->safari_date)->format('d M Y') }}</strong></div>
                  @if ($booking->safari_vehicle_details)
                    <div class="col-md-6"><small class="text-muted d-block">Vehicle Details</small><strong>{{ $booking->safari_vehicle_details }}</strong></div>
                  @endif
                @endif
                <div class="col-md-6">
                  <small class="text-muted d-block">Status</small>
                  @php
                    $badge = ['pending' => 'pending', 'confirmed' => 'new', 'checked_out' => 'won', 'cancelled' => 'stuck'][$booking->status] ?? 'new';
                  @endphp
                  <span class="deal-badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                </div>
              </div>
            </div>
          </div>
          <div class="col-xl-5">
            <div class="panel booking-detail-balance">
              <div class="panel-head"><div><h2>Payment Summary</h2><p>Total, advance, and outstanding balance</p></div></div>
              <div class="balance-grid">
                <div class="balance-stat"><span>{{ $booking->extras->isNotEmpty() ? 'Total (incl. extras)' : 'Total Amount' }}</span><strong>{{ number_format($booking->grand_total, 2) }}</strong></div>
                <div class="balance-stat"><span>Advance Payment</span><strong>{{ number_format($booking->advance_payment, 2) }}</strong></div>
                <div class="balance-stat"><span>Remaining Balance</span><strong>{{ number_format($booking->remaining_balance, 2) }}</strong></div>
              </div>
              @if ($booking->extras->isNotEmpty())
                <div class="co-section mb-0">
                  <div class="co-section-title">Charges</div>
                  <div class="co-line mb-2"><span>Room charges</span><strong>{{ number_format($booking->total_amount, 2) }}</strong></div>
                  @foreach ($booking->extras as $extra)
                    <div class="co-line mb-2"><div><strong>{{ $extra->item_name }}</strong><small>{{ $extra->quantity }} × {{ number_format($extra->unit_price, 2) }}</small></div><strong>{{ number_format($extra->line_total, 2) }}</strong></div>
                  @endforeach
                  @if ($booking->payment_method)
                    <small class="text-muted">Balance paid by {{ ucfirst(str_replace('_', ' ', $booking->payment_method)) }}</small>
                  @endif
                </div>
              @endif
            </div>
          </div>
        </div>

        @if ($booking->status === 'confirmed')
          @include('bookings._checkout-modal', ['booking' => $booking, 'modalId' => 'checkoutModal'])
        @endif
@endsection

@push('scripts')
<script>
    function bookingActions(sendWhatsAppUrl) {
        return {
            waSending: false,
            async sendWhatsApp() {
                this.waSending = true;
                try {
                    const response = await fetch(sendWhatsAppUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });
                    const data = await response.json();
                    this.showToast(data.message || (data.success ? 'WhatsApp dispatch initiated' : 'Failed to send WhatsApp message.'));
                } catch (error) {
                    console.error('Failed to dispatch WhatsApp message', error);
                    this.showToast('Failed to send WhatsApp message. Please try again.');
                } finally {
                    this.waSending = false;
                }
            },
            showToast(message) {
                const toastEl = document.getElementById('actionToast');
                if (!toastEl || !window.bootstrap) return;
                const body = toastEl.querySelector('.toast-body');
                if (body) body.textContent = message;
                bootstrap.Toast.getOrCreateInstance(toastEl).show();
            },
        };
    }
</script>
@endpush
