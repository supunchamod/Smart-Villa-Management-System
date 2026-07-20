@extends('layouts.app')

@section('title', 'Calendar | Dashora Admin Dashboard')

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
    <div class="calendar-legend"><span><i class="event-blue"></i> Confirmed</span><span><i class="event-green"></i> Checked out</span><span><i class="event-red"></i> Cancelled</span></div>
  </div>
  <div class="row g-4 mt-1">
    <div class="col-xl-9"><div class="panel calendar-library-panel h-100"><div id="dashoraCalendar" data-calendar-mount data-events-url="{{ route('bookings.calendar') }}"><div class="fallback-calendar"><div class="fallback-calendar-toolbar"><button class="btn btn-light btn-sm" type="button" disabled><i class="bi bi-chevron-left"></i></button><h3>May 2026</h3><div><button class="btn btn-primary btn-sm" type="button" disabled>Today</button><button class="btn btn-light btn-sm" type="button" disabled><i class="bi bi-chevron-right"></i></button></div></div><div class="fallback-calendar-grid"><div class="fallback-calendar-head">Mon</div><div class="fallback-calendar-head">Tue</div><div class="fallback-calendar-head">Wed</div><div class="fallback-calendar-head">Thu</div><div class="fallback-calendar-head">Fri</div><div class="fallback-calendar-head">Sat</div><div class="fallback-calendar-head">Sun</div><div class="fallback-calendar-day muted"></div><div class="fallback-calendar-day muted"></div><div class="fallback-calendar-day muted"></div><button class="fallback-calendar-day" type="button"><strong>1</strong></button><button class="fallback-calendar-day" type="button"><strong>2</strong></button><button class="fallback-calendar-day" type="button"><strong>3</strong></button><button class="fallback-calendar-day" type="button"><strong>4</strong></button><button class="fallback-calendar-day" type="button"><strong>5</strong><span style="--event-color:#5278ff">Cabana A - Reserved</span></button><button class="fallback-calendar-day" type="button"><strong>6</strong></button><button class="fallback-calendar-day" type="button"><strong>7</strong></button><button class="fallback-calendar-day" type="button"><strong>8</strong><span style="--event-color:#2fa84f">Villa Suite Check-in</span></button><button class="fallback-calendar-day" type="button"><strong>9</strong></button><button class="fallback-calendar-day" type="button"><strong>10</strong></button><button class="fallback-calendar-day" type="button"><strong>11</strong></button><button class="fallback-calendar-day" type="button"><strong>12</strong><span style="--event-color:#f6a642">Advance Payment Due</span></button><button class="fallback-calendar-day" type="button"><strong>13</strong></button><button class="fallback-calendar-day" type="button"><strong>14</strong></button><button class="fallback-calendar-day" type="button"><strong>15</strong></button><button class="fallback-calendar-day" type="button"><strong>16</strong><span style="--event-color:#8a1df2">Full Venue Event</span></button><button class="fallback-calendar-day" type="button"><strong>17</strong></button><button class="fallback-calendar-day" type="button"><strong>18</strong></button><button class="fallback-calendar-day" type="button"><strong>19</strong></button><button class="fallback-calendar-day" type="button"><strong>20</strong><span style="--event-color:#5278ff">Cabana B - Reserved</span></button><button class="fallback-calendar-day" type="button"><strong>21</strong></button><button class="fallback-calendar-day" type="button"><strong>22</strong></button><button class="fallback-calendar-day today" type="button"><strong>23</strong></button><button class="fallback-calendar-day" type="button"><strong>24</strong><span style="--event-color:#2fa84f">Deluxe Suite Check-out</span></button><button class="fallback-calendar-day" type="button"><strong>25</strong></button><button class="fallback-calendar-day" type="button"><strong>26</strong></button><button class="fallback-calendar-day" type="button"><strong>27</strong></button><button class="fallback-calendar-day" type="button"><strong>28</strong><span style="--event-color:#dc2626">Final Settlement Due</span></button><button class="fallback-calendar-day" type="button"><strong>29</strong></button><button class="fallback-calendar-day" type="button"><strong>30</strong></button><button class="fallback-calendar-day" type="button"><strong>31</strong></button></div></div></div></div></div>
    <div class="col-xl-3">
      <div class="panel event-agenda h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="panel-head"><div><h2>Upcoming</h2><p>Next scheduled check-ins</p></div></div>
          <div class="vc-agenda-list">
            @forelse ($upcomingBookings as $booking)
              @php $nights = max(1, $booking->check_in->diffInDays($booking->check_out)); @endphp
              <div class="d-flex align-items-center justify-content-between p-3 mb-3 rounded shadow-xs vc-agenda-card">
                <div class="text-center me-3 px-2 py-1 rounded vc-agenda-date">
                  <span class="d-block fw-bold vc-agenda-date-num">{{ $booking->check_in->format('d') }}</span>
                  <span class="text-uppercase vc-agenda-date-month">{{ $booking->check_in->format('M') }}</span>
                </div>
                <div class="vc-agenda-content">
                  <strong class="vc-agenda-title">{{ $booking->room->name_or_number }}</strong>
                  <small class="vc-agenda-meta">{{ $booking->customer_name }} &middot; {{ $nights }} Night{{ $nights === 1 ? '' : 's' }}</small>
                </div>
                <span class="vc-agenda-revenue">{{ $globalSettings->currency }} {{ number_format($booking->total_amount, 0) }}</span>
              </div>
            @empty
              <p class="vc-empty">No upcoming check-ins scheduled.</p>
            @endforelse
          </div>
        </div>
        <a href="{{ route('bookings.index') }}" class="vc-agenda-footer-link">View all bookings <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>
  </div>
</div>
        <div class="modal fade" id="bookingDetailModal" tabindex="-1" aria-hidden="true"
             x-data="{ booking: null }"
             x-init="window.addEventListener('calendar-event-selected', (event) => { booking = event.detail; bootstrap.Modal.getOrCreateInstance($el).show(); })">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content dash-modal">
              <div class="modal-header">
                <div><span class="eyebrow">Booking</span><h2 class="modal-title" x-text="booking?.customer_name"></h2></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body" x-show="booking">
                <div class="row g-3">
                  <div class="col-6"><small class="text-muted d-block">Room</small><strong x-text="booking?.room"></strong></div>
                  <div class="col-6"><small class="text-muted d-block">Status</small><strong x-text="booking?.status"></strong></div>
                  <div class="col-6"><small class="text-muted d-block">Check-in</small><strong x-text="booking?.check_in"></strong></div>
                  <div class="col-6"><small class="text-muted d-block">Check-out</small><strong x-text="booking?.check_out"></strong></div>
                  <div class="col-6" x-show="booking?.customer_email"><small class="text-muted d-block">Email</small><strong x-text="booking?.customer_email"></strong></div>
                  <div class="col-6" x-show="booking?.customer_phone"><small class="text-muted d-block">Phone</small><strong x-text="booking?.customer_phone"></strong></div>
                  <div class="col-4"><small class="text-muted d-block">Total</small><strong x-text="booking?.total_amount"></strong></div>
                  <div class="col-4"><small class="text-muted d-block">Advance</small><strong x-text="booking?.advance_payment"></strong></div>
                  <div class="col-4"><small class="text-muted d-block">Balance</small><strong x-text="booking?.balance"></strong></div>
                </div>
              </div>
            </div>
          </div>
        </div>
@endsection

@push('scripts')
  <script src="{{ asset('assets/vendor/fullcalendar/index.global.min.js') }}"></script>
@endpush
