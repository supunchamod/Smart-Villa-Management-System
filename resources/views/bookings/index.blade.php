@extends('layouts.app')

@section('title', 'Bookings | ' . ($globalSettings->villa_name ?? config('app.name')))

@push('styles')
  <link href="{{ asset('assets/css/bookings-index.css') }}" rel="stylesheet">
@endpush

@section('content')
        @php
          $today = today();
          $baseQuery = collect(request()->except(['tab', 'page']))->filter(fn ($value) => $value !== null && $value !== '')->all();
          $pillUrl = fn (string $tab) => route('bookings.index', $tab === 'all' ? $baseQuery : array_merge($baseQuery, ['tab' => $tab]));
          $typeBaseQuery = collect(request()->except(['type', 'page']))->filter(fn ($value) => $value !== null && $value !== '')->all();
          $typePillUrl = fn (string $type) => route('bookings.index', $type === 'all' ? $typeBaseQuery : array_merge($typeBaseQuery, ['type' => $type]));
          $hasActiveFilters = $search !== '' || $selectedRoomType !== '' || $activeRange !== null || $activeType !== 'all';
        @endphp

        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Bookings</h1></li>
            </ol>
          </nav>
          <div class="page-actions"><a class="btn btn-primary" href="{{ route('bookings.create') }}"><i class="bi bi-plus-lg"></i> Add Booking</a></div>
        </div>

        @if (session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($pendingBookingsCount > 0)
          <div class="bk-pending-banner">
            <span><i class="bi bi-bell me-1"></i> {{ $pendingBookingsCount }} pending {{ \Illuminate\Support\Str::plural('enquiry', $pendingBookingsCount) }} awaiting review.</span>
            <a class="btn btn-sm btn-warning" href="{{ $pillUrl('pending') }}">Review Now</a>
          </div>
        @endif

        {{-- Daily Operations Grid --}}
        <div class="bk-kpi-grid">
          <div class="bk-kpi-card">
            <div class="bk-kpi-head">
              <div>
                <div class="bk-kpi-label"><i class="bi bi-box-arrow-in-right"></i> Today's Check-ins</div>
                <div class="bk-kpi-count">{{ $todayCheckInsCount }}</div>
              </div>
              <span class="bk-kpi-icon blue"><i class="bi bi-box-arrow-in-right"></i></span>
            </div>
            <div class="bk-kpi-list">
              @forelse ($todayArrivals->take(3) as $arrival)
                <div class="bk-kpi-list-row"><strong>{{ $arrival->customer_name }}</strong><span>{{ $arrival->room_label }}</span></div>
              @empty
                <p class="bk-kpi-empty mb-0">No arrivals scheduled today.</p>
              @endforelse
              @if ($todayArrivals->count() > 3)
                <a class="bk-kpi-more" href="{{ route('bookings.index', ['range' => 'today', 'tab' => 'active']) }}">+{{ $todayArrivals->count() - 3 }} more</a>
              @endif
            </div>
          </div>

          <div class="bk-kpi-card">
            <div class="bk-kpi-head">
              <div>
                <div class="bk-kpi-label"><i class="bi bi-box-arrow-right"></i> Today's Check-outs</div>
                <div class="bk-kpi-count">{{ $todayCheckOutsCount }}</div>
              </div>
              <span class="bk-kpi-icon teal"><i class="bi bi-box-arrow-right"></i></span>
            </div>
            @php $unsettledDepartures = $todayDepartures->filter(fn ($booking) => $booking->remaining_balance > 0); @endphp
            @if ($unsettledDepartures->isNotEmpty())
              <div class="bk-kpi-alert warn"><i class="bi bi-exclamation-triangle"></i> {{ $unsettledDepartures->count() }} with balance due</div>
              <div class="bk-kpi-list">
                @foreach ($unsettledDepartures->take(2) as $departure)
                  <div class="bk-kpi-list-row"><strong>{{ $departure->customer_name }}</strong><span>{{ $globalSettings->currency }} {{ number_format($departure->remaining_balance, 2) }}</span></div>
                @endforeach
              </div>
            @elseif ($todayCheckOutsCount > 0)
              <div class="bk-kpi-alert ok"><i class="bi bi-check-circle"></i> All departures settled</div>
            @else
              <p class="bk-kpi-empty mb-0">No departures scheduled today.</p>
            @endif
          </div>

          <div class="bk-kpi-card">
            <div class="bk-kpi-head">
              <div>
                <div class="bk-kpi-label"><i class="bi bi-calendar-plus"></i> Tomorrow's Check-ins</div>
                <div class="bk-kpi-count">{{ $tomorrowCheckInsCount }}</div>
              </div>
              <span class="bk-kpi-icon violet"><i class="bi bi-calendar-plus"></i></span>
            </div>
            <p class="bk-kpi-empty mb-0">Upcoming arrivals for tomorrow.</p>
          </div>

          <div class="bk-kpi-card">
            <div class="bk-kpi-head">
              <div>
                <div class="bk-kpi-label"><i class="bi bi-calendar-minus"></i> Tomorrow's Check-outs</div>
                <div class="bk-kpi-count">{{ $tomorrowCheckOutsCount }}</div>
              </div>
              <span class="bk-kpi-icon amber"><i class="bi bi-calendar-minus"></i></span>
            </div>
            <p class="bk-kpi-empty mb-0">Scheduled room clearances tomorrow.</p>
          </div>
        </div>

        <div class="row g-4 bk-row">
          <div class="col-12">
            <div class="panel bk-panel" x-data="bookingsPanel()">
              <div class="panel-head"><div><h2>All Bookings</h2><p>Reservations across all rooms and cabanas</p></div></div>

              {{-- Search & filters --}}
              <form method="GET" action="{{ route('bookings.index') }}" class="bk-filter-bar">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <input type="hidden" name="type" value="{{ $activeType }}">
                <div class="bk-search-wrap">
                  <i class="bi bi-search"></i>
                  <input type="search" name="search" value="{{ $search }}" placeholder="Search guest name, phone, or booking ID...">
                </div>
                <div class="bk-filter-selects">
                  <select name="room_type" onchange="this.form.submit()" aria-label="Filter by cabana / room type">
                    <option value="">All Cabana Types</option>
                    @foreach ($roomTypes as $type)
                      <option value="{{ $type }}" @selected($selectedRoomType === $type)>{{ $type }}</option>
                    @endforeach
                  </select>
                  <select name="range" onchange="this.form.submit()" aria-label="Filter by check-in date range">
                    <option value="">Any Check-in Date</option>
                    <option value="today" @selected($activeRange === 'today')>Checking In Today</option>
                    <option value="week" @selected($activeRange === 'week')>Next 7 Days</option>
                    <option value="month" @selected($activeRange === 'month')>This Month</option>
                  </select>
                  <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Search</button>
                  @if ($hasActiveFilters)
                    <a class="bk-clear-filters" href="{{ $pillUrl($activeTab) }}"><i class="bi bi-x-circle me-1"></i>Clear filters</a>
                  @endif
                </div>
              </form>

              {{-- Filter pills --}}
              <div class="bk-pills" role="tablist" aria-label="Filter bookings by status">
                <a role="tab" href="{{ $pillUrl('all') }}" class="bk-pill {{ $activeTab === 'all' ? 'active' : '' }}" aria-selected="{{ $activeTab === 'all' ? 'true' : 'false' }}">All Bookings</a>
                <a role="tab" href="{{ $pillUrl('active') }}" class="bk-pill {{ $activeTab === 'active' ? 'active' : '' }}" aria-selected="{{ $activeTab === 'active' ? 'true' : 'false' }}">Active / In-House</a>
                <a role="tab" href="{{ $pillUrl('balance_due') }}" class="bk-pill {{ $activeTab === 'balance_due' ? 'active' : '' }}" aria-selected="{{ $activeTab === 'balance_due' ? 'true' : 'false' }}">
                  Balance Due
                  @if ($balanceDueCount > 0)
                    <span class="tab-count-badge tab-count-badge-red">{{ $balanceDueCount }}</span>
                  @endif
                </a>
                <a role="tab" href="{{ $pillUrl('history') }}" class="bk-pill {{ $activeTab === 'history' ? 'active' : '' }}" aria-selected="{{ $activeTab === 'history' ? 'true' : 'false' }}">Completed / History</a>
              </div>

              @if ($globalSettings->is_safari_enabled)
                <div class="bk-pills" role="tablist" aria-label="Filter bookings by type">
                  <a role="tab" href="{{ $typePillUrl('all') }}" class="bk-pill {{ $activeType === 'all' ? 'active' : '' }}" aria-selected="{{ $activeType === 'all' ? 'true' : 'false' }}">All Types</a>
                  <a role="tab" href="{{ $typePillUrl('accommodation_only') }}" class="bk-pill {{ $activeType === 'accommodation_only' ? 'active' : '' }}" aria-selected="{{ $activeType === 'accommodation_only' ? 'true' : 'false' }}"><i class="bi bi-house-door"></i> Accommodations Only</a>
                  <a role="tab" href="{{ $typePillUrl('safari_only') }}" class="bk-pill {{ $activeType === 'safari_only' ? 'active' : '' }}" aria-selected="{{ $activeType === 'safari_only' ? 'true' : 'false' }}"><i class="bi bi-truck-front"></i> Safari Only</a>
                  <a role="tab" href="{{ $typePillUrl('full_package') }}" class="bk-pill {{ $activeType === 'full_package' ? 'active' : '' }}" aria-selected="{{ $activeType === 'full_package' ? 'true' : 'false' }}"><i class="bi bi-layers"></i> Full Packages</a>
                </div>
              @endif

              <div class="d-none d-md-block">
                <div class="table-responsive">
                  <table class="table align-middle dash-table">
                    <thead>
                      <tr>
                        <th>Guest</th>
                        <th>Cabana</th>
                        <th>Stay</th>
                        <th>Financial</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($bookings as $booking)
                        @php
                          $nights = $booking->check_in->diffInDays($booking->check_out);
                          $isInHouse = $booking->booking_type !== 'safari_only' && $booking->status === 'confirmed' && $booking->check_in->lte($today) && $booking->check_out->gte($today);
                          $statusMeta = match (true) {
                              $booking->status === 'pending' => ['label' => 'Pending', 'badge' => 'pending'],
                              $booking->status === 'confirmed' && $isInHouse => ['label' => 'Checked In', 'badge' => 'progress'],
                              $booking->status === 'confirmed' => ['label' => 'Confirmed', 'badge' => 'new'],
                              $booking->status === 'checked_out' => ['label' => 'Checked Out', 'badge' => 'won'],
                              $booking->status === 'cancelled' => ['label' => 'Cancelled', 'badge' => 'stuck'],
                              default => ['label' => ucfirst($booking->status), 'badge' => 'new'],
                          };
                          $financialBadge = match (true) {
                              in_array($booking->status, ['cancelled', 'pending'], true) => null,
                              $booking->remaining_balance <= 0 => ['label' => 'Fully Paid', 'class' => 'won'],
                              default => ['label' => 'Balance Due: ' . $globalSettings->currency . ' ' . number_format($booking->remaining_balance, 2), 'class' => 'pending'],
                          };
                          $canSendWhatsApp = $booking->customer_phone && in_array($booking->status, ['confirmed', 'checked_out'], true);
                          $typeBadgeClass = ['accommodation_only' => 'type-badge-cabana', 'safari_only' => 'type-badge-safari', 'full_package' => 'type-badge-combo'][$booking->booking_type] ?? 'type-badge-cabana';
                          $typeIcon = ['accommodation_only' => 'house-door', 'safari_only' => 'truck-front', 'full_package' => 'layers'][$booking->booking_type] ?? 'house-door';
                        @endphp
                        <tr>
                          <td>
                            <div class="bk-guest-line">
                              <span class="mdash-avatar sm">{{ $booking->customer_initials }}</span>
                              <div>
                                <strong>{{ $booking->customer_name }}</strong>
                                <small>#{{ $booking->id }}@if($booking->customer_phone) &middot; {{ $booking->customer_phone }}@endif</small>
                              </div>
                            </div>
                          </td>
                          <td>
                            @if ($globalSettings->is_safari_enabled)
                              <span class="type-badge {{ $typeBadgeClass }}"><i class="bi bi-{{ $typeIcon }}"></i> {{ $booking->booking_type_label }}</span><br>
                            @endif
                            @if ($booking->room)
                              {{ $booking->room->name_or_number }}<br>
                              <small class="text-muted">{{ $booking->room->type }}</small><br>
                              <span class="board-badge {{ $booking->board_type_badge_class }}" data-bs-toggle="tooltip" title="{{ $booking->board_type_label }}">{{ $booking->board_type_badge_label }}</span>
                            @else
                              <small class="text-muted">{{ $booking->safari_tour_name }}</small>
                            @endif
                          </td>
                          <td>
                            @if ($booking->booking_type === 'safari_only')
                              <span class="bk-stay-dates">Safari: {{ optional($booking->safari_date)->format('d M Y') }}</span>
                            @else
                              <span class="bk-stay-dates">{{ $booking->check_in->format('d M') }} - {{ $booking->check_out->format('d M Y') }}</span>
                              <span class="bk-stay-nights">{{ $nights }} {{ \Illuminate\Support\Str::plural('Night', $nights) }}</span>
                            @endif
                          </td>
                          <td class="bk-financial">
                            <strong>{{ $globalSettings->currency }} {{ number_format($booking->total_amount, 2) }}</strong>
                            @if ($financialBadge)
                              <span class="deal-badge {{ $financialBadge['class'] }}">{{ $financialBadge['label'] }}</span>
                            @endif
                          </td>
                          <td><span class="deal-badge {{ $statusMeta['badge'] }}">{{ $statusMeta['label'] }}</span></td>
                          <td>
                            <div class="bk-row-actions">
                              @if ($booking->status === 'pending')
                                <form method="POST" action="{{ route('bookings.confirm', $booking) }}">
                                  @csrf
                                  <button class="btn btn-sm btn-success" type="submit"><i class="bi bi-check-lg"></i> Accept</button>
                                </form>
                                <form method="POST" action="{{ route('bookings.decline', $booking) }}" onsubmit="return confirm('Decline this booking request?');">
                                  @csrf
                                  <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-x-lg"></i> Decline</button>
                                </form>
                              @elseif ($booking->status === 'confirmed')
                                <button type="button" class="btn btn-sm {{ $booking->remaining_balance > 0 ? 'btn-warning' : 'btn-success' }}" data-bs-toggle="modal" data-bs-target="#checkoutModal{{ $booking->id }}">
                                  <i class="bi bi-box-arrow-right"></i> {{ $booking->remaining_balance > 0 ? 'Collect & Checkout' : 'Checkout' }}
                                </button>
                              @endif

                              @if ($booking->status !== 'cancelled' && $booking->status !== 'pending')
                                <a class="bk-icon-btn" href="{{ route($booking->status === 'checked_out' ? 'bookings.invoice.final' : 'bookings.invoice.confirmation', $booking) }}" target="_blank" rel="noopener" data-bs-toggle="tooltip" title="Invoice PDF" aria-label="Download invoice"><i class="bi bi-file-earmark-pdf"></i></a>
                              @endif

                              @if ($canSendWhatsApp)
                                <button type="button" class="bk-icon-btn whatsapp" data-bs-toggle="tooltip" title="WhatsApp Message" aria-label="Resend via WhatsApp" @click="sendWhatsApp({{ $booking->id }}, '{{ route('bookings.send-whatsapp', $booking) }}')" :disabled="sendingWhatsApp[{{ $booking->id }}]"><i class="bi bi-whatsapp"></i></button>
                              @endif

                              <button type="button" class="bk-icon-btn" data-bs-toggle="modal" data-bs-target="#quickViewModal{{ $booking->id }}" title="View Details" aria-label="View details"><i class="bi bi-eye"></i></button>

                              <div class="dropdown">
                                <button class="bk-icon-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions"><i class="bi bi-three-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                  <li><a class="dropdown-item" href="{{ route('bookings.edit', $booking) }}"><i class="bi bi-pencil me-2"></i>Edit Booking</a></li>
                                  <li><hr class="dropdown-divider"></li>
                                  <li>
                                    <form method="POST" action="{{ route('bookings.destroy', $booking) }}" onsubmit="return confirm('Delete this booking?');">
                                      @csrf
                                      @method('DELETE')
                                      <button class="dropdown-item text-danger" type="submit"><i class="bi bi-trash me-2"></i>Delete Booking</button>
                                    </form>
                                  </li>
                                </ul>
                              </div>
                            </div>
                          </td>
                        </tr>
                      @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No bookings match these filters.</td></tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="d-block d-md-none">
                <div class="mdash-booking-list">
                  @forelse ($bookings as $booking)
                    @php
                      $nights = $booking->check_in->diffInDays($booking->check_out);
                      $isInHouse = $booking->booking_type !== 'safari_only' && $booking->status === 'confirmed' && $booking->check_in->lte($today) && $booking->check_out->gte($today);
                      $statusMeta = match (true) {
                          $booking->status === 'pending' => ['label' => 'Pending', 'badge' => 'pending'],
                          $booking->status === 'confirmed' && $isInHouse => ['label' => 'Checked In', 'badge' => 'progress'],
                          $booking->status === 'confirmed' => ['label' => 'Confirmed', 'badge' => 'new'],
                          $booking->status === 'checked_out' => ['label' => 'Checked Out', 'badge' => 'won'],
                          $booking->status === 'cancelled' => ['label' => 'Cancelled', 'badge' => 'stuck'],
                          default => ['label' => ucfirst($booking->status), 'badge' => 'new'],
                      };
                      $financialBadge = match (true) {
                          in_array($booking->status, ['cancelled', 'pending'], true) => null,
                          $booking->remaining_balance <= 0 => ['label' => 'Fully Paid', 'class' => 'won'],
                          default => ['label' => 'Balance Due: ' . $globalSettings->currency . ' ' . number_format($booking->remaining_balance, 2), 'class' => 'pending'],
                      };
                      $canSendWhatsApp = $booking->customer_phone && in_array($booking->status, ['confirmed', 'checked_out'], true);
                      $typeBadgeClass = ['accommodation_only' => 'type-badge-cabana', 'safari_only' => 'type-badge-safari', 'full_package' => 'type-badge-combo'][$booking->booking_type] ?? 'type-badge-cabana';
                      $typeIcon = ['accommodation_only' => 'house-door', 'safari_only' => 'truck-front', 'full_package' => 'layers'][$booking->booking_type] ?? 'house-door';
                    @endphp
                    <div class="mdash-booking-card stacked">
                      <div class="mdash-card-top">
                        <span class="mdash-avatar sm">{{ $booking->customer_initials }}</span>
                        <div class="mdash-booking-info">
                          <strong>{{ $booking->customer_name }}</strong>
                          <small>{{ $booking->room_label }} @if ($booking->booking_type !== 'safari_only') &middot; {{ $booking->check_in->format('d M') }} - {{ $booking->check_out->format('d M Y') }} ({{ $nights }} {{ \Illuminate\Support\Str::plural('Night', $nights) }}) @endif</small>
                          @if ($booking->room)
                            <span class="board-badge {{ $booking->board_type_badge_class }}">{{ $booking->board_type_badge_label }}</span>
                          @endif
                        </div>
                        <span class="deal-badge {{ $statusMeta['badge'] }}">{{ $statusMeta['label'] }}</span>
                      </div>
                      @if ($globalSettings->is_safari_enabled)
                        <div class="mdash-card-top">
                          <span class="type-badge {{ $typeBadgeClass }}"><i class="bi bi-{{ $typeIcon }}"></i> {{ $booking->booking_type_label }}</span>
                        </div>
                      @endif

                      <div class="mdash-card-meta">
                        <span>Total <strong>{{ $globalSettings->currency }} {{ number_format($booking->total_amount, 2) }}</strong></span>
                        @if ($financialBadge)
                          <span class="deal-badge {{ $financialBadge['class'] }}">{{ $financialBadge['label'] }}</span>
                        @endif
                      </div>

                      @if ($booking->status === 'pending')
                        <div class="booking-request-actions">
                          <form method="POST" action="{{ route('bookings.confirm', $booking) }}">
                            @csrf
                            <button type="submit" class="mdash-action-btn mdash-action-btn-success"><i class="bi bi-check-lg"></i> Accept &amp; Confirm</button>
                          </form>
                          <form method="POST" action="{{ route('bookings.decline', $booking) }}" onsubmit="return confirm('Decline this booking request?');">
                            @csrf
                            <button type="submit" class="mdash-action-btn mdash-action-btn-outline-danger"><i class="bi bi-x-lg"></i> Decline</button>
                          </form>
                        </div>
                      @elseif ($booking->status === 'confirmed')
                        <button
                          type="button"
                          class="mdash-action-btn {{ $booking->remaining_balance > 0 ? 'mdash-action-btn-amber' : 'mdash-action-btn-success' }}"
                          data-bs-toggle="modal"
                          data-bs-target="#checkoutModal{{ $booking->id }}"
                        ><i class="bi bi-box-arrow-right"></i> {{ $booking->remaining_balance > 0 ? 'Collect Balance & Checkout' : 'Checkout' }}</button>
                      @endif

                      {{-- One compact row of icon actions: view, edit, invoice PDF, WhatsApp, and delete (pushed right) --}}
                      <div class="bk-m-actions">
                        <button class="mdash-icon-btn" type="button" data-bs-toggle="modal" data-bs-target="#quickViewModal{{ $booking->id }}" title="View details" aria-label="View details"><i class="bi bi-eye"></i></button>
                        <a class="mdash-icon-btn" href="{{ route('bookings.edit', $booking) }}" title="Edit booking" aria-label="Edit booking"><i class="bi bi-pencil"></i></a>
                        @if ($booking->status === 'confirmed')
                          <a class="mdash-icon-btn primary" href="{{ route('bookings.invoice.confirmation', $booking) }}" target="_blank" rel="noopener" title="Confirmation invoice (PDF)" aria-label="Download confirmation invoice PDF"><i class="bi bi-file-earmark-arrow-down"></i></a>
                        @elseif ($booking->status === 'checked_out')
                          <a class="mdash-icon-btn primary" href="{{ route('bookings.invoice.final', $booking) }}" target="_blank" rel="noopener" title="Final invoice (PDF)" aria-label="Download final invoice PDF"><i class="bi bi-file-earmark-check"></i></a>
                        @endif
                        @if ($canSendWhatsApp)
                          <button type="button" class="mdash-icon-btn bk-m-wa" title="Resend via WhatsApp" aria-label="Resend via WhatsApp" @click="sendWhatsApp({{ $booking->id }}, '{{ route('bookings.send-whatsapp', $booking) }}')" :disabled="sendingWhatsApp[{{ $booking->id }}]"><i class="bi" :class="sendingWhatsApp[{{ $booking->id }}] ? 'bi-hourglass-split' : 'bi-whatsapp'"></i></button>
                        @endif
                        <form class="bk-m-delete" method="POST" action="{{ route('bookings.destroy', $booking) }}" onsubmit="return confirm('Delete this booking?');">
                          @csrf
                          @method('DELETE')
                          <button class="mdash-icon-btn danger" type="submit" title="Delete booking" aria-label="Delete booking"><i class="bi bi-trash"></i></button>
                        </form>
                      </div>
                    </div>
                  @empty
                    <p class="mdash-empty">No bookings match these filters.</p>
                  @endforelse
                </div>
              </div>

              @if ($bookings->hasPages())
                <div class="mt-3">{{ $bookings->links() }}</div>
              @endif
            </div>
          </div>
        </div>

        {{-- Checkout & Quick View modals - one per booking on this page, shared by
             both the desktop table and mobile cards above. --}}
        @foreach ($bookings as $booking)
          @if ($booking->status === 'confirmed')
            @include('bookings._checkout-modal', ['booking' => $booking, 'modalId' => 'checkoutModal'.$booking->id])
          @endif

          @include('bookings._details-modal', ['booking' => $booking])
        @endforeach
@endsection

@push('scripts')
<script>
    function bookingsPanel() {
        return {
            sendingWhatsApp: {},
            async sendWhatsApp(bookingId, url) {
                if (this.sendingWhatsApp[bookingId]) return;
                this.sendingWhatsApp[bookingId] = true;
                try {
                    const response = await fetch(url, {
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
                    this.sendingWhatsApp[bookingId] = false;
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
