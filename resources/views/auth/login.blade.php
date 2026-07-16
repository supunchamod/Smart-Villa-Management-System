@extends('layouts.auth')

@section('title', 'Login | Dashora Admin Dashboard')

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
        <h1>Welcome back to Dashora</h1>
        <p>Sign in to continue managing dashboards, reports, teams, and customer workflows.</p>
        <form method="POST" action="{{ route('login') }}">
          @csrf
          <label class="form-label">Email address</label>
          <input class="form-control mb-3 @error('email') is-invalid @enderror" type="email" name="email" id="email" value="{{ old('email') }}" placeholder="name@example.com" required autofocus>
          @error('email')<div class="invalid-feedback d-block mb-3">{{ $message }}</div>@enderror
          <label class="form-label">Password</label>
          <input class="form-control mb-2" type="password" name="password" id="password" placeholder="Password" required>
          <div class="auth-options"><label><input type="checkbox" name="remember" checked> Remember me</label><a href="forgot-password.html">Forgot password?</a></div>
          <button class="btn btn-primary w-100" type="submit">Sign In</button>
        </form>
        <div class="auth-divider"><span>or continue with</span></div><div class="auth-social"><button type="button"><i class="bi bi-google"></i></button><button type="button"><i class="bi bi-github"></i></button><button type="button"><i class="bi bi-linkedin"></i></button></div>
        <div class="auth-links"><span>New on Dashora?</span><a href="{{ route('register') }}">Create an account</a></div>
      </div>
    </section>
  </main>
@endsection
