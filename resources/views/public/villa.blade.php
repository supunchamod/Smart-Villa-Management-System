@extends('layouts.public')

@section('title', $settings->villa_name.' | Book Direct & Save')
@section('meta_description', 'Book your stay at '.$settings->villa_name.' direct - the best rate, guaranteed, with a personalised meal plan.')

@section('content')
<div
  x-data='villaBooking(
    @json($rooms->map(fn ($room) => [
        "id" => $room->id,
        "name" => $room->name_or_number,
        "type" => $room->type,
        "price_per_night" => (float) $room->price_per_night,
        "capacity" => $room->capacity,
        "photo_url" => $room->photo_url,
    ])),
    @json($boardTypes),
    @json($menuOptions),
    @json($settings->currency),
    @json([
        "check_in" => old("check_in"),
        "check_out" => old("check_out"),
        "adults" => old("adults"),
        "children" => old("children"),
        "board_type" => old("board_type"),
        "room_id" => old("room_id"),
    ])
  )'
  class="pv-has-sticky-bar"
>
  {{-- Hero --}}
  <header class="pv-hero">
    <div class="pv-container pv-hero-inner">
      <div class="pv-hero-logo">
        @if ($settings->villa_logo)
          <img src="{{ $settings->villa_logo }}" alt="{{ $settings->villa_name }} logo">
        @else
          <span class="pv-hero-logo-fallback"><i class="bi bi-water"></i></span>
        @endif
        <span>{{ $settings->villa_name }}</span>
      </div>
      <span class="pv-hero-eyebrow"><i class="bi bi-star-fill"></i> Book Direct &amp; Save</span>
      <h1 class="pv-hero-title">{{ $settings->villa_name }}</h1>
      <p class="pv-hero-tagline">A private cabana escape on Sri Lanka's coast - handpicked rooms, a personalised meal plan, and the best rate you'll find anywhere, guaranteed direct.</p>
      <div class="pv-hero-specs">
        <span class="pv-spec-chip"><i class="bi bi-wifi"></i> Free WiFi</span>
        <span class="pv-spec-chip"><i class="bi bi-water"></i> Private Pool</span>
        <span class="pv-spec-chip"><i class="bi bi-snow2"></i> Air Conditioning</span>
        <span class="pv-spec-chip"><i class="bi bi-sunset"></i> Ocean &amp; Jungle Views</span>
      </div>
    </div>
  </header>

  <div class="pv-container">
    <div class="pv-trust-row">
      <span class="pv-trust-item"><i class="bi bi-shield-check"></i> Best Rate Guaranteed</span>
      <span class="pv-trust-item"><i class="bi bi-cash-coin"></i> No Booking Fees</span>
      <span class="pv-trust-item"><i class="bi bi-whatsapp"></i> Instant Owner Response</span>
    </div>
  </div>

  {{-- Booking success / WhatsApp confirmation --}}
  @if (session('bookingSubmitted'))
    <div class="pv-container" id="booking-confirmation">
      <div class="pv-confirmation">
        <div class="pv-confirmation-icon"><i class="bi bi-check-lg"></i></div>
        <h3>Enquiry received - Reference {{ session('bookingReference') }}</h3>
        <p>We've saved your request as a pending booking. Tap below to send your booking summary straight to the villa on WhatsApp for instant confirmation.</p>
        <a href="{{ session('whatsappUrl') }}" class="pv-whatsapp-btn" target="_blank" rel="noopener">
          <i class="bi bi-whatsapp"></i> Notify the Villa on WhatsApp
        </a>
      </div>
    </div>
  @endif

  {{-- Cabana / package selection grid --}}
  <section class="pv-section" id="cabanas">
    <div class="pv-container">
      <div class="pv-section-head">
        <span class="pv-accent-bar" aria-hidden="true"></span>
        <span class="pv-eyebrow">Choose Your Stay</span>
        <h2 class="pv-section-title">Cabanas &amp; Villa Suites</h2>
        <p class="pv-section-sub">Every room is available to book instantly below - select one to start your booking &amp; meal plan.</p>
      </div>

      @if ($rooms->isEmpty())
        <p class="pv-empty-state">No cabanas are available to book right now - please check back soon.</p>
      @else
        <div class="row g-4">
          @foreach ($rooms as $room)
            <div class="col-12 col-md-6 col-lg-4">
              <div class="pv-room-card" :class="{ 'pv-room-selected': selectedRoomId === {{ $room->id }} }">
                <div class="pv-room-photo" @if ($room->photo_url) style="background-image: url({{ $room->photo_url }})" @endif>
                  @unless ($room->photo_url)
                    <span class="pv-room-photo-fallback"><i class="bi bi-image"></i></span>
                  @endunless
                  <span class="pv-room-rate-badge">{{ $settings->currency }} {{ number_format($room->price_per_night, 0) }}/night</span>
                </div>
                <div class="pv-room-body">
                  <span class="pv-room-type">{{ $room->type }}</span>
                  <h3 class="pv-room-name">{{ $room->name_or_number }}</h3>
                  <span class="pv-room-meta"><i class="bi bi-people"></i> Sleeps up to {{ $room->capacity }} guests</span>
                  <div class="pv-room-price-row">
                    <span class="pv-room-price">{{ $settings->currency }} {{ number_format($room->price_per_night, 0) }}<small> / night</small></span>
                  </div>
                  <button
                    type="button"
                    class="pv-select-btn"
                    :class="{ 'pv-select-btn-active': selectedRoomId === {{ $room->id }} }"
                    @click="selectRoom({{ $room->id }})"
                  >
                    <i class="bi" :class="selectedRoomId === {{ $room->id }} ? 'bi-check-circle-fill' : 'bi-cursor-fill'"></i>
                    <span x-text="selectedRoomId === {{ $room->id }} ? 'Selected' : 'Select Cabana'"></span>
                  </button>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </section>

  {{-- Booking & meal plan calculator --}}
  @if ($rooms->isNotEmpty())
    <section class="pv-section" id="booking-form" style="background: var(--pv-surface);">
      <div class="pv-container">
        <div class="pv-section-head">
          <span class="pv-accent-bar" aria-hidden="true"></span>
          <span class="pv-eyebrow">Live Availability Request</span>
          <h2 class="pv-section-title">Build Your Stay</h2>
          <p class="pv-section-sub">Pick your dates and meal plan - your total updates instantly, no page reloads.</p>
        </div>

        <form method="POST" action="{{ route('public.villa.book', $slug) }}" id="pv-booking-form">
          @csrf
          <input type="hidden" name="room_id" :value="selectedRoomId">
          <input type="hidden" name="board_type" :value="boardType">

          @if ($errors->any())
            <div class="pv-guest-warning mb-4">
              <i class="bi bi-exclamation-triangle-fill"></i>
              <span>
                @foreach ($errors->all() as $error)
                  {{ $error }}@if (!$loop->last)<br>@endif
                @endforeach
              </span>
            </div>
          @endif

          <div class="row g-4">
            <div class="col-lg-7">
              <div class="pv-calc-panel">
                <h3>Your Details &amp; Dates</h3>
                <p class="pv-calc-sub">Selected cabana: <strong x-text="selectedRoom ? selectedRoom.name : 'Please select a cabana above'"></strong></p>

                <div class="row g-3 mb-2">
                  <div class="col-md-6">
                    <label class="pv-form-label">Full Name</label>
                    <input class="pv-input" type="text" name="customer_name" value="{{ old('customer_name') }}" required maxlength="255" placeholder="Your name">
                  </div>
                  <div class="col-md-6">
                    <label class="pv-form-label">WhatsApp / Phone Number</label>
                    <input class="pv-input" type="tel" name="customer_phone" value="{{ old('customer_phone') }}" required maxlength="30" placeholder="+94 77 123 4567">
                  </div>
                  <div class="col-md-6">
                    <label class="pv-form-label">Email <small class="text-muted">(optional)</small></label>
                    <input class="pv-input" type="email" name="customer_email" value="{{ old('customer_email') }}" maxlength="255" placeholder="you@example.com">
                  </div>
                </div>

                <div class="row g-3 mb-2">
                  <div class="col-md-6">
                    <label class="pv-form-label">Check-in</label>
                    <input class="pv-input" type="date" name="check_in" x-model="checkIn" :min="todayIso" required>
                  </div>
                  <div class="col-md-6">
                    <label class="pv-form-label">Check-out</label>
                    <input class="pv-input" type="date" name="check_out" x-model="checkOut" :min="checkIn || todayIso" required>
                  </div>
                  <div class="col-md-6">
                    <label class="pv-form-label">Adult Guests</label>
                    <input class="pv-input" type="number" name="adults" x-model.number="adults" min="1" max="20" required>
                  </div>
                  <div class="col-md-6">
                    <label class="pv-form-label">Child Guests</label>
                    <input class="pv-input" type="number" name="children" x-model.number="children" min="0" max="20">
                  </div>
                </div>

                <template x-if="selectedRoom && guests > selectedRoom.capacity">
                  <p class="pv-guest-warning"><i class="bi bi-exclamation-triangle-fill"></i> <span x-text="selectedRoom.name + ' sleeps up to ' + selectedRoom.capacity + ' guests - please choose a larger cabana or reduce your guest count.'"></span></p>
                </template>

                <div class="pv-menu-block" style="border-top:0; margin-top: 20px; padding-top: 0;">
                  <label class="pv-form-label mb-2">Board Type</label>
                  <div class="pv-board-grid">
                    <template x-for="(board, key) in boardTypes" :key="key">
                      <label class="pv-board-option">
                        <input type="radio" x-model="boardType" :value="key">
                        <span class="pv-board-card">
                          <strong x-text="board.label"></strong>
                          <small x-text="board.supplement > 0 ? ('+ ' + currency + ' ' + formatNumber(board.supplement) + ' / guest / night') : 'No meal supplement'"></small>
                        </span>
                      </label>
                    </template>
                  </div>
                </div>

                <template x-for="meal in includedMeals" :key="meal">
                  <div class="pv-menu-block">
                    <div class="pv-menu-title"><i class="bi bi-egg-fried"></i> <span x-text="meal"></span> Menu</div>
                    <div class="pv-menu-pills">
                      <template x-for="option in (menuOptions[meal] || [])" :key="option">
                        <label class="pv-menu-pill">
                          <input type="radio" :name="'menu_items[' + meal + ']'" :value="option" x-model="menu[meal]">
                          <span class="pv-menu-pill-label" x-text="option"></span>
                        </label>
                      </template>
                    </div>
                  </div>
                </template>
              </div>
            </div>

            <div class="col-lg-5">
              <div class="pv-summary-panel">
                <h3>Your Estimated Total</h3>
                <div class="pv-summary-row"><span>Cabana</span><span x-text="selectedRoom ? selectedRoom.name : '-'"></span></div>
                <div class="pv-summary-row"><span>Nights</span><span x-text="nights || '-'"></span></div>
                <div class="pv-summary-row"><span>Guests</span><span x-text="guests + ' total'"></span></div>
                <div class="pv-summary-row"><span>Room Total</span><span x-text="currency + ' ' + formatNumber(roomTotal)"></span></div>
                <div class="pv-summary-row" x-show="mealTotal > 0"><span>Meal Plan</span><span x-text="currency + ' ' + formatNumber(mealTotal)"></span></div>

                <div class="pv-summary-total">
                  <span>Total<br><small>Estimated</small></span>
                  <strong x-text="currency + ' ' + formatNumber(grandTotal)"></strong>
                </div>

                <button type="submit" form="pv-booking-form" class="pv-book-btn" :disabled="!canSubmit || submitting">
                  <i class="bi bi-whatsapp"></i>
                  <span x-text="submitting ? 'Sending...' : 'Book Now'"></span>
                </button>
                <p class="pv-summary-note">No payment now - the villa will confirm availability &amp; payment details with you directly.</p>
              </div>
            </div>
          </div>
        </form>
      </div>
    </section>

    {{-- Sticky mobile price + CTA bar --}}
    <div class="pv-sticky-bar">
      <div class="pv-sticky-bar-total">
        <span>Total Estimate</span>
        <strong x-text="currency + ' ' + formatNumber(grandTotal)"></strong>
      </div>
      <button type="submit" form="pv-booking-form" class="pv-book-btn" :disabled="!canSubmit || submitting">
        <span x-text="submitting ? 'Sending...' : 'Book Now'"></span>
      </button>
    </div>
  @endif

  <footer class="pv-footer">
    <div class="pv-container">
      <p><strong>{{ $settings->villa_name }}</strong> &middot; {{ $settings->address }}</p>
      <p>{{ $settings->phone_number }} @if ($settings->email) &middot; {{ $settings->email }} @endif</p>
    </div>
  </footer>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('villaBooking', (rooms, boardTypes, menuOptions, currency, oldInput) => ({
            rooms,
            boardTypes,
            menuOptions,
            currency,
            selectedRoomId: oldInput.room_id ? parseInt(oldInput.room_id, 10) : (rooms.length ? rooms[0].id : null),
            checkIn: oldInput.check_in || '',
            checkOut: oldInput.check_out || '',
            adults: oldInput.adults ? parseInt(oldInput.adults, 10) : 2,
            children: oldInput.children ? parseInt(oldInput.children, 10) : 0,
            boardType: oldInput.board_type || Object.keys(boardTypes)[0] || 'cabana_only',
            menu: {},
            submitting: false,

            get todayIso() {
                return new Date().toISOString().slice(0, 10);
            },
            get selectedRoom() {
                return this.rooms.find((room) => room.id === this.selectedRoomId) || null;
            },
            get nights() {
                if (!this.checkIn || !this.checkOut) return 0;
                const diff = Math.round((new Date(this.checkOut) - new Date(this.checkIn)) / 86400000);
                return diff > 0 ? diff : 0;
            },
            get includedMeals() {
                return this.boardTypes[this.boardType]?.meals || [];
            },
            get guests() {
                return Math.max(1, (parseInt(this.adults, 10) || 0) + (parseInt(this.children, 10) || 0));
            },
            get roomTotal() {
                return this.selectedRoom ? this.selectedRoom.price_per_night * this.nights : 0;
            },
            get mealTotal() {
                const supplement = this.boardTypes[this.boardType]?.supplement || 0;
                return supplement * this.guests * this.nights;
            },
            get grandTotal() {
                return this.roomTotal + this.mealTotal;
            },
            get canSubmit() {
                return Boolean(this.selectedRoomId) && this.nights > 0;
            },
            selectRoom(id) {
                this.selectedRoomId = id;
                this.$nextTick(() => {
                    document.getElementById('booking-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            },
            formatNumber(value) {
                return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Math.round(value || 0));
            },
            init() {
                document.getElementById('pv-booking-form')?.addEventListener('submit', () => {
                    this.submitting = true;
                });
            },
        }));
    });
</script>
@endpush
