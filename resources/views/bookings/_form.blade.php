@php
    $isEdit = isset($booking);
@endphp
<div class="row g-3" x-data="{ total: {{ (float) old('total_amount', $booking->total_amount ?? 0) }}, advance: {{ (float) old('advance_payment', $booking->advance_payment ?? 0) }}, get balance() { return (this.total - this.advance).toFixed(2); } }">
  <div class="col-md-6">
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

  <div class="col-md-6">
    <label class="form-label">Check-in</label>
    <input class="form-control @error('check_in') is-invalid @enderror" type="date" name="check_in" value="{{ old('check_in', optional($booking->check_in ?? null)->toDateString()) }}">
    @error('check_in')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6">
    <label class="form-label">Check-out</label>
    <input class="form-control @error('check_out') is-invalid @enderror" type="date" name="check_out" value="{{ old('check_out', optional($booking->check_out ?? null)->toDateString()) }}">
    @error('check_out')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>

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
