@php
    $existingTier = fn (array $bracket) => isset($cabanaType)
        ? $cabanaType->pricingTiers->first(fn ($tier) => $tier->min_pax === $bracket['min'])
        : null;
@endphp
<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label">Cabana name</label>
    <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $cabanaType->name ?? '') }}" placeholder="Vintage Couple Cabana">
    @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6">
    <label class="form-label">Max capacity</label>
    <input class="form-control @error('max_capacity') is-invalid @enderror" type="number" step="1" min="1" max="20" name="max_capacity" value="{{ old('max_capacity', $cabanaType->max_capacity ?? 2) }}">
    @error('max_capacity')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6">
    <label class="form-label">Cabana image <small class="text-muted">(optional - JPG, PNG or WEBP, up to 4MB)</small></label>
    <input class="form-control @error('image') is-invalid @enderror" type="file" name="image" accept="image/jpeg,image/png,image/webp">
    @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    @if (isset($cabanaType) && $cabanaType->image_url)
      <div class="d-flex align-items-center gap-2 mt-2">
        <img src="{{ asset($cabanaType->image_url) }}" alt="{{ $cabanaType->name }}" style="width:64px;height:64px;object-fit:cover;border-radius:8px;">
        <small class="text-muted">Current image - upload a new file to replace it.</small>
      </div>
    @endif
  </div>
  <div class="col-12">
    <label class="form-label">Description</label>
    <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3" placeholder="A cosy two-storey cabana perfect for families and groups...">{{ old('description', $cabanaType->description ?? '') }}</textarea>
    @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>
  <div class="col-12">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="is_active" value="1" @checked(old('is_active', $cabanaType->is_active ?? true))>
      <label class="form-check-label" for="is_active">Active (visible on the public booking page)</label>
    </div>
  </div>

  <div class="col-12">
    <hr>
    <label class="form-label mb-2">Pax &amp; board tier pricing <small class="text-muted">(leave a bracket's prices blank to hide it / that board type isn't offered)</small></label>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width:20%">Guest Tier</th>
            <th>Cabana Only</th>
            <th>Half Board</th>
            <th>Full Board</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($tierBrackets as $index => $bracket)
            @php $tier = $existingTier($bracket); @endphp
            <tr>
              <td><strong>{{ $bracket['label'] }}</strong></td>
              <td>
                <input class="form-control @error('tiers.'.$index.'.cabana_only_price') is-invalid @enderror" type="number" step="0.01" min="0" name="tiers[{{ $index }}][cabana_only_price]" value="{{ old('tiers.'.$index.'.cabana_only_price', $tier->cabana_only_price ?? '') }}" placeholder="e.g. 12500.00">
                @error('tiers.'.$index.'.cabana_only_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              </td>
              <td>
                <input class="form-control @error('tiers.'.$index.'.half_board_price') is-invalid @enderror" type="number" step="0.01" min="0" name="tiers[{{ $index }}][half_board_price]" value="{{ old('tiers.'.$index.'.half_board_price', $tier->half_board_price ?? '') }}" placeholder="optional">
                @error('tiers.'.$index.'.half_board_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              </td>
              <td>
                <input class="form-control @error('tiers.'.$index.'.full_board_price') is-invalid @enderror" type="number" step="0.01" min="0" name="tiers[{{ $index }}][full_board_price]" value="{{ old('tiers.'.$index.'.full_board_price', $tier->full_board_price ?? '') }}" placeholder="optional">
                @error('tiers.'.$index.'.full_board_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="col-12">
    <button class="btn btn-primary" type="submit">{{ isset($cabanaType) ? 'Save Changes' : 'Add Cabana Type' }}</button>
    <a class="btn btn-light" href="{{ route('landing-page.index') }}">Cancel</a>
  </div>
</div>
