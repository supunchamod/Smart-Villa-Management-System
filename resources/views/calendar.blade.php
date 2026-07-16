@extends('layouts.app')

@section('title', 'Calendar | Dashora Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="index.html"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Calendar</h1></li>
            </ol>
          </nav>
          <div class="page-actions"><a class="btn btn-primary" href="{{ route('bookings.create') }}"><i class="bi bi-plus-lg"></i> Add Booking</a></div>
        </div>
        <div class="calendar-shell">
  <div class="calendar-toolbar panel">
    <div><span class="eyebrow">Interactive Calendar</span><h2>Team Calendar</h2><p>Use month, week, day, and list views with working previous, next, and today controls.</p></div>
    <div class="calendar-legend"><span><i class="event-blue"></i> Confirmed</span><span><i class="event-green"></i> Checked out</span><span><i class="event-red"></i> Cancelled</span></div>
  </div>
  <div class="row g-4 mt-1">
    <div class="col-xl-9"><div class="panel calendar-library-panel"><div id="dashoraCalendar" data-calendar-mount data-events-url="{{ route('bookings.calendar') }}"><div class="fallback-calendar"><div class="fallback-calendar-toolbar"><button class="btn btn-light btn-sm" type="button" disabled><i class="bi bi-chevron-left"></i></button><h3>May 2026</h3><div><button class="btn btn-primary btn-sm" type="button" disabled>Today</button><button class="btn btn-light btn-sm" type="button" disabled><i class="bi bi-chevron-right"></i></button></div></div><div class="fallback-calendar-grid"><div class="fallback-calendar-head">Mon</div><div class="fallback-calendar-head">Tue</div><div class="fallback-calendar-head">Wed</div><div class="fallback-calendar-head">Thu</div><div class="fallback-calendar-head">Fri</div><div class="fallback-calendar-head">Sat</div><div class="fallback-calendar-head">Sun</div><div class="fallback-calendar-day muted"></div><div class="fallback-calendar-day muted"></div><div class="fallback-calendar-day muted"></div><button class="fallback-calendar-day" type="button"><strong>1</strong></button><button class="fallback-calendar-day" type="button"><strong>2</strong></button><button class="fallback-calendar-day" type="button"><strong>3</strong></button><button class="fallback-calendar-day" type="button"><strong>4</strong></button><button class="fallback-calendar-day" type="button"><strong>5</strong><span style="--event-color:#5278ff">Sprint Planning</span></button><button class="fallback-calendar-day" type="button"><strong>6</strong></button><button class="fallback-calendar-day" type="button"><strong>7</strong></button><button class="fallback-calendar-day" type="button"><strong>8</strong><span style="--event-color:#2fa84f">Design Sync</span></button><button class="fallback-calendar-day" type="button"><strong>9</strong></button><button class="fallback-calendar-day" type="button"><strong>10</strong></button><button class="fallback-calendar-day" type="button"><strong>11</strong></button><button class="fallback-calendar-day" type="button"><strong>12</strong><span style="--event-color:#f6a642">Invoice Review</span></button><button class="fallback-calendar-day" type="button"><strong>13</strong></button><button class="fallback-calendar-day" type="button"><strong>14</strong></button><button class="fallback-calendar-day" type="button"><strong>15</strong></button><button class="fallback-calendar-day" type="button"><strong>16</strong><span style="--event-color:#8a1df2">Product Demo</span></button><button class="fallback-calendar-day" type="button"><strong>17</strong></button><button class="fallback-calendar-day" type="button"><strong>18</strong></button><button class="fallback-calendar-day" type="button"><strong>19</strong></button><button class="fallback-calendar-day" type="button"><strong>20</strong><span style="--event-color:#5278ff">Roadmap Review</span></button><button class="fallback-calendar-day" type="button"><strong>21</strong></button><button class="fallback-calendar-day" type="button"><strong>22</strong></button><button class="fallback-calendar-day today" type="button"><strong>23</strong></button><button class="fallback-calendar-day" type="button"><strong>24</strong><span style="--event-color:#2fa84f">Release Launch</span></button><button class="fallback-calendar-day" type="button"><strong>25</strong></button><button class="fallback-calendar-day" type="button"><strong>26</strong></button><button class="fallback-calendar-day" type="button"><strong>27</strong></button><button class="fallback-calendar-day" type="button"><strong>28</strong><span style="--event-color:#dc2626">Board Update</span></button><button class="fallback-calendar-day" type="button"><strong>29</strong></button><button class="fallback-calendar-day" type="button"><strong>30</strong></button><button class="fallback-calendar-day" type="button"><strong>31</strong></button></div></div></div></div></div>
    <div class="col-xl-3"><div class="panel event-agenda"><div class="panel-head"><div><h2>Upcoming</h2><p>Next scheduled items</p></div></div><div class="agenda-item"><span>09:00</span><div><strong>Design Sync</strong><small>Product team</small></div></div><div class="agenda-item"><span>11:30</span><div><strong>Invoice Review</strong><small>Finance</small></div></div><div class="agenda-item"><span>14:00</span><div><strong>Sprint Demo</strong><small>Engineering</small></div></div><div class="agenda-item"><span>16:30</span><div><strong>Board Update</strong><small>Leadership</small></div></div></div></div>
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
