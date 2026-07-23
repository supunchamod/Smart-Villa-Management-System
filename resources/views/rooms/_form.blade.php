<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label">Room name / number</label>
    <input class="form-control @error('name_or_number') is-invalid @enderror" name="name_or_number" value="{{ old('name_or_number', $room->name_or_number ?? '') }}" placeholder="Cabana 12">
    @error('name_or_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6">
    <label class="form-label">Type</label>
    <input class="form-control @error('type') is-invalid @enderror" name="type" value="{{ old('type', $room->type ?? '') }}" placeholder="Deluxe Villa">
    @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6">
    <label class="form-label">Price per night</label>
    <input class="form-control @error('price_per_night') is-invalid @enderror" type="number" step="0.01" min="0" name="price_per_night" value="{{ old('price_per_night', $room->price_per_night ?? '') }}" placeholder="120.00">
    @error('price_per_night')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6">
    <label class="form-label">Guest capacity</label>
    <input class="form-control @error('capacity') is-invalid @enderror" type="number" step="1" min="1" max="20" name="capacity" value="{{ old('capacity', $room->capacity ?? 2) }}" placeholder="2">
    @error('capacity')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6">
    <label class="form-label">Photo URL <small class="text-muted">(optional, for the public booking page)</small></label>
    <input class="form-control @error('photo_url') is-invalid @enderror" type="url" name="photo_url" value="{{ old('photo_url', $room->photo_url ?? '') }}" placeholder="https://...">
    @error('photo_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6">
    <label class="form-label">Status</label>
    <select class="form-select @error('status') is-invalid @enderror" name="status">
      @foreach (['available' => 'Available', 'maintenance' => 'Maintenance'] as $value => $label)
        <option value="{{ $value }}" @selected(old('status', $room->status ?? 'available') === $value)>{{ $label }}</option>
      @endforeach
    </select>
    @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-12">
    <button class="btn btn-primary" type="submit">{{ isset($room) ? 'Save Changes' : 'Add Room' }}</button>
    <a class="btn btn-light" href="{{ route('rooms.index') }}">Cancel</a>
  </div>
</div>
