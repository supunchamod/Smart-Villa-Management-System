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
          <div class="col-12">
            <label class="form-label">Villa logo</label>
            <div class="d-flex align-items-center gap-3 mb-2">
              @if ($villa->logo)
                <img src="{{ asset('storage/'.$villa->logo) }}" alt="{{ $villa->name }}" style="width:56px;height:56px;object-fit:cover;border-radius:8px;">
              @endif
              <input class="form-control @error('logo') is-invalid @enderror" type="file" name="logo" accept="image/*">
            </div>
            @error('logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Villa name</label>
            <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $villa->name) }}">
            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Currency</label>
            <select class="form-select @error('currency') is-invalid @enderror" name="currency">
              @foreach (['USD', 'EUR', 'GBP', 'LKR', 'AUD'] as $code)
                <option value="{{ $code }}" @selected(old('currency', $villa->currency) === $code)>{{ $code }}</option>
              @endforeach
            </select>
            @error('currency')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone number</label>
            <input class="form-control @error('phone_number') is-invalid @enderror" name="phone_number" value="{{ old('phone_number', $villa->phone_number) }}">
            @error('phone_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Contact email</label>
            <input class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email', $villa->email) }}">
            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-12">
            <label class="form-label">Address</label>
            <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="3">{{ old('address', $villa->address) }}</textarea>
            @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
          </div>
          <div class="col-12"><button class="btn btn-primary" type="submit">Save Changes</button></div>
        </form></div></div><div class="col-xl-4"><div class="panel"><div class="panel-head"><div><h2>Activity Timeline</h2><p>Live operational events</p></div></div><div class="timeline"><div class="timeline-item"><span></span><div><strong>New enterprise lead assigned to CRM pipeline</strong><small>2 min ago</small></div></div><div class="timeline-item"><span></span><div><strong>Invoice #DS-1024 was paid successfully</strong><small>18 min ago</small></div></div><div class="timeline-item"><span></span><div><strong>Inventory alert triggered for Pro License</strong><small>43 min ago</small></div></div><div class="timeline-item"><span></span><div><strong>Q2 executive report generated</strong><small>1 hr ago</small></div></div></div></div></div></div>
@endsection
