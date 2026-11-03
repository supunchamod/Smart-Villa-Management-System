@php
    $isEdit = isset($booking);
    $bookingType = old('booking_type', $booking->booking_type ?? 'accommodation_only');

    // Included services: ticked pre-defined options, plus any custom items staff typed in.
    $inclusionOptions = \App\Models\Booking::INCLUSION_OPTIONS;
    $savedInclusions = collect(old('inclusions', $booking->inclusions ?? []))->map(fn ($item) => trim((string) $item))->filter()->values()->all();
    $selectedInclusions = array_values(array_intersect($inclusionOptions, $savedInclusions));
    $customInclusions = array_values(array_udiff($savedInclusions, $inclusionOptions, 'strcasecmp'));
@endphp
<div
  class="row g-3"
  x-data="bookingForm({
      total: {{ (float) old('total_amount', $booking->total_amount ?? 0) }},
      advance: {{ (float) old('advance_payment', $booking->advance_payment ?? 0) }},
      bookingType: '{{ $bookingType }}',
      lunch: @js((bool) old('include_lunch', $booking->include_lunch ?? false)),
      drone: @js((bool) old('include_drone_videos', $booking->include_drone_videos ?? false)),
      lunchAmount: @js(old('lunch_charge', $booking->lunch_charge ?? '')),
      droneAmount: @js(old('drone_charge', $booking->drone_charge ?? '')),
      options: @js($inclusionOptions),
      selected: @js($selectedInclusions),
      customs: @js($customInclusions),
  })"
>
  @if ($globalSettings->is_safari_enabled)
    <div class="col-12">
      <label class="form-label d-block">Booking Type</label>
      <div class="btn-group w-100 flex-wrap" role="group" aria-label="Booking type">
        @foreach (\App\Models\Booking::BOOKING_TYPES as $value => $label)
          <input type="radio" class="btn-check" name="booking_type" id="bookingType_{{ $value }}" value="{{ $value }}" autocomplete="off" x-model="bookingType">
          <label class="btn btn-outline-primary" for="bookingType_{{ $value }}">{{ $label }}</label>
        @endforeach
      </div>
      @error('booking_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
  @else
    <input type="hidden" name="booking_type" value="accommodation_only">
  @endif

  {{-- Full Package: the hotel / accommodation where the guests stay (instead of one of the villa's rooms). --}}
  <div class="col-md-6" x-show="bookingType === 'full_package'" x-cloak>
    <label class="form-label" for="stayHotelName">Stay Hotel / Accommodation Name</label>
    <input id="stayHotelName" class="form-control @error('stay_hotel_name') is-invalid @enderror" name="stay_hotel_name" list="stayHotelOptions" value="{{ old('stay_hotel_name', $booking->stay_hotel_name ?? '') }}" placeholder="e.g. Cinnamon Wild, Yala" maxlength="255" :required="bookingType === 'full_package'">
    <datalist id="stayHotelOptions">
      @foreach (\App\Models\Booking::query()->whereNotNull('stay_hotel_name')->distinct()->orderBy('stay_hotel_name')->limit(50)->pluck('stay_hotel_name') as $hotel)
        <option value="{{ $hotel }}">
      @endforeach
    </datalist>
    @error('stay_hotel_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>

  <div class="col-md-6" x-show="bookingType === 'accommodation_only'">
    <label class="form-label">Room</label>
    <select class="form-select @error('room_id') is-invalid @enderror" name="room_id">
      <option value="">Select a room</option>
      @foreach ($rooms as $room)
        <option value="{{ $room->id }}" @selected((int) old('room_id', $booking->room_id ?? null) === $room->id)>{{ $room->name_or_number }} — {{ $room->type }}</option>
      @endforeach
    </select>
    @error('room_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>

  @if ($isEdit)
    <div class="col-md-6">
      <label class="form-label">Status</label>
      <select class="form-select @error('status') is-invalid @enderror" name="status">
        @foreach (['pending' => 'Pending', 'confirmed' => 'Confirmed', 'checked_out' => 'Checked out', 'cancelled' => 'Cancelled'] as $value => $label)
          <option value="{{ $value }}" @selected(old('status', $booking->status) === $value)>{{ $label }}</option>
        @endforeach
      </select>
      @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
  @endif

  {{-- Only for accommodation bookings - safari trips and full packages have no meal plan here. --}}
  <div class="col-md-6" x-show="bookingType === 'accommodation_only'">
    <label class="form-label">Board Basis / Meal Plan</label>
    <select class="form-select @error('board_type') is-invalid @enderror" name="board_type">
      @foreach (\App\Models\Booking::BOARD_TYPES as $value => $label)
        <option value="{{ $value }}" @selected(old('board_type', $booking->board_type ?? 'cabana_only') === $value)>{{ $label }}</option>
      @endforeach
    </select>
    @error('board_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>

  <div class="col-md-4">
    <label class="form-label">Customer name</label>
    <input class="form-control @error('customer_name') is-invalid @enderror" name="customer_name" value="{{ old('customer_name', $booking->customer_name ?? '') }}" placeholder="Sara Ahmed">
    @error('customer_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-4">
    <label class="form-label">Customer email</label>
    <input class="form-control @error('customer_email') is-invalid @enderror" type="email" name="customer_email" value="{{ old('customer_email', $booking->customer_email ?? '') }}" placeholder="name@example.com">
    @error('customer_email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-4">
    <label class="form-label">Customer phone</label>
    <input class="form-control @error('customer_phone') is-invalid @enderror" name="customer_phone" value="{{ old('customer_phone', $booking->customer_phone ?? '') }}" placeholder="+1 415 555 0100">
    @error('customer_phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>

  <div class="col-md-6" x-show="bookingType !== 'safari_only'">
    <label class="form-label">Check-in</label>
    <input class="form-control @error('check_in') is-invalid @enderror" type="date" name="check_in" value="{{ old('check_in', optional($booking->check_in ?? null)->toDateString()) }}">
    @error('check_in')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6" x-show="bookingType !== 'safari_only'">
    <label class="form-label">Check-out</label>
    <input class="form-control @error('check_out') is-invalid @enderror" type="date" name="check_out" value="{{ old('check_out', optional($booking->check_out ?? null)->toDateString()) }}">
    @error('check_out')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>

  <template x-if="bookingType !== 'accommodation_only'">
    <div class="col-12"><hr><h3 class="h6 mb-0"><i class="bi bi-compass"></i> Safari Details</h3></div>
  </template>
  <div class="col-md-5" x-show="bookingType !== 'accommodation_only'">
    <label class="form-label">Safari package / tour name</label>
    <input class="form-control @error('safari_tour_name') is-invalid @enderror" name="safari_tour_name" value="{{ old('safari_tour_name', $booking->safari_tour_name ?? '') }}" placeholder="Yala Full Day Jeep Safari">
    @error('safari_tour_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-3" x-show="bookingType !== 'accommodation_only'">
    <label class="form-label">Safari date</label>
    <input class="form-control @error('safari_date') is-invalid @enderror" type="date" name="safari_date" value="{{ old('safari_date', optional($booking->safari_date ?? null)->toDateString()) }}">
    @error('safari_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-4" x-show="bookingType !== 'accommodation_only'">
    <label class="form-label">Vehicle details</label>
    <input class="form-control @error('safari_vehicle_details') is-invalid @enderror" name="safari_vehicle_details" value="{{ old('safari_vehicle_details', $booking->safari_vehicle_details ?? '') }}" placeholder="Jeep #3 — Driver: Sunil">
    @error('safari_vehicle_details')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>

  {{-- Safari-only trip logistics --}}
  <template x-if="bookingType === 'safari_only' || bookingType === 'full_package'">
    <div class="col-12"><hr><h3 class="h6 mb-0"><i class="bi bi-geo-alt"></i> <span x-text="bookingType === 'safari_only' ? 'Safari Trip Details' : 'Package Trip Details'"></span></h3></div>
  </template>
  <div class="col-md-6" x-show="bookingType === 'safari_only'">
    <label class="form-label">Pickup location</label>
    <input class="form-control @error('pickup_location') is-invalid @enderror" name="pickup_location" value="{{ old('pickup_location', $booking->pickup_location ?? '') }}" placeholder="e.g. Star Moon Cabana, Kalupahana" maxlength="255">
    @error('pickup_location')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-3 col-6" x-show="bookingType === 'safari_only' || bookingType === 'full_package'">
    <label class="form-label">Pickup time</label>
    <input class="form-control @error('pickup_time') is-invalid @enderror" type="time" name="pickup_time" value="{{ old('pickup_time', $booking->pickup_time_short ?? '') }}">
    @error('pickup_time')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-3 col-6" x-show="bookingType === 'safari_only' || bookingType === 'full_package'">
    <label class="form-label">Group size (pax)</label>
    <input class="form-control @error('group_pax_size') is-invalid @enderror" type="number" min="1" max="500" step="1" inputmode="numeric" name="group_pax_size" value="{{ old('group_pax_size', $booking->group_pax_size ?? '') }}" placeholder="e.g. 4" :required="bookingType === 'safari_only' || bookingType === 'full_package'">
    @error('group_pax_size')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6" x-show="bookingType === 'safari_only'">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" role="switch" id="includeLunch" name="include_lunch" value="1" x-model="lunch">
      <label class="form-check-label" for="includeLunch">Include lunch</label>
    </div>
    <div class="mt-2" x-show="lunch" x-cloak>
      <label class="form-label" for="lunchCharge">Lunch Charge Amount (LKR)</label>
      <input id="lunchCharge" class="form-control @error('lunch_charge') is-invalid @enderror" type="number" min="0" step="0.01" inputmode="decimal" name="lunch_charge" x-model="lunchAmount" placeholder="0.00" :required="bookingType === 'safari_only' && lunch">
      @error('lunch_charge')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="col-md-6" x-show="bookingType === 'safari_only'">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" role="switch" id="includeDrone" name="include_drone_videos" value="1" x-model="drone">
      <label class="form-check-label" for="includeDrone">Include drone videos</label>
    </div>
    <div class="mt-2" x-show="drone" x-cloak>
      <label class="form-label" for="droneCharge">Drone Video Charge Amount (LKR)</label>
      <input id="droneCharge" class="form-control @error('drone_charge') is-invalid @enderror" type="number" min="0" step="0.01" inputmode="decimal" name="drone_charge" x-model="droneAmount" placeholder="0.00" :required="bookingType === 'safari_only' && drone">
      @error('drone_charge')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
  </div>

  {{-- Included services / features: printed on the guest invoice under "Includes / Services Provided". All booking types. --}}
  <div class="col-12">
    <hr class="my-1">
    <h3 class="h6 mb-2"><i class="bi bi-check2-square"></i> Included Services / Features</h3>
    <p class="text-muted small mb-2">Tick what is included for the guest. These are printed on the invoice and confirmation.</p>
    <div class="incl-grid">
      @foreach ($inclusionOptions as $option)
        @php $isSafariToggle = in_array($option, ['Lunch', 'Drone Videos'], true); @endphp
        <label class="incl-chip" @if ($isSafariToggle) x-show="bookingType !== 'safari_only'" @endif>
          <input type="checkbox" name="inclusions[]" value="{{ $option }}" x-model="selected">
          <span>{{ $option }}</span>
        </label>
      @endforeach
      <template x-for="(custom, index) in customs" :key="custom">
        <span class="incl-chip custom on">
          <input type="hidden" name="inclusions[]" :value="custom">
          <span x-text="custom"></span>
          <button type="button" class="incl-remove" @click="removeCustom(index)" :aria-label="'Remove ' + custom"><i class="bi bi-x-lg"></i></button>
        </span>
      </template>
    </div>
    <p class="text-muted small mt-2 mb-0" x-show="bookingType === 'safari_only'" x-cloak>Lunch and drone videos for a safari-only booking are set with their own switches above (so they can carry a charge).</p>
    <div class="incl-add">
      <input type="text" class="form-control" x-model="customInput" @keydown.enter.prevent="addCustom()" placeholder="Type a custom inclusion, e.g. Airport transfer" maxlength="100" aria-label="Custom inclusion">
      <button type="button" class="btn btn-light" @click="addCustom()" :disabled="!customInput.trim()"><i class="bi bi-plus-lg"></i> Add Custom Inclusion</button>
    </div>
    @error('inclusions')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>

  <div class="col-12"><hr class="my-1"><h3 class="h6 mb-0"><i class="bi bi-cash-coin"></i> Billing</h3></div>
  <div class="col-md-4">
    <label class="form-label">Total amount</label>
    <input class="form-control @error('total_amount') is-invalid @enderror" type="number" step="0.01" min="0" name="total_amount" x-model.number="total" placeholder="0.00">
    @error('total_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-4">
    <label class="form-label">Advance payment</label>
    <input class="form-control @error('advance_payment') is-invalid @enderror" type="number" step="0.01" min="0" name="advance_payment" x-model.number="advance" placeholder="0.00">
    @error('advance_payment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-4">
    <label class="form-label">Remaining balance</label>
    <input class="form-control" type="text" x-bind:value="balance" disabled>
    <small class="text-muted" x-show="extraCharges > 0" x-cloak>Includes <span x-text="extraCharges.toFixed(2)"></span> in lunch / drone extra charges.</small>
  </div>

  {{-- Internal staff reminders. Deliberately never rendered on guest invoices, image bills or messages. --}}
  <div class="col-12">
    <label class="form-label" for="specialNotes"><i class="bi bi-lock"></i> Special Notes <small class="text-muted">(Internal Use Only - Not Visible to Guest)</small></label>
    <textarea id="specialNotes" class="form-control @error('special_notes') is-invalid @enderror" name="special_notes" rows="3" maxlength="2000" placeholder="e.g. Guest is a VIP, prefers a quiet cabana, balance to be collected in cash…">{{ old('special_notes', $booking->special_notes ?? '') }}</textarea>
    <div class="form-text">These notes are for internal staff reminders only and will NEVER be shown on the guest's invoice or receipt.</div>
    @error('special_notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>

  <div class="col-12">
    <button class="btn btn-primary" type="submit">{{ $isEdit ? 'Save Changes' : 'Create Booking' }}</button>
    <a class="btn btn-light" href="{{ route('bookings.index') }}">Cancel</a>
  </div>
</div>

@push('scripts')
<script>
    function bookingForm(config) {
        return {
            bookingType: config.bookingType,
            total: config.total,
            advance: config.advance,
            options: config.options || [],
            selected: config.selected || [],
            customs: config.customs || [],
            customInput: '',
            addCustom() {
                const value = this.customInput.trim().replace(/\s+/g, ' ');
                if (!value) return;
                const lower = value.toLowerCase();
                const known = this.options.find((o) => o.toLowerCase() === lower);
                if (known) {
                    // Typing a pre-defined item just ticks it.
                    if (!this.selected.includes(known)) this.selected.push(known);
                } else if (!this.customs.some((c) => c.toLowerCase() === lower)) {
                    this.customs.push(value);
                }
                this.customInput = '';
            },
            removeCustom(index) {
                this.customs.splice(index, 1);
            },
            lunch: !!config.lunch,
            drone: !!config.drone,
            lunchAmount: config.lunchAmount,
            droneAmount: config.droneAmount,
            // Safari add-on prices are billed as extra charges on top of the total amount.
            get extraCharges() {
                if (this.bookingType !== 'safari_only') return 0;
                return (this.lunch ? parseFloat(this.lunchAmount) || 0 : 0) + (this.drone ? parseFloat(this.droneAmount) || 0 : 0);
            },
            get balance() {
                return (this.total + this.extraCharges - this.advance).toFixed(2);
            },
        };
    }
</script>
@endpush
