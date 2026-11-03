@extends('layouts.app')

@section('title', 'Booking Details | ' . ($globalSettings->villa_name ?? config('app.name')))

@section('content')
    @php
        $currency = $globalSettings->currency;
        $money = fn ($amount) => $currency.' '.number_format((float) $amount, 2);
        $isStay = $booking->booking_type !== 'safari_only';
        $hasSafari = $booking->booking_type !== 'accommodation_only';
        $isCheckedOut = $booking->status === 'checked_out';
        $statusBadge = ['pending' => 'pending', 'confirmed' => 'new', 'checked_out' => 'won', 'cancelled' => 'stuck'][$booking->status] ?? 'new';
        $statusLabel = ucfirst(str_replace('_', ' ', $booking->status));
        $typeBadge = ['accommodation_only' => 'type-badge-cabana', 'safari_only' => 'type-badge-safari', 'full_package' => 'type-badge-combo'][$booking->booking_type] ?? 'type-badge-cabana';
        $paymentLabel = $booking->payment_status_label;
        $paymentTone = ['Fully Paid' => 'paid', 'Partially Paid' => 'partial', 'Unpaid' => 'unpaid'][$paymentLabel] ?? 'unpaid';
        $methodLabels = ['cash' => 'Cash', 'card' => 'Card', 'bank_transfer' => 'Bank Transfer'];
        $guests = ((int) $booking->guests_adults) + ((int) $booking->guests_children);
    @endphp

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
    </div>

    @if (session('status'))
      <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Header card: who/what at a glance, plus the action toolbar --}}
    <section class="bd-hero" x-data="bookingActions('{{ route('bookings.send-whatsapp', $booking) }}')">
      <div class="bd-hero-main">
        <span class="bd-avatar" aria-hidden="true">{{ $booking->customer_initials }}</span>
        <div class="bd-hero-text">
          <div class="bd-hero-title">
            <h2>{{ $booking->customer_name }}</h2>
            <span class="deal-badge {{ $statusBadge }}">{{ $statusLabel }}</span>
          </div>
          <div class="bd-hero-meta">
            <span><i class="bi bi-hash"></i>Booking {{ $booking->id }}</span>
            <span class="type-badge {{ $typeBadge }}">{{ $booking->booking_type_label }}</span>
            @if ($isStay && $booking->room)
              <span><i class="bi bi-door-open"></i>{{ $booking->room->name_or_number }}</span>
            @endif
            @if ($isStay)
              <span><i class="bi bi-calendar3"></i>{{ $booking->check_in->format('d M') }} – {{ $booking->check_out->format('d M Y') }}</span>
            @endif
          </div>
        </div>
      </div>

      <div class="bd-toolbar" role="toolbar" aria-label="Booking actions">
        @if ($booking->status === 'confirmed')
          <button type="button" class="bd-btn bd-btn-primary" @click="bootstrap.Modal.getOrCreateInstance(document.getElementById('checkoutModal')).show()"><i class="bi bi-box-arrow-right"></i><span>Checkout</span></button>
        @endif
        <a class="bd-btn" href="{{ route('bookings.edit', $booking) }}"><i class="bi bi-pencil-square"></i><span>Edit</span></a>
        @if ($booking->status !== 'cancelled')
          <a class="bd-btn" href="{{ route('bookings.invoice.confirmation', $booking) }}" target="_blank" rel="noopener"><i class="bi bi-file-earmark-text"></i><span>Confirmation Invoice</span></a>
        @endif
        @if ($isCheckedOut)
          <a class="bd-btn" href="{{ route('bookings.invoice.view', [$booking, 'final']) }}" target="_blank" rel="noopener"><i class="bi bi-receipt"></i><span>View Bill</span></a>
          <a class="bd-btn" href="{{ route('bookings.invoice.final', [$booking, 'download' => 1]) }}"><i class="bi bi-file-earmark-pdf"></i><span>Final Invoice <em>(PDF)</em></span></a>
        @endif
        @if (in_array($booking->status, ['confirmed', 'checked_out'], true) && $booking->customer_phone)
          <button type="button" class="bd-btn bd-btn-wa" @click="sendWhatsApp()" :disabled="waSending">
            <i class="bi bi-whatsapp"></i><span x-text="waSending ? 'Sending…' : 'Send WhatsApp'"></span>
          </button>
        @endif
      </div>
    </section>

    <div class="row g-4 booking-detail-grid">
      {{-- ============ Booking details ============ --}}
      <div class="col-xl-7">
        <section class="bd-card">
          <header class="bd-card-head">
            <span class="bd-card-icon"><i class="bi bi-journal-text"></i></span>
            <div><h2>Booking Details</h2><p>Customer and stay information</p></div>
          </header>

          <div class="bd-group">
            <h3>Guest</h3>
            <dl class="bd-fields">
              <div class="bd-field"><dt><i class="bi bi-person"></i>Customer</dt><dd>{{ $booking->customer_name }}</dd></div>
              <div class="bd-field"><dt><i class="bi bi-telephone"></i>Phone</dt><dd>@if ($booking->customer_phone)<a href="tel:{{ preg_replace('/[^\d+]/', '', $booking->customer_phone) }}">{{ $booking->customer_phone }}</a>@else<span class="bd-empty">Not provided</span>@endif</dd></div>
              <div class="bd-field bd-field-wide"><dt><i class="bi bi-envelope"></i>Email</dt><dd>@if ($booking->customer_email)<a href="mailto:{{ $booking->customer_email }}">{{ $booking->customer_email }}</a>@else<span class="bd-empty">Not provided</span>@endif</dd></div>
            </dl>
          </div>

          @if ($isStay)
            <div class="bd-group">
              <h3>Stay</h3>
              <dl class="bd-fields">
                @if ($booking->room)
                  <div class="bd-field bd-field-wide"><dt><i class="bi bi-door-open"></i>Room</dt><dd>{{ $booking->room->name_or_number }} <span class="bd-sub">{{ $booking->room->type }}</span></dd></div>
                @endif
                <div class="bd-field"><dt><i class="bi bi-box-arrow-in-right"></i>Check-in</dt><dd>{{ $booking->check_in->format('D, d M Y') }}</dd></div>
                <div class="bd-field"><dt><i class="bi bi-box-arrow-right"></i>Check-out</dt><dd>{{ $booking->check_out->format('D, d M Y') }}</dd></div>
                <div class="bd-field"><dt><i class="bi bi-moon-stars"></i>Duration</dt><dd>{{ $booking->nights }} {{ \Illuminate\Support\Str::plural('night', $booking->nights) }}</dd></div>
                @if ($booking->room)
                  <div class="bd-field"><dt><i class="bi bi-cup-hot"></i>Board Basis</dt><dd><span class="board-badge {{ $booking->board_type_badge_class }}">{{ $booking->board_type_label }}</span></dd></div>
                @endif
                @if ($guests > 0)
                  <div class="bd-field"><dt><i class="bi bi-people"></i>Guests</dt><dd>{{ $booking->guests_adults }} {{ \Illuminate\Support\Str::plural('adult', (int) $booking->guests_adults) }}@if ($booking->guests_children), {{ $booking->guests_children }} {{ \Illuminate\Support\Str::plural('child', (int) $booking->guests_children) }}@endif</dd></div>
                @endif
              </dl>
            </div>
          @endif

          @if ($hasSafari)
            <div class="bd-group">
              <h3>Safari</h3>
              <dl class="bd-fields">
                <div class="bd-field"><dt><i class="bi bi-compass"></i>Tour</dt><dd>{{ $booking->safari_tour_name }}</dd></div>
                <div class="bd-field"><dt><i class="bi bi-calendar-event"></i>Date</dt><dd>{{ optional($booking->safari_date)->format('D, d M Y') }}</dd></div>
                @if ($booking->pickup_location)
                  <div class="bd-field bd-field-wide"><dt><i class="bi bi-geo-alt"></i>Pickup location</dt><dd>{{ $booking->pickup_location }}</dd></div>
                @endif
                @if ($booking->pickup_time)
                  <div class="bd-field"><dt><i class="bi bi-clock"></i>Pickup time</dt><dd>{{ $booking->pickup_time_short }}</dd></div>
                @endif
                @if ($booking->group_pax_size)
                  <div class="bd-field"><dt><i class="bi bi-people"></i>Group size</dt><dd>{{ $booking->group_pax_size }} pax</dd></div>
                @endif
                @if ($booking->booking_type === 'safari_only')
                  <div class="bd-field bd-field-wide"><dt><i class="bi bi-check2-square"></i>Inclusions</dt><dd>
                    <span class="bd-incl {{ $booking->include_lunch ? 'on' : '' }}"><i class="bi bi-{{ $booking->include_lunch ? 'check-circle-fill' : 'dash-circle' }}"></i> Lunch</span>
                    <span class="bd-incl {{ $booking->include_drone_videos ? 'on' : '' }}"><i class="bi bi-{{ $booking->include_drone_videos ? 'check-circle-fill' : 'dash-circle' }}"></i> Drone videos</span>
                  </dd></div>
                @endif
                @if ($booking->safari_vehicle_details)
                  <div class="bd-field bd-field-wide"><dt><i class="bi bi-truck"></i>Vehicle</dt><dd>{{ $booking->safari_vehicle_details }}</dd></div>
                @endif
              </dl>
            </div>
          @endif
          @if ($booking->special_notes)
            <div class="bd-group">
              <h3>Special notes</h3>
              <p class="bd-notes"><i class="bi bi-chat-left-text"></i><span>{{ $booking->special_notes }}</span></p>
            </div>
          @endif
        </section>
      </div>

      {{-- ============ Payment summary ============ --}}
      <div class="col-xl-5">
        <section class="bd-card bd-pay">
          <header class="bd-card-head">
            <span class="bd-card-icon bd-card-icon-green"><i class="bi bi-wallet2"></i></span>
            <div><h2>Payment Summary</h2><p>Charges, advance and balance</p></div>
            <span class="bd-pay-pill bd-pay-{{ $paymentTone }}">{{ $paymentLabel }}</span>
          </header>

          <div class="bd-stats">
            <div class="bd-stat bd-stat-total">
              <span>{{ $booking->extras->isNotEmpty() ? 'Total (incl. extras)' : 'Total' }}</span>
              <strong>{{ $money($booking->grand_total) }}</strong>
            </div>
            <div class="bd-stat">
              <span>Advance paid</span>
              <strong>{{ $money($booking->advance_payment) }}</strong>
            </div>
            <div class="bd-stat {{ $booking->remaining_balance > 0 ? 'bd-stat-due' : 'bd-stat-clear' }}">
              <span>{{ $isCheckedOut ? 'Remaining balance' : 'Balance due' }}</span>
              <strong>{{ $money($booking->remaining_balance) }}</strong>
            </div>
          </div>

          <div class="bd-group bd-breakdown">
            <h3>Charges breakdown</h3>
            <ul class="bd-lines">
              <li class="bd-line">
                <div class="bd-line-main">
                  <strong>{{ $isStay ? 'Room charges' : 'Safari charges' }}</strong>
                  @if ($isStay)<small>{{ $booking->nights }} {{ \Illuminate\Support\Str::plural('night', $booking->nights) }} × {{ $money((float) $booking->total_amount / max(1, $booking->nights)) }}</small>@endif
                </div>
                <span class="bd-line-amount">{{ $money($booking->total_amount) }}</span>
              </li>
              @forelse ($booking->extras as $extra)
                <li class="bd-line">
                  <div class="bd-line-main">
                    <strong>{{ $extra->item_name }}</strong>
                    <small>{{ $extra->quantity }} × {{ $money($extra->unit_price) }}</small>
                  </div>
                  <span class="bd-line-amount">{{ $money($extra->line_total) }}</span>
                </li>
              @empty
                <li class="bd-line bd-line-empty">No extra charges</li>
              @endforelse
            </ul>

            <dl class="bd-totals">
              <div class="bd-total-row bd-total-sub"><dt>Subtotal</dt><dd>{{ $money($booking->grand_total) }}</dd></div>
              <div class="bd-total-row"><dt>Less: advance paid</dt><dd>− {{ $money($booking->advance_payment) }}</dd></div>
              @if ($isCheckedOut)
                <div class="bd-total-row"><dt>Balance settled at checkout</dt><dd>{{ $money($booking->final_settlement_amount) }}</dd></div>
              @endif
              <div class="bd-total-row bd-total-due {{ $booking->remaining_balance > 0 ? '' : 'is-clear' }}">
                <dt>Balance due</dt>
                <dd>{{ $money($booking->remaining_balance) }}</dd>
              </div>
            </dl>

            {{-- Internal margin: the villa's own direct costs for this booking. Never shown on guest-facing bills. --}}
            <div class="bd-internal">
              <div class="bd-internal-title"><i class="bi bi-lock"></i> Internal · not on the guest bill</div>
              <div class="bd-total-row"><span>Booking expenses (direct costs)</span><span class="bd-internal-amt">− {{ $money($booking->expenses_total) }}</span></div>
              <div class="bd-total-row bd-internal-profit {{ $booking->booking_profit < 0 ? 'is-loss' : '' }}"><span>Profit on this booking</span><span class="bd-internal-amt">{{ $money($booking->booking_profit) }}</span></div>
            </div>

            @if ($isCheckedOut && ($booking->payment_method || $booking->checked_out_at))
              <p class="bd-paid-note">
                <i class="bi bi-check-circle-fill"></i>
                <span>
                  @if ($booking->payment_method)Paid by {{ $methodLabels[$booking->payment_method] ?? ucfirst(str_replace('_', ' ', $booking->payment_method)) }}@endif
                  @if ($booking->checked_out_at) · {{ $booking->checked_out_at->format('d M Y, H:i') }}@endif
                </span>
              </p>
            @endif
          </div>
        </section>
      </div>
    </div>

    {{-- ============ Booking expenses / direct costs ============ --}}
    <section class="bd-card bd-expenses mt-4">
      <header class="bd-card-head">
        <span class="bd-card-icon bd-card-icon-amber"><i class="bi bi-receipt-cutoff"></i></span>
        <div><h2>Booking Expenses / Direct Costs</h2><p>Costs the villa pays for this booking (external meals, transport…). Not billed to the guest.</p></div>
        <span class="bd-exp-total">{{ $money($booking->expenses_total) }}</span>
      </header>

      @if ($booking->expenses->isNotEmpty())
        <ul class="bd-exp-list">
          @foreach ($booking->expenses as $expense)
            <li class="bd-exp-row">
              <div class="bd-exp-main">
                <strong>{{ $expense->category }}</strong>
                @if ($expense->description)<small>{{ $expense->description }}</small>@endif
                <small class="bd-exp-date">{{ $expense->expense_date->format('d M Y') }}</small>
              </div>
              <span class="bd-line-amount">{{ $money($expense->amount) }}</span>
              <form method="POST" action="{{ route('bookings.expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this expense?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="bd-exp-delete" aria-label="Delete expense"><i class="bi bi-trash3"></i></button>
              </form>
            </li>
          @endforeach
        </ul>
      @else
        <p class="bd-exp-empty">No direct costs recorded for this booking yet.</p>
      @endif

      @if ($booking->status !== 'cancelled')
        <form method="POST" action="{{ route('bookings.expenses.store', $booking) }}" class="bd-exp-form">
          @csrf
          <div class="bd-exp-field">
            <label for="expCategory">Category</label>
            <input id="expCategory" name="category" type="text" class="form-control @error('category') is-invalid @enderror" list="expCategories" value="{{ old('category') }}" placeholder="e.g. Transport" maxlength="100" required>
            <datalist id="expCategories">@foreach (\App\Models\Expense::BOOKING_CATEGORIES as $category)<option value="{{ $category }}">@endforeach</datalist>
            @error('category')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="bd-exp-field bd-exp-field-grow">
            <label for="expDescription">Description <span class="text-muted">(optional)</span></label>
            <input id="expDescription" name="description" type="text" class="form-control @error('description') is-invalid @enderror" value="{{ old('description') }}" placeholder="e.g. Tuk-tuk to the station" maxlength="255">
            @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="bd-exp-field">
            <label for="expDate">Date</label>
            <input id="expDate" name="expense_date" type="date" class="form-control @error('expense_date') is-invalid @enderror" value="{{ old('expense_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}">
            @error('expense_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="bd-exp-field">
            <label for="expAmount">Amount ({{ $currency }})</label>
            <input id="expAmount" name="amount" type="number" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" placeholder="0.00" inputmode="decimal" required>
            @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <button type="submit" class="bd-btn bd-btn-primary bd-exp-add"><i class="bi bi-plus-lg"></i><span>Add expense</span></button>
        </form>
      @endif
    </section>

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
