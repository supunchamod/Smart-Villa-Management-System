@extends('layouts.auth')

@section('title', 'Login | ' . ($globalSettings->villa_name ?? 'Smart Cabana'))

@push('styles')
  <link href="{{ asset('assets/css/villa-auth.css') }}" rel="stylesheet">
@endpush

@section('content')
  <main class="villa-auth-page">
    <div class="villa-auth-grid">
      <section class="villa-auth-showcase">
        <div class="villa-auth-brand">
          <span class="villa-auth-brand-mark">
            @if ($globalSettings->logo_url)
              <img src="{{ $globalSettings->logo_url }}" alt="{{ $globalSettings->villa_name }}">
            @else
              {{ strtoupper(substr($globalSettings->villa_name ?? 'S', 0, 1)) }}
            @endif
          </span>
          <div class="villa-auth-brand-name">
            <strong>{{ $globalSettings->villa_name ?? 'Smart Cabana' }}</strong>
            <small>Villa Management Suite</small>
          </div>
        </div>

        <div class="villa-auth-showcase-copy">
          <span class="villa-auth-eyebrow">Welcome back</span>
          <h2>Run your villa like a five-star brand.</h2>
          <p>Everything you need to manage bookings, guests, and revenue from one calm, connected dashboard.</p>
        </div>

        <div class="villa-auth-features">
          <div class="villa-auth-feature-card">
            <span class="villa-auth-feature-icon"><i class="bi bi-calendar2-check"></i></span>
            <div>
              <strong>Zero Double Bookings</strong>
              <p>Real-time calendar sync keeps every reservation conflict-free.</p>
            </div>
          </div>
          <div class="villa-auth-feature-card">
            <span class="villa-auth-feature-icon"><i class="bi bi-whatsapp"></i></span>
            <div>
              <strong>Automated WhatsApp Confirmations</strong>
              <p>Guests get instant booking and payment confirmations, automatically.</p>
            </div>
          </div>
          <div class="villa-auth-feature-card">
            <span class="villa-auth-feature-icon"><i class="bi bi-graph-up-arrow"></i></span>
            <div>
              <strong>Real-Time Profit Analytics</strong>
              <p>Track income, expenses, and net profit as they happen.</p>
            </div>
          </div>
        </div>
      </section>

      <section class="villa-auth-form-panel">
        <div class="villa-auth-form-wrap">
          <div class="villa-auth-mobile-brand">
            <span class="villa-auth-brand-mark">
              @if ($globalSettings->logo_url)
                <img src="{{ $globalSettings->logo_url }}" alt="{{ $globalSettings->villa_name }}">
              @else
                {{ strtoupper(substr($globalSettings->villa_name ?? 'S', 0, 1)) }}
              @endif
            </span>
            <div>
              <strong>{{ $globalSettings->villa_name ?? 'Smart Cabana' }}</strong>
              <small>Villa Management Suite</small>
            </div>
          </div>

          <div class="villa-auth-form-head">
            <h1>Welcome back</h1>
            <p>Sign in to manage bookings, guests, and revenue.</p>
          </div>

          @if (session('status'))
            <div class="villa-auth-alert villa-auth-alert-success">
              <i class="bi bi-check-circle"></i>
              <span>{{ session('status') }}</span>
            </div>
          @endif

          <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="villa-auth-field">
              <label class="villa-auth-label" for="email">Email address</label>
              <div class="villa-auth-input-wrap @error('email') is-invalid @enderror">
                <span class="villa-auth-input-icon"><i class="bi bi-envelope"></i></span>
                <input
                  class="villa-auth-input"
                  type="email"
                  name="email"
                  id="email"
                  value="{{ old('email') }}"
                  placeholder="you@example.com"
                  autocomplete="email"
                  required
                  autofocus
                >
              </div>
              @error('email')
                <p class="villa-auth-field-error">{{ $message }}</p>
              @enderror
            </div>

            <div class="villa-auth-field" x-data="{ showPassword: false }">
              <label class="villa-auth-label" for="password">Password</label>
              <div class="villa-auth-input-wrap @error('password') is-invalid @enderror">
                <span class="villa-auth-input-icon"><i class="bi bi-lock"></i></span>
                <input
                  class="villa-auth-input"
                  :type="showPassword ? 'text' : 'password'"
                  name="password"
                  id="password"
                  placeholder="Enter your password"
                  autocomplete="current-password"
                  required
                >
                <button
                  type="button"
                  class="villa-auth-toggle-visibility"
                  @click="showPassword = !showPassword"
                  :aria-label="showPassword ? 'Hide password' : 'Show password'"
                >
                  <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                </button>
              </div>
              @error('password')
                <p class="villa-auth-field-error">{{ $message }}</p>
              @enderror
            </div>

            <div class="villa-auth-remember">
              <input type="checkbox" name="remember" id="remember">
              <label for="remember">Remember me for 30 days</label>
            </div>

            <button type="submit" class="villa-auth-submit">
              Sign in <i class="bi bi-arrow-right"></i>
            </button>
          </form>

          <p class="villa-auth-switch">Don't have an account? <a href="{{ route('register') }}">Create one</a></p>
        </div>
      </section>
    </div>
  </main>
@endsection
