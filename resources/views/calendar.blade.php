@extends('layouts.app')

@section('title', 'Calendar | '.$globalSettings->villa_name.' Admin Dashboard')

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
    <!-- Calendar Component (Left - 9 Columns) -->
    <div class="col-xl-9">
        <div class="panel calendar-library-panel h-100">
            <!-- Dynamic Mounting Target for JS FullCalendar / Custom Calendar -->
            <div id="bookingsCalendar" data-calendar-mount data-events-url="{{ route('bookings.calendar') }}">
                <div class="fallback-calendar">
                    <div class="fallback-calendar-toolbar">
                        <button class="btn btn-light btn-sm" type="button" disabled><i class="bi bi-chevron-left"></i></button>
                        <h3 class="fw-bold text-navy" style="color: #002d62;">May 2026</h3>
                        <div>
                            <button class="btn btn-sm text-white" type="button" style="background-color: #e8b22d;" disabled>Today</button>
                            <button class="btn btn-light btn-sm" type="button" disabled><i class="bi bi-chevron-right"></i></button>
                        </div>
                    </div>
                    
                    <div class="fallback-calendar-grid">
                        <div class="fallback-calendar-head">Mon</div>
                        <div class="fallback-calendar-head">Tue</div>
                        <div class="fallback-calendar-head">Wed</div>
                        <div class="fallback-calendar-head">Thu</div>
                        <div class="fallback-calendar-head">Fri</div>
                        <div class="fallback-calendar-head">Sat</div>
                        <div class="fallback-calendar-head">Sun</div>

                        <!-- Calendar Days Layer with Villa Booking Fallbacks -->
                        <div class="fallback-calendar-day muted"></div>
                        <div class="fallback-calendar-day muted"></div>
                        <div class="fallback-calendar-day muted"></div>
                        <button class="fallback-calendar-day" type="button"><strong>1</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>2</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>3</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>4</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>5</strong><span style="--event-color:#e8b22d">Cabana A - Reserved</span></button>
                        <button class="fallback-calendar-day" type="button"><strong>6</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>7</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>8</strong><span style="--event-color:#002d62">Villa Suite Check-in</span></button>
                        <button class="fallback-calendar-day" type="button"><strong>9</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>10</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>11</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>12</strong><span style="--event-color:#2fa84f">Full Venue Event</span></button>
                        <button class="fallback-calendar-day" type="button"><strong>13</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>14</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>15</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>16</strong><span style="--event-color:#e8b22d">Cabana B - Check-in</span></button>
                        <button class="fallback-calendar-day" type="button"><strong>17</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>18</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>19</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>20</strong><span style="--event-color:#002d62">Corporate Booking</span></button>
                        <button class="fallback-calendar-day" type="button"><strong>21</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>22</strong></button>
                        <button class="fallback-calendar-day today" type="button"><strong>23</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>24</strong><span style="--event-color:#2fa84f">Premium Cabana Pack</span></button>
                        <button class="fallback-calendar-day" type="button"><strong>25</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>26</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>27</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>28</strong><span style="--event-color:#dc2626">Maintenance Block</span></button>
                        <button class="fallback-calendar-day" type="button"><strong>29</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>30</strong></button>
                        <button class="fallback-calendar-day" type="button"><strong>31</strong></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Villa Upcoming Bookings / Next Scheduled Items (Right - 3 Columns) -->
    <div class="col-xl-3">
        <div class="panel event-agenda h-100 d-flex flex-column justify-content-between p-3" style="background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
            <div>
                <!-- Header Block with Gold Theme Alignment -->
                <div class="panel-head mb-4 pb-2" style="border-bottom: 2px solid #e8b22d;">
                    <div>
                        <span class="text-uppercase fw-bold text-muted d-block mb-1" style="font-size: 11px; letter-spacing: 1.5px;">Villa Schedules</span>
                        <h2 class="fw-bold h5 mb-0" style="color: #002d62;">Upcoming Bookings</h2>
                    </div>
                </div>

                <!-- Livewire / Blade Variable Loop Target Area -->
                <div class="vc-agenda-list d-flex flex-column gap-3">
                    
                    <!-- Booking Item 1 -->
                    <div class="d-flex align-items-center justify-content-between p-3 rounded vc-agenda-card" style="background: #fdfdfd; border-left: 4px solid #002d62; border-top: 1px solid #eee; border-right: 1px solid #eee; border-bottom: 1px solid #eee; transition: all 0.3s ease;">
                        <div class="d-flex align-items-center">
                            <!-- Premium Date Badge -->
                            <div class="text-center me-3 px-2 py-1 rounded text-white" style="background: #002d62; min-width: 55px; flex-shrink: 0;">
                                <span class="d-block fw-bold" style="font-size: 14px; color: #e8b22d;">08</span>
                                <span class="text-uppercase fw-semibold" style="font-size: 9px; letter-spacing: 0.5px;">May</span>
                            </div>
                            <div class="vc-agenda-content">
                                <strong class="vc-agenda-title d-block" style="color: #002d62; font-size: 13.5px;">Luxury Cabana A</strong>
                                <small class="vc-agenda-meta text-muted" style="font-size: 11.5px;">Check-in: 14:00 &middot; 2 Nights</small>
                            </div>
                        </div>
                        <span class="fw-bold px-2 py-1 rounded" style="background: rgba(232, 178, 45, 0.1); color: #002d62; font-size: 12px; border: 1px dashed #e8b22d;">Rs. 45,000</span>
                    </div>

                    <!-- Booking Item 2 -->
                    <div class="d-flex align-items-center justify-content-between p-3 rounded vc-agenda-card" style="background: #fdfdfd; border-left: 4px solid #e8b22d; border-top: 1px solid #eee; border-right: 1px solid #eee; border-bottom: 1px solid #eee; transition: all 0.3s ease;">
                        <div class="d-flex align-items-center">
                            <div class="text-center me-3 px-2 py-1 rounded text-white" style="background: #002d62; min-width: 55px; flex-shrink: 0;">
                                <span class="d-block fw-bold" style="font-size: 14px; color: #e8b22d;">12</span>
                                <span class="text-uppercase fw-semibold" style="font-size: 9px; letter-spacing: 0.5px;">May</span>
                            </div>
                            <div class="vc-agenda-content">
                                <strong class="vc-agenda-title d-block" style="color: #002d62; font-size: 13.5px;">Deluxe Villa Suite</strong>
                                <small class="vc-agenda-meta text-muted" style="font-size: 11.5px;">Check-in: 14:00 &middot; 1 Night</small>
                            </div>
                        </div>
                        <span class="fw-bold px-2 py-1 rounded" style="background: rgba(232, 178, 45, 0.1); color: #002d62; font-size: 12px; border: 1px dashed #e8b22d;">Rs. 38,000</span>
                    </div>

                    <!-- Booking Item 3 -->
                    <div class="d-flex align-items-center justify-content-between p-3 rounded vc-agenda-card" style="background: #fdfdfd; border-left: 4px solid #002d62; border-top: 1px solid #eee; border-right: 1px solid #eee; border-bottom: 1px solid #eee; transition: all 0.3s ease;">
                        <div class="d-flex align-items-center">
                            <div class="text-center me-3 px-2 py-1 rounded text-white" style="background: #002d62; min-width: 55px; flex-shrink: 0;">
                                <span class="d-block fw-bold" style="font-size: 14px; color: #e8b22d;">16</span>
                                <span class="text-uppercase fw-semibold" style="font-size: 9px; letter-spacing: 0.5px;">May</span>
                            </div>
                            <div class="vc-agenda-content">
                                <strong class="vc-agenda-title d-block" style="color: #002d62; font-size: 13.5px;">Full Venue Rental</strong>
                                <small class="vc-agenda-meta text-muted" style="font-size: 11.5px;">Event &middot; Corporate Group</small>
                            </div>
                        </div>
                        <span class="fw-bold px-2 py-1 rounded" style="background: rgba(232, 178, 45, 0.1); color: #002d62; font-size: 12px; border: 1px dashed #e8b22d;">Rs. 180,000</span>
                    </div>

                    <!-- Booking Item 4 -->
                    <div class="d-flex align-items-center justify-content-between p-3 rounded vc-agenda-card" style="background: #fdfdfd; border-left: 4px solid #e8b22d; border-top: 1px solid #eee; border-right: 1px solid #eee; border-bottom: 1px solid #eee; transition: all 0.3s ease;">
                        <div class="d-flex align-items-center">
                            <div class="text-center me-3 px-2 py-1 rounded text-white" style="background: #002d62; min-width: 55px; flex-shrink: 0;">
                                <span class="d-block fw-bold" style="font-size: 14px; color: #e8b22d;">28</span>
                                <span class="text-uppercase fw-semibold" style="font-size: 9px; letter-spacing: 0.5px;">May</span>
                            </div>
                            <div class="vc-agenda-content">
                                <strong class="vc-agenda-title d-block" style="color: #002d62; font-size: 13.5px;">Premium Cabana B</strong>
                                <small class="vc-agenda-meta text-muted" style="font-size: 11.5px;">Check-in: 14:00 &middot; 3 Nights</small>
                            </div>
                        </div>
                        <span class="fw-bold px-2 py-1 rounded" style="background: rgba(232, 178, 45, 0.1); color: #002d62; font-size: 12px; border: 1px dashed #e8b22d;">Rs. 65,000</span>
                    </div>

                </div>
            </div>
            
            <!-- Bottom Footer Action Component Alignment -->
            <a href="{{ route('bookings.index') }}" class="btn w-100 text-uppercase fw-bold text-center mt-3 py-2 transition" style="border: 2px solid #e8b22d; color: #002d62; background: transparent; font-size: 11px; letter-spacing: 1.5px; border-radius: 8px;">
                View All Bookings <i class="bi bi-arrow-right ms-2" style="color: #e8b22d;"></i>
            </a>
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
