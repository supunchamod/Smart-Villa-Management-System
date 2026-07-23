@extends('layouts.public')

@section('title', $settings->villa_name.' | Book Direct & Save')
@section('meta_description', 'Book your stay at '.$settings->villa_name.' direct - the best rate, guaranteed, with a personalised meal plan.')

@section('content')
<div
  x-data='villaBooking(
    @json($cabanaTypesForCalculator),
    @json($boardTypeLabels),
    @json($boardTypeMeals),
    @json($menuOptions),
    @json($settings->currency),
    @json($oldBookingInput)
  )'
  class="pv-has-sticky-bar"
>
  {{-- Hero --}}
  <header class="pv-hero" @if ($settings->website_hero_image_url) style="background-image: linear-gradient(180deg, rgba(0,15,35,.55), rgba(0,15,35,.85)), url({{ $settings->website_hero_image_url }}); background-size: cover; background-position: center;" @endif>
    <div class="pv-container pv-hero-inner">
      <div class="pv-hero-logo">
        @if ($settings->website_logo_url)
          <img src="{{ $settings->website_logo_url }}" alt="{{ $settings->villa_name }} logo">
        @else
          <span class="pv-hero-logo-fallback"><i class="bi bi-water"></i></span>
        @endif
        <span>{{ $settings->villa_name }}</span>
      </div>
      <span class="pv-hero-eyebrow"><i class="bi bi-star-fill"></i> Book Direct &amp; Save</span>
      <h1 class="pv-hero-title">{{ $settings->website_hero_title ?: $settings->villa_name }}</h1>
      <p class="pv-hero-tagline">{{ $settings->website_hero_subtitle ?: "A private cabana escape on Sri Lanka's coast - handpicked rooms, a personalised meal plan, and the best rate you'll find anywhere, guaranteed direct." }}</p>
      <div class="pv-hero-specs">
        <span class="pv-spec-chip"><i class="bi bi-binoculars-fill"></i> Mountain Views</span>
        <span class="pv-spec-chip"><i class="bi bi-cloud-fog2-fill"></i> Cool Air &amp; Misty Atmosphere</span>
        <span class="pv-spec-chip"><i class="bi bi-cup-hot-fill"></i> Outdoor Dining</span>
        <span class="pv-spec-chip"><i class="bi bi-wifi"></i> Free Wi-Fi</span>
        <span class="pv-spec-chip"><i class="bi bi-droplet-half"></i> Hot Water</span>
        <span class="pv-spec-chip"><i class="bi bi-p-circle-fill"></i> Free Parking</span>
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
        <p class="pv-section-sub">Every cabana below is available to book instantly - select one to start your booking &amp; meal plan.</p>
      </div>

      @if ($cabanaTypes->isEmpty())
        <p class="pv-empty-state">No cabanas are available to book right now - please check back soon.</p>
      @else
        <div class="row g-4">
          @foreach ($cabanaTypes as $cabanaType)
            <div class="col-12 col-md-6 col-lg-4">
              <div class="pv-room-card" :class="{ 'pv-room-selected': selectedCabanaTypeId === {{ $cabanaType->id }} }">
                <div class="pv-room-photo" @if ($cabanaType->image_url) style="background-image: url({{ asset($cabanaType->image_url) }})" @endif>
                  @unless ($cabanaType->image_url)
                    <span class="pv-room-photo-fallback"><i class="bi bi-image"></i></span>
                  @endunless
                  @if ($cabanaType->starting_rate !== null)
                    <span class="pv-room-rate-badge">{{ $settings->currency }} {{ number_format($cabanaType->starting_rate, 0) }}+/night</span>
                  @endif
                </div>
                <div class="pv-room-body">
                  <h3 class="pv-room-name">{{ $cabanaType->name }}</h3>
                  @if ($cabanaType->description)
                    <p class="pv-room-meta">{{ $cabanaType->description }}</p>
                  @endif
                  <span class="pv-room-meta"><i class="bi bi-people"></i> Sleeps up to {{ $cabanaType->max_capacity }} guests</span>
                  @if ($cabanaType->pricingTiers->isNotEmpty())
                    <ul class="pv-tier-list">
                      @foreach ($cabanaType->pricingTiers as $tier)
                        <li>
                          <span>{{ $tier->min_pax === $tier->max_pax ? $tier->min_pax : $tier->min_pax.'-'.$tier->max_pax }} Pax</span>
                          <strong>
                            @if ($tier->cabana_only_price !== null)
                              {{ $settings->currency }} {{ number_format($tier->cabana_only_price, 0) }}
                            @elseif ($tier->half_board_price !== null)
                              {{ $settings->currency }} {{ number_format($tier->half_board_price, 0) }}
                            @else
                              {{ $settings->currency }} {{ number_format($tier->full_board_price, 0) }}
                            @endif
                          </strong>
                        </li>
                      @endforeach
                    </ul>
                  @endif
                  <div class="pv-room-price-row">
                    <span class="pv-room-price">
                      @if ($cabanaType->starting_rate !== null)
                        {{ $settings->currency }} {{ number_format($cabanaType->starting_rate, 0) }}<small> starting / night</small>
                      @else
                        <small>Contact for pricing</small>
                      @endif
                    </span>
                  </div>
                  <button
                    type="button"
                    class="pv-select-btn"
                    :class="{ 'pv-select-btn-active': selectedCabanaTypeId === {{ $cabanaType->id }} }"
                    @click="selectCabana({{ $cabanaType->id }})"
                  >
                    <i class="bi" :class="selectedCabanaTypeId === {{ $cabanaType->id }} ? 'bi-check-circle-fill' : 'bi-cursor-fill'"></i>
                    <span x-text="selectedCabanaTypeId === {{ $cabanaType->id }} ? 'Selected' : 'Select Cabana'"></span>
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
  @if ($cabanaTypes->isNotEmpty())
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
          <input type="hidden" name="cabana_type_id" :value="selectedCabanaTypeId">
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
                <p class="pv-calc-sub">Selected cabana: <strong x-text="selectedCabanaType ? selectedCabanaType.name : 'Please select a cabana above'"></strong></p>

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

                <template x-if="selectedCabanaType && guests > selectedCabanaType.max_capacity">
                  <p class="pv-guest-warning"><i class="bi bi-exclamation-triangle-fill"></i> <span x-text="selectedCabanaType.name + ' sleeps up to ' + selectedCabanaType.max_capacity + ' guests - please choose a larger cabana or reduce your guest count.'"></span></p>
                </template>

                <div class="pv-menu-block" style="border-top:0; margin-top: 20px; padding-top: 0;">
                  <label class="pv-form-label mb-2">Board Type</label>
                  <div class="pv-board-grid">
                    <template x-for="(label, key) in boardTypeLabels" :key="key">
                      <label class="pv-board-option" x-show="rateFor(selectedCabanaType, key, guests) !== null">
                        <input type="radio" x-model="boardType" :value="key">
                        <span class="pv-board-card">
                          <strong x-text="label"></strong>
                          <small x-text="currency + ' ' + formatNumber(rateFor(selectedCabanaType, key, guests)) + ' / night'"></small>
                        </span>
                      </label>
                    </template>
                  </div>
                  <template x-if="selectedCabanaType && !boardTypeOptionsAvailable">
                    <p class="pv-guest-warning"><i class="bi bi-exclamation-triangle-fill"></i> No board type is priced for <span x-text="guests"></span> guests on this cabana - please try a different guest count or contact the villa directly.</p>
                  </template>
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

                <div class="pv-menu-block">
                  <label class="pv-form-label mb-2">Value Add-Ons &amp; Experience Options</label>
                  <div class="pv-addon-grid">
                    <label class="pv-addon-option">
                      <input type="checkbox" name="bbq_addon" value="1" x-model="bbqAddon">
                      <span class="pv-addon-card"><i class="bi bi-fire"></i> BBQ &amp; Campfire Experience Setup</span>
                    </label>
                    <label class="pv-addon-option">
                      <input type="checkbox" name="safari_jeep_addon" value="1" x-model="safariJeepAddon">
                      <span class="pv-addon-card"><i class="bi bi-truck-front-fill"></i> Safari Jeep Arrangement</span>
                    </label>
                    <label class="pv-addon-option">
                      <input type="checkbox" name="outdoor_dining_preference" value="1" x-model="outdoorDiningPreference">
                      <span class="pv-addon-card"><i class="bi bi-cup-hot-fill"></i> Outdoor Dining &amp; Fresh Food Preference</span>
                    </label>
                  </div>
                  <p class="pv-summary-note" style="text-align:left; margin-top:8px;">These are requests only - the villa will confirm availability and any charges directly with you.</p>
                </div>
              </div>
            </div>

            <div class="col-lg-5">
              <div class="pv-summary-panel">
                <h3>Your Estimated Total</h3>
                <div class="pv-summary-row"><span>Cabana</span><span x-text="selectedCabanaType ? selectedCabanaType.name : '-'"></span></div>
                <div class="pv-summary-row"><span>Board Type</span><span x-text="boardTypeLabels[boardType] || '-'"></span></div>
                <div class="pv-summary-row"><span>Nights</span><span x-text="nights || '-'"></span></div>
                <div class="pv-summary-row"><span>Guests</span><span x-text="guests + ' total (' + guests + ' Pax)'"></span></div>
                <div class="pv-summary-row" x-show="nightlyRate !== null"><span>Rate for <span x-text="guests"></span> Pax</span><span x-text="currency + ' ' + formatNumber(nightlyRate) + ' / night'"></span></div>

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
        Alpine.data('villaBooking', (cabanaTypes, boardTypeLabels, boardTypeMeals, menuOptions, currency, oldInput) => ({
            cabanaTypes,
            boardTypeLabels,
            boardTypeMeals,
            menuOptions,
            currency,
            selectedCabanaTypeId: oldInput.cabana_type_id ? parseInt(oldInput.cabana_type_id, 10) : (cabanaTypes.length ? cabanaTypes[0].id : null),
            checkIn: oldInput.check_in || '',
            checkOut: oldInput.check_out || '',
            adults: oldInput.adults ? parseInt(oldInput.adults, 10) : 2,
            children: oldInput.children ? parseInt(oldInput.children, 10) : 0,
            boardType: oldInput.board_type || Object.keys(boardTypeLabels)[0] || 'cabana_only',
            menu: {},
            bbqAddon: Boolean(oldInput.bbq_addon),
            safariJeepAddon: Boolean(oldInput.safari_jeep_addon),
            outdoorDiningPreference: Boolean(oldInput.outdoor_dining_preference),
            submitting: false,

            get todayIso() {
                return new Date().toISOString().slice(0, 10);
            },
            get selectedCabanaType() {
                return this.cabanaTypes.find((cabanaType) => cabanaType.id === this.selectedCabanaTypeId) || null;
            },
            get nights() {
                if (!this.checkIn || !this.checkOut) return 0;
                const diff = Math.round((new Date(this.checkOut) - new Date(this.checkIn)) / 86400000);
                return diff > 0 ? diff : 0;
            },
            get includedMeals() {
                return this.boardTypeMeals[this.boardType] || [];
            },
            get guests() {
                return Math.max(1, (parseInt(this.adults, 10) || 0) + (parseInt(this.children, 10) || 0));
            },
            get boardTypeOptionsAvailable() {
                return Object.keys(this.boardTypeLabels)
                    .some((key) => this.rateFor(this.selectedCabanaType, key, this.guests) !== null);
            },
            get nightlyRate() {
                return this.rateFor(this.selectedCabanaType, this.boardType, this.guests);
            },
            get grandTotal() {
                return this.nightlyRate !== null ? this.nightlyRate * this.nights : 0;
            },
            get canSubmit() {
                return Boolean(this.selectedCabanaTypeId) && this.nights > 0 && this.nightlyRate !== null;
            },
            selectCabana(id) {
                this.selectedCabanaTypeId = id;
                this.ensureValidBoardType();
                this.$nextTick(() => {
                    document.getElementById('booking-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            },
            /**
             * Finds the pricing bracket the guest count falls into and
             * returns that bracket's rate for the given board type - null
             * if that board type isn't priced for this cabana/bracket.
             * Guest counts above every configured bracket fall back to the
             * top bracket's rate rather than being refused here.
             */
            rateFor(cabanaType, boardType, guests) {
                if (!cabanaType || !cabanaType.tiers.length) {
                    return null;
                }

                const tier = cabanaType.tiers.find((tier) => guests >= tier.min_pax && guests <= tier.max_pax)
                    || [...cabanaType.tiers].sort((a, b) => b.max_pax - a.max_pax)[0];

                const price = tier[boardType + '_price'];

                return price === null || price === undefined ? null : price;
            },
            /**
             * If the selected board type stops being priced for the
             * current cabana/guest count (e.g. the guest count just
             * changed), switch to the first board type that still is,
             * rather than leaving a stale, unavailable selection in place.
             */
            ensureValidBoardType() {
                if (this.rateFor(this.selectedCabanaType, this.boardType, this.guests) !== null) {
                    return;
                }

                const fallback = Object.keys(this.boardTypeLabels)
                    .find((key) => this.rateFor(this.selectedCabanaType, key, this.guests) !== null);

                if (fallback) {
                    this.boardType = fallback;
                }
            },
            formatNumber(value) {
                return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Math.round(value || 0));
            },
            init() {
                document.getElementById('pv-booking-form')?.addEventListener('submit', () => {
                    this.submitting = true;
                });
                this.ensureValidBoardType();
                this.$watch('guests', () => this.ensureValidBoardType());
            },
        }));
    });
</script>
@endpush
