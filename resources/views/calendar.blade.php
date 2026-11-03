@extends('layouts.app')

@section('title', 'Calendar | ' . ($globalSettings->villa_name ?? config('app.name')))

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/villa-calendar.css') }}">
@endpush

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Calendar</h1></li>
            </ol>
          </nav>
          <div class="page-actions"><a class="btn btn-primary" href="{{ route('bookings.create') }}"><i class="bi bi-plus-lg"></i> Add Booking</a></div>
        </div>
        <div class="calendar-shell vc">
  <div class="calendar-toolbar panel">
    <div>
      <span class="vc-accent-bar" aria-hidden="true"></span>
      <span class="vc-badge">Our Schedule</span>
      <h2 class="vc-title">Villa Bookings &amp; Events Calendar</h2>
      <p>Use month, week, day, and list views with working previous, next, and today controls.</p>
    </div>
    <div class="calendar-legend"><span><i class="event-amber"></i> Pending</span><span><i class="event-blue"></i> Confirmed</span><span><i class="event-green"></i> Checked out</span><span><i class="event-red"></i> Cancelled</span></div>
  </div>
 <div class="row g-4 mt-1">
    <!-- Calendar Component (Left - 9 Columns) -->
    <div class="col-xl-9">
        <div class="panel calendar-library-panel h-100">
            {{-- FullCalendar mounts here and replaces the placeholder; events come from bookings.calendar --}}
            <div id="villaCalendar" data-calendar-mount data-events-url="{{ route('bookings.calendar') }}">
                <p class="vc-empty">Loading calendar…</p>
            </div>
        </div>
    </div>

    {{-- Upcoming bookings: real confirmed bookings, soonest check-in first --}}
    <div class="col-xl-3">
        <div class="panel event-agenda vc-agenda-panel h-100">
            <div class="vc-agenda-head">
                <span class="vc-agenda-eyebrow">Villa Schedules</span>
                <h2>Upcoming Bookings</h2>
            </div>

            <div class="vc-agenda-list">
                @forelse ($upcomingBookings as $booking)
                    @php
                        $days = today()->diffInDays($booking->check_in->copy()->startOfDay(), false);
                        $when = $days <= 0 ? 'Today' : ($days === 1 ? 'Tomorrow' : 'In '.(int) $days.' days');
                        $isStay = $booking->booking_type !== 'safari_only';
                    @endphp
                    <button type="button" class="vc-agenda-card" title="View booking details"
                            x-data
                            @click="$dispatch('calendar-event-selected', @js($upcomingPayloads[$booking->id]))">
                        <span class="vc-agenda-date">
                            <span class="vc-agenda-date-num">{{ $booking->check_in->format('d') }}</span>
                            <span class="vc-agenda-date-month">{{ $booking->check_in->format('M') }}</span>
                        </span>
                        <span class="vc-agenda-content">
                            <strong class="vc-agenda-title">{{ $booking->customer_name }}</strong>
                            <small class="vc-agenda-meta">{{ $booking->room_label }}</small>
                            <small class="vc-agenda-meta">{{ $when }}@if ($isStay) · {{ $booking->nights }} {{ \Illuminate\Support\Str::plural('night', $booking->nights) }}@endif</small>
                        </span>
                        <span class="vc-agenda-revenue">{{ $globalSettings->currency }} {{ number_format($booking->grand_total, 0) }}</span>
                    </button>
                @empty
                    <p class="vc-empty">No upcoming confirmed bookings.</p>
                @endforelse
            </div>

            <a href="{{ route('bookings.index') }}" class="vc-agenda-footer-link">View All Bookings <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
</div>
</div>

{{-- ============ Booking popup (opened by clicking a calendar event or an upcoming-booking card) ============ --}}
<div class="modal fade" id="bookingDetailModal" tabindex="-1" aria-labelledby="bookingDetailTitle" aria-hidden="true"
     x-data="bookingCalendarModal(@js([
         'villa' => [
             'name' => $globalSettings->villa_name,
             'address' => $globalSettings->address,
             'phone' => $globalSettings->phone_number,
             'email' => $globalSettings->email,
             'logo' => $globalSettings->logo_url,
         ],
     ]))"
     x-init="window.addEventListener('calendar-event-selected', (e) => open(e.detail))">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down vb-dialog">
    <div class="modal-content dash-modal">
      <template x-if="b">
        <div class="vcm-wrap">
          <div class="modal-header">
            <div class="vcm-title">
              <span class="bd-avatar" aria-hidden="true" x-text="b.initials"></span>
              <div>
                <span class="eyebrow" x-text="b.reference"></span>
                <h2 class="modal-title" id="bookingDetailTitle" x-text="b.customer_name"></h2>
                <span class="deal-badge" :class="badgeClass" x-text="b.status_label"></span>
              </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body vcm-body">
            <div class="bd-group vcm-first">
              <h3>Guest</h3>
              <dl class="bd-fields">
                <div class="bd-field"><dt><i class="bi bi-telephone"></i>Phone</dt>
                  <dd><a x-show="b.customer_phone" :href="'tel:' + (b.customer_phone || '').replace(/[^\d+]/g, '')" x-text="b.customer_phone"></a><span class="bd-empty" x-show="!b.customer_phone">Not provided</span></dd></div>
                <div class="bd-field"><dt><i class="bi bi-envelope"></i>Email</dt>
                  <dd><a x-show="b.customer_email" :href="'mailto:' + b.customer_email" x-text="b.customer_email"></a><span class="bd-empty" x-show="!b.customer_email">Not provided</span></dd></div>
              </dl>
            </div>

            <div class="bd-group">
              <h3 x-text="b.is_stay ? 'Stay' : 'Safari'"></h3>
              <dl class="bd-fields">
                <div class="bd-field bd-field-wide"><dt><i class="bi bi-door-open"></i><span x-text="b.is_stay ? 'Room / Cabana' : 'Tour'"></span></dt>
                  <dd><span x-text="b.room"></span> <span class="bd-sub" x-show="b.room_type" x-text="b.room_type"></span></dd></div>
                <template x-if="b.is_stay">
                  <div class="bd-field"><dt><i class="bi bi-box-arrow-in-right"></i>Check-in</dt><dd x-text="b.check_in"></dd></div>
                </template>
                <template x-if="b.is_stay">
                  <div class="bd-field"><dt><i class="bi bi-box-arrow-right"></i>Check-out</dt><dd x-text="b.check_out"></dd></div>
                </template>
                <template x-if="b.is_stay">
                  <div class="bd-field"><dt><i class="bi bi-moon-stars"></i>Duration</dt><dd x-text="b.nights + (b.nights === 1 ? ' night' : ' nights')"></dd></div>
                </template>
                <template x-if="b.board">
                  <div class="bd-field"><dt><i class="bi bi-cup-hot"></i>Board basis</dt><dd><span class="board-badge" :class="boardClass" x-text="b.board"></span></dd></div>
                </template>
                <template x-if="b.guests">
                  <div class="bd-field"><dt><i class="bi bi-people"></i>Guests</dt><dd x-text="b.guests"></dd></div>
                </template>
                <template x-if="b.safari">
                  <div class="bd-field"><dt><i class="bi bi-calendar-event"></i>Safari date</dt><dd x-text="b.safari.date"></dd></div>
                </template>
                <template x-if="b.safari && b.safari.vehicle">
                  <div class="bd-field bd-field-wide"><dt><i class="bi bi-truck"></i>Vehicle</dt><dd x-text="b.safari.vehicle"></dd></div>
                </template>
              </dl>
            </div>

            <div class="bd-group">
              <h3>Charges</h3>
              <ul class="bd-lines">
                <li class="bd-line">
                  <div class="bd-line-main"><strong x-text="b.room_charges_label"></strong><small x-show="b.room_charges_detail" x-text="b.room_charges_detail"></small></div>
                  <span class="bd-line-amount" x-text="b.room_charges"></span>
                </li>
                <template x-for="(extra, i) in b.extras" :key="i">
                  <li class="bd-line">
                    <div class="bd-line-main"><strong x-text="extra.name"></strong><small x-text="extra.detail"></small></div>
                    <span class="bd-line-amount" x-text="extra.amount"></span>
                  </li>
                </template>
                <li class="bd-line bd-line-empty" x-show="b.extras.length === 0">No extra charges</li>
              </ul>
            </div>

            <div class="bd-group vcm-last">
              <h3>Financial summary</h3>
              <div class="bd-stats">
                <div class="bd-stat bd-stat-total"><span>Total</span><strong x-text="b.total_amount"></strong></div>
                <div class="bd-stat"><span>Advance paid</span><strong x-text="b.advance_payment"></strong></div>
                <div class="bd-stat" :class="b.balance_due ? 'bd-stat-due' : 'bd-stat-clear'"><span>Balance due</span><strong x-text="b.balance"></strong></div>
              </div>
              <p class="bd-paid-note">
                <span class="bd-pay-pill" :class="payClass" x-text="b.payment_status"></span>
                <span x-show="b.payment_method" x-text="'Paid by ' + b.payment_method"></span>
              </p>
            </div>

            {{-- Off-screen bill: the markup html2canvas turns into the downloadable image --}}
            <div class="vcm-stage" aria-hidden="true"><div x-ref="stage"></div></div>
          </div>

          <div class="modal-footer vb-footer">
            <a class="btn btn-light" x-show="b.urls.bill" :href="b.urls.bill" target="_blank" rel="noopener"><i class="bi bi-receipt"></i> View Bill</a>
            <a class="btn btn-primary" x-show="b.urls.pdf" :href="b.urls.pdf"><i class="bi bi-file-earmark-pdf"></i> Download PDF Invoice</a>
            <button type="button" class="btn btn-outline-primary" x-show="b.urls.bill" @click="downloadImage()" :disabled="busy">
              <i class="bi bi-image"></i> <span x-text="busy ? 'Preparing…' : 'Download Bill Image'"></span>
            </button>
            <div class="vb-footer-links">
              <a :href="b.urls.show">Open booking</a>
              <a :href="b.urls.edit">Edit</a>
            </div>
            <p class="vb-error" x-show="error" x-text="error" x-cloak></p>
          </div>
        </div>
      </template>
    </div>
  </div>
</div>

@endsection

@push('scripts')
  <script src="{{ asset('assets/vendor/fullcalendar/index.global.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/html2canvas/html2canvas.min.js') }}"></script>
  <script>
    function bookingCalendarModal(config) {
        const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        return {
            b: null,
            busy: false,
            error: '',
            open(detail) {
                this.b = detail;
                this.error = '';
                this.$nextTick(() => bootstrap.Modal.getOrCreateInstance(this.$el).show());
            },
            get badgeClass() {
                return { pending: 'pending', confirmed: 'new', checked_out: 'won', cancelled: 'stuck' }[this.b?.status] || 'new';
            },
            get payClass() {
                const s = this.b?.payment_status;
                return s === 'Fully Paid' ? 'bd-pay-paid' : (s === 'Partially Paid' ? 'bd-pay-partial' : 'bd-pay-unpaid');
            },
            get boardClass() {
                const t = (this.b?.board || '').toLowerCase();
                return t.includes('full') ? 'board-badge-fb' : t.includes('half') ? 'board-badge-hb' : t.includes('breakfast') ? 'board-badge-bb' : 'board-badge-room-only';
            },
            // The same branded bill the bookings list popup produces (.vb-sheet), built from the event data.
            billHtml() {
                const b = this.b, v = config.villa;
                const stay = b.is_stay
                    ? `<div class="vb-muted">${esc(b.check_in_short)} → ${esc(b.check_out_short)} (${b.nights} ${b.nights === 1 ? 'night' : 'nights'})</div>`
                      + (b.board ? `<div class="vb-muted">Board: ${esc(b.board)}</div>` : '')
                      + (b.guests ? `<div class="vb-muted">${esc(b.guests)}</div>` : '')
                    : '';
                const safari = b.safari
                    ? `<div class="vb-strong">${esc(b.safari.tour)}</div><div class="vb-muted">${esc(b.safari.date || '')}${b.safari.vehicle ? ' · ' + esc(b.safari.vehicle) : ''}</div>`
                    : '';
                const item = (name, detail, amount) => `<div class="vb-item"><div class="vb-item-main"><strong>${esc(name)}</strong>${detail ? `<small>${esc(detail)}</small>` : ''}</div><span class="vb-item-amt">${esc(amount)}</span></div>`;
                const extras = b.extras.length
                    ? `<div class="vb-group">Extra charges</div>` + b.extras.map((x) => item(x.name, x.detail, x.amount)).join('')
                    : '';
                const payTone = !b.balance_due ? 'paid' : (b.payment_status === 'Partially Paid' ? 'partial' : 'unpaid');
                const contact = [v.address, [v.phone, v.email].filter(Boolean).join(' · ')].filter(Boolean).map(esc).join('<br>');
                return `<div class="vb-sheet vb-export">
                  <div class="vb-head">
                    <div class="vb-brand">${v.logo ? `<img class="vb-logo" src="${esc(v.logo)}" alt="">` : ''}<div><div class="vb-villa">${esc(v.name)}</div><div class="vb-contact">${contact}</div></div></div>
                    <div class="vb-doc"><div class="vb-doc-title">${esc(b.doc_title)}</div><div class="vb-doc-meta">Ref: ${esc(b.reference)}</div><div class="vb-doc-meta">Date: ${esc(b.doc_date)}</div></div>
                  </div>
                  <div class="vb-parties">
                    <div class="vb-party"><div class="vb-label">Billed to</div><div class="vb-strong">${esc(b.customer_name)}</div>${b.customer_phone ? `<div class="vb-muted">${esc(b.customer_phone)}</div>` : ''}${b.customer_email ? `<div class="vb-muted">${esc(b.customer_email)}</div>` : ''}</div>
                    <div class="vb-party"><div class="vb-label">${b.is_stay ? 'Stay details' : 'Safari details'}</div>${b.is_stay ? `<div class="vb-strong">${esc(b.room)}${b.room_type ? ` <span class="vb-muted">· ${esc(b.room_type)}</span>` : ''}</div>` : ''}${stay}${safari}</div>
                  </div>
                  <div class="vb-items"><div class="vb-items-head"><span>Description</span><span>Amount</span></div>${item(b.room_charges_label, b.room_charges_detail, b.room_charges)}${extras}</div>
                  <div class="vb-totals">
                    <div class="vb-row vb-row-sub"><span>Subtotal</span><span>${esc(b.total_amount)}</span></div>
                    <div class="vb-row"><span>Advance paid</span><span>− ${esc(b.advance_payment)}</span></div>
                    ${b.settlement ? `<div class="vb-row"><span>Balance settled at checkout</span><span>${esc(b.settlement)}</span></div>` : ''}
                    <div class="vb-due ${b.balance_due ? '' : 'is-paid'}"><span>Balance due</span><strong>${esc(b.balance)}</strong></div>
                  </div>
                  <div class="vb-foot"><span class="vb-status vb-status-${payTone}">${esc(b.payment_status)}</span><span class="vb-muted">Status: ${esc(b.status_label)}${b.payment_method ? ' · Paid by ' + esc(b.payment_method) : ''}</span></div>
                  <div class="vb-thanks">Thank you for staying with ${esc(v.name)}.</div>
                </div>`;
            },
            async downloadImage() {
                this.busy = true; this.error = '';
                const stage = this.$refs.stage;
                try {
                    if (typeof html2canvas !== 'function') throw new Error('Image export is unavailable.');
                    if (document.fonts && document.fonts.ready) await document.fonts.ready;
                    stage.innerHTML = this.billHtml();
                    const canvas = await html2canvas(stage.firstElementChild, { scale: 2, backgroundColor: '#ffffff', useCORS: true, logging: false });
                    const blob = await new Promise((res, rej) => canvas.toBlob((x) => (x ? res(x) : rej(new Error('No image'))), 'image/png'));
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url; a.download = this.b.file_name;
                    document.body.appendChild(a); a.click(); a.remove();
                    setTimeout(() => URL.revokeObjectURL(url), 2000);
                } catch (e) {
                    console.error(e);
                    this.error = 'Could not create the bill image. Please try again.';
                } finally {
                    stage.innerHTML = '';
                    this.busy = false;
                }
            },
        };
    }
  </script>
@endpush
