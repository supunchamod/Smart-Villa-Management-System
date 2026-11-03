@extends('layouts.app')

@section('title', 'Partner Resort Bookings | ' . ($globalSettings->villa_name ?? config('app.name')))

@push('styles')
  <link href="{{ asset('assets/css/bookings-index.css') }}" rel="stylesheet">
@endpush

@section('content')
@php
    $currency = $globalSettings->currency;
    $money = fn ($amount) => $currency.' '.number_format((float) $amount, 2);
    $tabUrl = fn (string $key) => route('partner-bookings.index', array_filter(['tab' => $key === 'all' ? null : $key, 'search' => $search]));
    $tabs = ['all' => 'All', 'pending_advance' => 'Pending Advance', 'advance_paid' => 'Advance Paid / Confirmed', 'pending_final' => 'Pending Final Settlement', 'settled' => 'Fully Settled'];

    // Re-open the right modal with the user's input after a validation error.
    $oldEntry = old('entry_id') ? \App\Models\PartnerBooking::with('booking')->find(old('entry_id')) : null;
@endphp
<div x-data="partnerManager(@js([
    'currency' => $currency,
    'today' => today()->toDateString(),
    'reopen' => $errors->any() && $oldEntry ? old('_modal') : null,
    'old' => $oldEntry ? [
        'id' => $oldEntry->id,
        'booking_id' => $oldEntry->booking_id,
        'guest' => $oldEntry->booking?->customer_name,
        'partner_name' => old('partner_name', $oldEntry->partner_name),
        'agreed_cost' => old('agreed_cost', $oldEntry->agreed_cost),
        'advance' => old('advance_amount', $oldEntry->advance_paid),
        'advance_date' => old('advance_date', optional($oldEntry->advance_paid_on)->toDateString()),
        'remaining' => $oldEntry->remaining_cost,
        'final_amount' => old('final_amount', $oldEntry->remaining_cost),
        'settled_date' => old('settled_date'),
        'advance_url' => route('partner-bookings.advance', $oldEntry),
        'settle_url' => route('partner-bookings.settle', $oldEntry),
    ] : null,
]))">
  <div class="page-title">
    <nav class="page-breadcrumb" aria-label="breadcrumb">
      <ol>
        <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
        <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
        <li aria-current="page"><h1>Partner Resort Bookings</h1></li>
      </ol>
    </nav>
  </div>

  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger"><strong>Could not save.</strong> {{ $errors->first() }}</div>
  @endif

  <div class="ex-kpis pb-kpis">
    <div class="ex-kpi">
      <span class="ex-kpi-icon ex-kpi-amber"><i class="bi bi-hourglass-split"></i></span>
      <div><span>Pending advance</span><strong>{{ $counts['pending_advance'] }}</strong><small>Partner not yet confirmed</small></div>
    </div>
    <div class="ex-kpi {{ $counts['pending_final'] > 0 ? 'pb-kpi-alert' : '' }}">
      <span class="ex-kpi-icon pb-kpi-red"><i class="bi bi-exclamation-triangle"></i></span>
      <div><span>Pending final settlement</span><strong>{{ $counts['pending_final'] }}</strong><small>Guest checked out, partner unpaid</small></div>
    </div>
    <div class="ex-kpi">
      <span class="ex-kpi-icon ex-kpi-blue"><i class="bi bi-cash-stack"></i></span>
      <div><span>Still owed to partners</span><strong>{{ $money($outstanding) }}</strong><small>Agreed cost minus advances</small></div>
    </div>
    <div class="ex-kpi">
      <span class="ex-kpi-icon ex-kpi-green"><i class="bi bi-check2-circle"></i></span>
      <div><span>Fully settled</span><strong>{{ $counts['settled'] }}</strong><small>Paid in full</small></div>
    </div>
  </div>

  <div class="panel ex-panel">
    <div class="bk-pills pb-pills" role="tablist" aria-label="Filter partner bookings">
      @foreach ($tabs as $key => $label)
        <a role="tab" href="{{ $tabUrl($key) }}" class="bk-pill {{ $tab === $key ? 'active' : '' }}" aria-selected="{{ $tab === $key ? 'true' : 'false' }}">
          {{ $label }} <span class="pb-pill-count">{{ $counts[$key] }}</span>
        </a>
      @endforeach
    </div>

    <form method="GET" action="{{ route('partner-bookings.index') }}" class="ex-filters">
      @if ($tab !== 'all')<input type="hidden" name="tab" value="{{ $tab }}">@endif
      <div class="ex-search">
        <i class="bi bi-search"></i>
        <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Search guest, partner, hotel or booking #…" aria-label="Search partner bookings">
      </div>
      <button type="submit" class="btn btn-light"><i class="bi bi-search"></i> Search</button>
      @if ($search !== '')<a href="{{ route('partner-bookings.index', array_filter(['tab' => $tab === 'all' ? null : $tab])) }}" class="btn btn-link ex-reset">Clear</a>@endif
    </form>

    @php
        $rows = $entries->map(function ($entry) use ($money) {
            $booking = $entry->booking;

            return [
                'entry' => $entry,
                'booking' => $booking,
                'meta' => $entry->stage_meta,
                'stage' => $entry->stage,
                'payload' => [
                    'id' => $entry->id,
                    'booking_id' => $entry->booking_id,
                    'guest' => $booking?->customer_name,
                    'partner_name' => $entry->partner_name ?? '',
                    'agreed_cost' => $entry->agreed_cost !== null ? (float) $entry->agreed_cost : '',
                    'advance' => (float) $entry->advance_paid,
                    'advance_date' => optional($entry->advance_paid_on)->toDateString(),
                    'remaining' => $entry->remaining_cost,
                    'final_amount' => $entry->remaining_cost,
                    'settled_date' => null,
                    'advance_url' => route('partner-bookings.advance', $entry),
                    'settle_url' => route('partner-bookings.settle', $entry),
                ],
            ];
        });
    @endphp

    {{-- Desktop table --}}
    <div class="table-responsive d-none d-md-block">
      <table class="table align-middle dash-table ex-table pb-table">
        <thead>
          <tr>
            <th>Guest booking</th>
            <th>Package</th>
            <th>Partner</th>
            <th class="text-end">Agreed</th>
            <th class="text-end">Advance</th>
            <th class="text-end">Remaining</th>
            <th>Status</th>
            <th><span class="visually-hidden">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($rows as $row)
            @php $entry = $row['entry']; $booking = $row['booking']; @endphp
            <tr class="{{ $row['stage'] === 'pending_final' ? 'pb-row-alert' : '' }}">
              <td>
                <a href="{{ route('bookings.show', $booking) }}"><strong>#{{ $booking->id }} · {{ $booking->customer_name }}</strong></a><br>
                <small class="text-muted">{{ $booking->check_in->format('d M') }} – {{ $booking->check_out->format('d M Y') }}@if ($booking->status === 'cancelled') · <span class="text-danger">Booking cancelled</span>@elseif ($booking->status === 'checked_out') · Checked out @endif</small>
              </td>
              <td>
                <span class="ex-cat">{{ $booking->stay_hotel_name ?: '—' }}</span><br>
                <small class="text-muted">{{ $booking->safari_tour_name }}</small>
              </td>
              <td>@if ($entry->partner_name)<strong>{{ $entry->partner_name }}</strong>@else<span class="text-muted">Not assigned</span>@endif</td>
              <td class="text-end ex-amount">{{ $entry->agreed_cost !== null ? number_format($entry->agreed_cost, 2) : '—' }}</td>
              <td class="text-end ex-amount">{{ $entry->status === 'pending_advance' ? '—' : number_format($entry->advance_paid, 2) }}</td>
              <td class="text-end ex-amount">@if ($entry->status === 'pending_advance')—@elseif ($entry->status === 'settled')<span class="text-success">Paid</span>@else{{ number_format($entry->remaining_cost, 2) }}@endif</td>
              <td>
                <span class="deal-badge {{ $row['meta']['class'] }} {{ $row['stage'] === 'pending_final' ? 'pb-flag' : '' }}">{{ $row['meta']['label'] }}</span>
                @if ($entry->status === 'settled' && $entry->settled_on)<br><small class="text-muted">{{ $entry->settled_on->format('d M Y') }}</small>@endif
              </td>
              <td class="text-end">
                @if ($row['stage'] === 'pending_advance')
                  <button type="button" class="btn btn-sm btn-primary" @click="openAdvance(@js($row['payload']))"><i class="bi bi-cash-coin"></i> Record Advance</button>
                @elseif ($row['stage'] === 'pending_final')
                  <button type="button" class="btn btn-sm btn-danger" @click="openSettle(@js($row['payload']))"><i class="bi bi-check2-circle"></i> Settle Remaining</button>
                  <button type="button" class="btn btn-sm btn-light" @click="openAdvance(@js($row['payload']))" title="Edit advance details" aria-label="Edit advance details"><i class="bi bi-pencil"></i></button>
                @elseif ($row['stage'] === 'advance_paid')
                  <button type="button" class="btn btn-sm btn-light" @click="openAdvance(@js($row['payload']))"><i class="bi bi-pencil"></i> Edit Advance</button>
                @else
                  <a class="btn btn-sm btn-light" href="{{ route('bookings.show', $booking) }}">View</a>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted py-5">{{ $search !== '' || $tab !== 'all' ? 'No partner bookings match this filter.' : 'No Full Package bookings yet. They appear here automatically when you create one.' }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Mobile cards --}}
    <div class="d-md-none pb-cards">
      @forelse ($rows as $row)
        @php $entry = $row['entry']; $booking = $row['booking']; @endphp
        <div class="pb-card {{ $row['stage'] === 'pending_final' ? 'pb-row-alert' : '' }}">
          <div class="pb-card-top">
            <div>
              <a href="{{ route('bookings.show', $booking) }}"><strong>#{{ $booking->id }} · {{ $booking->customer_name }}</strong></a>
              <small class="d-block text-muted">{{ $booking->stay_hotel_name ?: $booking->safari_tour_name }}</small>
            </div>
            <span class="deal-badge {{ $row['meta']['class'] }} {{ $row['stage'] === 'pending_final' ? 'pb-flag' : '' }}">{{ $row['meta']['label'] }}</span>
          </div>
          <div class="pb-card-partner">{{ $entry->partner_name ?: 'Partner not assigned' }}</div>
          <div class="pb-card-nums">
            <span>Agreed <strong>{{ $entry->agreed_cost !== null ? number_format($entry->agreed_cost, 2) : '—' }}</strong></span>
            <span>Advance <strong>{{ $entry->status === 'pending_advance' ? '—' : number_format($entry->advance_paid, 2) }}</strong></span>
            <span>Remaining <strong>@if ($entry->status === 'pending_advance')—@elseif ($entry->status === 'settled')Paid @else{{ number_format($entry->remaining_cost, 2) }}@endif</strong></span>
          </div>
          @if ($row['stage'] === 'pending_advance')
            <button type="button" class="btn btn-primary w-100" @click="openAdvance(@js($row['payload']))"><i class="bi bi-cash-coin"></i> Record Advance</button>
          @elseif ($row['stage'] === 'pending_final')
            <div class="d-flex gap-2"><button type="button" class="btn btn-danger flex-grow-1" @click="openSettle(@js($row['payload']))"><i class="bi bi-check2-circle"></i> Settle Remaining</button><button type="button" class="btn btn-light" @click="openAdvance(@js($row['payload']))" aria-label="Edit advance details"><i class="bi bi-pencil"></i></button></div>
          @elseif ($row['stage'] === 'advance_paid')
            <button type="button" class="btn btn-light w-100" @click="openAdvance(@js($row['payload']))"><i class="bi bi-pencil"></i> Edit Advance</button>
          @endif
        </div>
      @empty
        <p class="text-center text-muted py-4 mb-0">{{ $search !== '' || $tab !== 'all' ? 'No partner bookings match this filter.' : 'No Full Package bookings yet.' }}</p>
      @endforelse
    </div>

    @if ($entries->hasPages())
      <div class="mt-3">{{ $entries->links() }}</div>
    @endif
  </div>

  {{-- ============ Record / edit advance ============ --}}
  <div class="modal fade" id="advanceModal" tabindex="-1" aria-labelledby="advanceModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down ex-dialog">
      <div class="modal-content dash-modal">
        <form method="POST" :action="form.advance_url" class="ex-form" data-live-form>
          @csrf
          <input type="hidden" name="entry_id" :value="form.id">
          <input type="hidden" name="_modal" value="advance">
          <div class="modal-header">
            <div><span class="eyebrow" x-text="'Booking #' + form.booking_id + ' · ' + form.guest"></span><h2 class="modal-title" id="advanceModalTitle">Partner Resort Advance</h2></div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label" for="pbPartner">Partner / Resort / Cabana Name <span class="text-danger">*</span></label>
                <input id="pbPartner" class="form-control" name="partner_name" x-model="form.partner_name" placeholder="e.g. Cinnamon Wild, Yala" maxlength="255" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="pbAgreed">Total Agreed Cost to Partner ({{ $currency }}) <span class="text-danger">*</span></label>
                <input id="pbAgreed" class="form-control" type="number" name="agreed_cost" x-model.number="form.agreed_cost" min="0.01" step="0.01" inputmode="decimal" placeholder="0.00" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="pbAdvance">Advance Amount Paid to Partner ({{ $currency }}) <span class="text-danger">*</span></label>
                <input id="pbAdvance" class="form-control" type="number" name="advance_amount" x-model.number="form.advance" min="0" step="0.01" inputmode="decimal" placeholder="0.00" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="pbAdvDate">Date paid</label>
                <input id="pbAdvDate" class="form-control" type="date" name="advance_date" x-model="form.advance_date" :max="today">
              </div>
              <div class="col-md-6">
                <label class="form-label">Remaining after advance</label>
                <input class="form-control" type="text" :value="money(Math.max(0, (Number(form.agreed_cost) || 0) - (Number(form.advance) || 0)))" disabled>
              </div>
            </div>
            <p class="pb-note" x-show="(Number(form.advance) || 0) > (Number(form.agreed_cost) || 0)">The advance cannot be more than the agreed cost.</p>
            <p class="text-muted small mt-3 mb-0"><i class="bi bi-info-circle"></i> Saving marks this booking <strong>Advance Paid / Confirmed</strong> and records the advance as a direct expense on booking #<span x-text="form.booking_id"></span>.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="(Number(form.advance) || 0) > (Number(form.agreed_cost) || 0)"><i class="bi bi-check2-circle"></i> Save Advance</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- ============ Settle the remaining balance ============ --}}
  <div class="modal fade" id="settleModal" tabindex="-1" aria-labelledby="settleModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down ex-dialog">
      <div class="modal-content dash-modal">
        <form method="POST" :action="form.settle_url" class="ex-form" data-live-form>
          @csrf
          <input type="hidden" name="entry_id" :value="form.id">
          <input type="hidden" name="_modal" value="settle">
          <div class="modal-header">
            <div><span class="eyebrow" x-text="'Booking #' + form.booking_id + ' · ' + form.guest"></span><h2 class="modal-title" id="settleModalTitle">Settle Final Payment</h2></div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="pb-sum">
              <div class="pb-sum-row"><span>Partner</span><strong x-text="form.partner_name"></strong></div>
              <div class="pb-sum-row"><span>Total agreed cost</span><strong x-text="money(form.agreed_cost)"></strong></div>
              <div class="pb-sum-row"><span>Advance already paid</span><strong x-text="'− ' + money(form.advance)"></strong></div>
              <div class="pb-sum-due"><span>Remaining to pay</span><strong x-text="money(form.remaining)"></strong></div>
            </div>
            <div class="row g-3 mt-1">
              <div class="col-md-6">
                <label class="form-label" for="pbFinal">Final payment ({{ $currency }}) <span class="text-danger">*</span></label>
                <input id="pbFinal" class="form-control" type="number" name="final_amount" x-model.number="form.final_amount" min="0" step="0.01" inputmode="decimal" required>
                <div class="form-text">Defaults to the full remaining balance.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="pbSetDate">Date paid</label>
                <input id="pbSetDate" class="form-control" type="date" name="settled_date" x-model="form.settled_date" :max="today">
              </div>
            </div>
            <p class="text-muted small mt-3 mb-0"><i class="bi bi-info-circle"></i> This marks the partner booking <strong>Fully Settled</strong> and adds the payment to booking #<span x-text="form.booking_id"></span>'s direct expenses, so its net profit is accurate.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger"><i class="bi bi-check2-circle"></i> <span x-text="'Pay ' + money(form.final_amount) + ' & Settle'"></span></button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    function partnerManager(config) {
        const blank = () => ({ id: null, booking_id: '', guest: '', partner_name: '', agreed_cost: '', advance: '', advance_date: config.today, remaining: 0, final_amount: 0, settled_date: config.today, advance_url: '#', settle_url: '#' });
        return {
            today: config.today,
            form: blank(),
            init() {
                if (config.reopen && config.old) {
                    this.form = { ...blank(), ...config.old };
                    this.show(config.reopen === 'settle' ? 'settleModal' : 'advanceModal');
                }
            },
            money(value) {
                return config.currency + ' ' + Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            show(id) {
                this.$nextTick(() => bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).show());
            },
            openAdvance(entry) {
                this.form = { ...blank(), ...entry, advance_date: entry.advance_date || config.today, advance: entry.agreed_cost === '' ? '' : entry.advance };
                this.show('advanceModal');
            },
            openSettle(entry) {
                this.form = { ...blank(), ...entry, settled_date: config.today };
                this.show('settleModal');
            },
        };
    }
</script>
@endpush
