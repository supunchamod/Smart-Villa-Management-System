@php
    $isEdit = isset($booking);
    $bookingType = old('booking_type', $booking->booking_type ?? 'accommodation_only');
@endphp
<div
  class="row g-3"
  x-data="bookingForm({
      total: {{ (float) old('total_amount', $booking->total_amount ?? 0) }},
      advance: {{ (float) old('advance_payment', $booking->advance_payment ?? 0) }},
      bookingType: '{{ $bookingType }}',
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

  <div class="col-md-6" x-show="bookingType !== 'safari_only'">
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

  <div class="col-md-6">
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

  <template x-if="bookingType === 'full_package'">
    <div class="col-12">
      <label class="form-label d-block">Integrated billing calculation <small class="text-muted">(optional helper, sums into Total Amount below)</small></label>
      <div class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label small text-muted">Accommodation amount</label>
          <input class="form-control" type="number" step="0.01" min="0" x-model.number="roomAmount" placeholder="0.00">
        </div>
        <div class="col-md-4">
          <label class="form-label small text-muted">Safari package amount</label>
          <input class="form-control" type="number" step="0.01" min="0" x-model.number="safariAmount" placeholder="0.00">
        </div>
        <div class="col-md-4">
          <button type="button" class="btn btn-light w-100" @click="applyCombinedTotal()"><i class="bi bi-calculator"></i> Sum into Total Amount</button>
        </div>
      </div>
    </div>
  </template>

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
            roomAmount: '',
            safariAmount: '',
            get balance() {
                return (this.total - this.advance).toFixed(2);
            },
            applyCombinedTotal() {
                const room = parseFloat(this.roomAmount) || 0;
                const safari = parseFloat(this.safariAmount) || 0;
                this.total = +(room + safari).toFixed(2);
            },
        };
    }
</script>
@endpush
