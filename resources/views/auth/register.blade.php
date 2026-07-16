@extends('layouts.auth')

@section('title', 'Register | Dashora Admin Dashboard')

@section('content')
  <main class="auth-page auth-cover-page">
    <section class="auth-cover-visual">
      <a class="brand auth-brand" href="index.html"><span class="brand-mark">D</span><div><strong>Dashora</strong><small>Admin Suite</small></div></a>
      <div class="auth-device">
        <div class="auth-device-top"><span></span><span></span><span></span></div>
        <div class="auth-device-body">
          <aside><i></i><i></i><i></i><i></i></aside>
          <div class="auth-device-screen"><div class="auth-chart"></div><div class="auth-bars"><i></i><i></i><i></i><i></i></div><div class="auth-mini-grid"><span></span><span></span><span></span></div></div>
        </div>
      </div>
      <div class="auth-cover-copy"><span class="eyebrow">Premium Admin Template</span><h2>Build polished SaaS dashboards faster.</h2><p>Dashora includes responsive layouts, RTL support, dark mode, charts, tables, forms, auth pages, and utility screens.</p></div>
    </section>
    <section class="auth-form-panel">
      <div class="auth-form-wrap">
        <a class="brand auth-form-brand" href="index.html"><span class="brand-mark">D</span><div><strong>Dashora</strong><small>Admin Suite</small></div></a>
        <h1>Start your Dashora workspace</h1>
        <p>Create your account and open the full admin workspace.</p>
        <form method="POST" action="{{ route('register') }}">
          @csrf
          <label class="form-label">Villa / property name</label>
          <input class="form-control mb-3 @error('villa_name') is-invalid @enderror" name="villa_name" id="villa_name" value="{{ old('villa_name') }}" placeholder="Sunset Villa Resort" required autofocus>
          @error('villa_name')<div class="invalid-feedback d-block mb-3">{{ $message }}</div>@enderror
          <label class="form-label">Full name</label>
          <input class="form-control mb-3 @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name') }}" placeholder="Sara Ahmed" required>
          @error('name')<div class="invalid-feedback d-block mb-3">{{ $message }}</div>@enderror
          <label class="form-label">Email address</label>
          <input class="form-control mb-3 @error('email') is-invalid @enderror" type="email" name="email" id="email" value="{{ old('email') }}" placeholder="name@example.com" required>
          @error('email')<div class="invalid-feedback d-block mb-3">{{ $message }}</div>@enderror
          <label class="form-label">Password</label>
          <input class="form-control mb-2 @error('password') is-invalid @enderror" type="password" name="password" id="password" placeholder="Password" required>
          @error('password')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
          <label class="form-label">Confirm password</label>
          <input class="form-control mb-2" type="password" name="password_confirmation" id="password_confirmation" placeholder="Confirm password" required>
          <label class="auth-terms"><input type="checkbox" checked required> I agree to privacy policy and terms</label>
          <button class="btn btn-primary w-100" type="submit">Create Account</button>
        </form>
        <div class="auth-divider"><span>or continue with</span></div><div class="auth-social"><button type="button"><i class="bi bi-google"></i></button><button type="button"><i class="bi bi-github"></i></button><button type="button"><i class="bi bi-linkedin"></i></button></div>
        <div class="auth-links">Already have an account? <a href="{{ route('login') }}">Sign in</a></div>
      </div>
    </section>
  </main>
@endsection
