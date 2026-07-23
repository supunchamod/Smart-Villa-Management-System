@extends('layouts.app')

@section('title', 'Settings | Dashora Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="index.html"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Settings</h1></li>
            </ol>
          </nav>
          <div class="page-actions"><button class="btn btn-primary" data-create-open data-create-type="settings" data-create-label="Save Preset"><i class="bi bi-plus-lg"></i> Save Preset</button></div>
        </div>
        <div class="row g-4"><div class="col-xl-8"><div class="panel"><div class="panel-head"><div><h2>Workspace Settings</h2><p>General preferences and account controls</p></div></div>
        @if (session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        <form class="row g-3" method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="section" value="general">
          <div class="col-12">
            <label class="form-label">Villa logo</label>
            <div class="d-flex align-items-center gap-3 mb-2">
              @if ($globalSettings->logo_url)
                <img src="{{ $globalSettings->logo_url }}" alt="{{ $globalSettings->villa_name }}" style="width:56px;height:56px;object-fit:cover;border-radius:8px;">
              @endif
              <input class="form-control @error('villa_logo') is-invalid @enderror" type="file" name="villa_logo" accept="image/*">
            </div>
            @error('villa_logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Villa name</label>
            <input class="form-control @error('villa_name') is-invalid @enderror" name="villa_name" value="{{ old('villa_name', $globalSettings->villa_name) }}">
            @error('villa_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Currency</label>
            <select class="form-select @error('currency') is-invalid @enderror" name="currency">
              @foreach (['USD', 'EUR', 'GBP', 'LKR', 'AUD'] as $code)
                <option value="{{ $code }}" @selected(old('currency', $globalSettings->currency) === $code)>{{ $code }}</option>
              @endforeach
            </select>
            @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone number</label>
            <input class="form-control @error('phone_number') is-invalid @enderror" name="phone_number" value="{{ old('phone_number', $globalSettings->phone_number) }}">
            @error('phone_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Contact email</label>
            <input class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email', $globalSettings->email) }}">
            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-12">
            <label class="form-label">Address</label>
            <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="3">{{ old('address', $globalSettings->address) }}</textarea>
            @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-12">
            <label class="form-label">Google Maps link</label>
            <input class="form-control @error('google_maps_link') is-invalid @enderror" type="url" name="google_maps_link" value="{{ old('google_maps_link', $globalSettings->google_maps_link) }}" placeholder="https://maps.google.com/...">
            <small class="text-muted">Shared with guests in the check-in day WhatsApp message.</small>
            @error('google_maps_link')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-12"><button class="btn btn-primary" type="submit">Save Changes</button></div>
        </form></div></div><div class="col-xl-4"><div class="panel"><div class="panel-head"><div><h2>Activity Timeline</h2><p>Live operational events</p></div></div><div class="timeline"><div class="timeline-item"><span></span><div><strong>New enterprise lead assigned to CRM pipeline</strong><small>2 min ago</small></div></div><div class="timeline-item"><span></span><div><strong>Invoice #DS-1024 was paid successfully</strong><small>18 min ago</small></div></div><div class="timeline-item"><span></span><div><strong>Inventory alert triggered for Pro License</strong><small>43 min ago</small></div></div><div class="timeline-item"><span></span><div><strong>Q2 executive report generated</strong><small>1 hr ago</small></div></div></div></div></div></div>

        <div class="row g-4 mt-1">
          <div class="col-12">
            <div class="panel">
              <div class="panel-head">
                <div><h2>Public Website Builder</h2><p>Controls the content of your public direct-booking mini-site</p></div>
                <a class="btn btn-sm btn-light" href="{{ route('public.villa', \Illuminate\Support\Str::slug($globalSettings->villa_name)) }}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> View Public Website</a>
              </div>
              <form class="row g-3" method="POST" action="{{ route('settings.update') }}">
                @csrf
                <input type="hidden" name="section" value="website">
                <div class="col-md-6">
                  <label class="form-label">Website logo URL</label>
                  <input class="form-control @error('website_logo_url') is-invalid @enderror" type="url" name="website_logo_url" value="{{ old('website_logo_url', $globalSettings->website_logo_url) }}" placeholder="https://...">
                  @error('website_logo_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                  <label class="form-label">Hero background image URL</label>
                  <input class="form-control @error('website_hero_image_url') is-invalid @enderror" type="url" name="website_hero_image_url" value="{{ old('website_hero_image_url', $globalSettings->website_hero_image_url) }}" placeholder="https://...">
                  @error('website_hero_image_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                  <label class="form-label">Hero title</label>
                  <input class="form-control @error('website_hero_title') is-invalid @enderror" name="website_hero_title" value="{{ old('website_hero_title', $globalSettings->website_hero_title) }}" placeholder="Welcome to {{ $globalSettings->villa_name }}">
                  @error('website_hero_title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                  <label class="form-label">Public WhatsApp number</label>
                  <input class="form-control @error('public_whatsapp_number') is-invalid @enderror" name="public_whatsapp_number" value="{{ old('public_whatsapp_number', $globalSettings->public_whatsapp_number) }}" placeholder="+94 77 123 4567">
                  @error('public_whatsapp_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                  <label class="form-label">Review link</label>
                  <input class="form-control @error('review_link') is-invalid @enderror" type="url" name="review_link" value="{{ old('review_link', $globalSettings->review_link) }}" placeholder="https://g.page/r/.../review">
                  <small class="text-muted">Sent to guests an hour after checkout.</small>
                  @error('review_link')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                  <label class="form-label">Hero subtitle / tagline</label>
                  <textarea class="form-control @error('website_hero_subtitle') is-invalid @enderror" name="website_hero_subtitle" rows="2" placeholder="A private cabana escape on Sri Lanka's coast...">{{ old('website_hero_subtitle', $globalSettings->website_hero_subtitle) }}</textarea>
                  @error('website_hero_subtitle')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                  <div class="alert alert-light border mb-0 d-flex align-items-center justify-content-between gap-3">
                    <span><i class="bi bi-info-circle"></i> Cabana types, pax pricing tiers, and meal menu options now live in <strong>Landing Page</strong>.</span>
                    <a class="btn btn-sm btn-light" href="{{ route('landing-page.index') }}">Open Landing Page</a>
                  </div>
                </div>
                <div class="col-12"><button class="btn btn-primary" type="submit">Save Website Settings</button></div>
              </form>
            </div>
          </div>
        </div>
@endsection
