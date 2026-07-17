@extends('layouts.app')

@section('title', 'Bookings | Dashora Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Bookings</h1></li>
            </ol>
          </nav>
          <div class="page-actions"><a class="btn btn-primary" href="{{ route('bookings.create') }}"><i class="bi bi-plus-lg"></i> Add Booking</a></div>
        </div>
        @if (session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <div class="row g-4">
          <div class="col-12">
            <div class="panel" x-data="{ tab: 'all' }">
              <div class="panel-head"><div><h2>Bookings</h2><p>Reservations across all rooms and cabanas</p></div></div>

              <div class="booking-tabs" role="tablist" aria-label="Filter bookings by status">
                <button type="button" class="booking-tab" role="tab" :class="{ active: tab === 'all' }" :aria-selected="tab === 'all'" @click="tab = 'all'">All Bookings</button>
                <button type="button" class="booking-tab" role="tab" :class="{ active: tab === 'active' }" :aria-selected="tab === 'active'" @click="tab = 'active'">Active / Pending</button>
                <button type="button" class="booking-tab" role="tab" :class="{ active: tab === 'completed' }" :aria-selected="tab === 'completed'" @click="tab = 'completed'">Completed</button>
                <button type="button" class="booking-tab" role="tab" :class="{ active: tab === 'cancelled' }" :aria-selected="tab === 'cancelled'" @click="tab = 'cancelled'">Cancelled</button>
              </div>

              <div class="d-none d-md-block">
                <div class="table-responsive">
                  <table class="table align-middle dash-table">
                    <thead>
                      <tr>
                        <th>Customer</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Total</th>
                        <th>Advance</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($bookings as $booking)
                        @php
                          $tabKey = ['checked_out' => 'completed', 'cancelled' => 'cancelled'][$booking->status] ?? 'active';
                          $badge = ['confirmed' => 'new', 'checked_out' => 'won', 'cancelled' => 'stuck'][$booking->status] ?? 'new';
                        @endphp
                        <tr x-show="tab === 'all' || tab === '{{ $tabKey }}'">
                          <td><strong>{{ $booking->customer_name }}</strong></td>
                          <td>{{ $booking->room->name_or_number }}</td>
                          <td>{{ $booking->check_in->format('d M Y') }}</td>
                          <td>{{ $booking->check_out->format('d M Y') }}</td>
                          <td>{{ number_format($booking->total_amount, 2) }}</td>
                          <td>{{ number_format($booking->advance_payment, 2) }}</td>
                          <td>{{ number_format($booking->remaining_balance, 2) }}</td>
                          <td>
                            <span class="deal-badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                          </td>
                          <td>
                            <div class="d-flex gap-2">
                              <a class="btn btn-sm btn-light" href="{{ route('bookings.show', $booking) }}" aria-label="View booking"><i class="bi bi-eye"></i></a>
                              <a class="btn btn-sm btn-light" href="{{ route('bookings.edit', $booking) }}" aria-label="Edit booking"><i class="bi bi-pencil"></i></a>
                              @if ($booking->status !== 'cancelled')
                                <a class="btn btn-sm btn-light" href="{{ route($booking->status === 'checked_out' ? 'bookings.invoice.final' : 'bookings.invoice.confirmation', $booking) }}" target="_blank" aria-label="Download invoice"><i class="bi bi-file-earmark-pdf"></i></a>
                              @endif
                              <form method="POST" action="{{ route('bookings.destroy', $booking) }}" onsubmit="return confirm('Delete this booking?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-light text-danger" type="submit" aria-label="Delete booking"><i class="bi bi-trash"></i></button>
                              </form>
                            </div>
                          </td>
                        </tr>
                      @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No bookings yet. Add your first booking to get started.</td></tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="d-block d-md-none">
                <div class="mdash-booking-list">
                  @forelse ($bookings as $booking)
                    @php
                      $tabKey = ['checked_out' => 'completed', 'cancelled' => 'cancelled'][$booking->status] ?? 'active';
                      $badge = ['confirmed' => 'new', 'checked_out' => 'won', 'cancelled' => 'stuck'][$booking->status] ?? 'new';
                    @endphp
                    <div class="mdash-booking-card stacked" x-show="tab === 'all' || tab === '{{ $tabKey }}'">
                      <div class="mdash-card-top">
                        <span class="mdash-avatar sm">{{ $booking->customer_initials }}</span>
                        <div class="mdash-booking-info">
                          <strong>{{ $booking->customer_name }}</strong>
                          <small>{{ $booking->room->name_or_number }} &middot; {{ $booking->check_in->format('d M') }} - {{ $booking->check_out->format('d M Y') }}</small>
                        </div>
                        <span class="deal-badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                      </div>

                      <div class="mdash-card-meta">
                        <span>Total <strong>{{ number_format($booking->total_amount, 2) }}</strong></span>
                        <span>Balance <strong>{{ number_format($booking->remaining_balance, 2) }}</strong></span>
                      </div>

                      <div class="mdash-card-actions">
                        <a class="mdash-icon-btn" href="{{ route('bookings.show', $booking) }}" aria-label="View booking"><i class="bi bi-eye"></i></a>
                        <a class="mdash-icon-btn" href="{{ route('bookings.edit', $booking) }}" aria-label="Edit booking"><i class="bi bi-pencil"></i></a>
                        @if ($booking->status !== 'cancelled')
                          <a
                            class="mdash-icon-btn primary"
                            href="{{ route($booking->status === 'checked_out' ? 'bookings.invoice.final' : 'bookings.invoice.confirmation', $booking) }}"
                            target="_blank"
                            rel="noopener"
                            aria-label="Download {{ $booking->status === 'checked_out' ? 'final' : 'confirmation' }} invoice"
                          ><i class="bi bi-file-earmark-pdf"></i></a>
                        @endif
                        <form method="POST" action="{{ route('bookings.destroy', $booking) }}" onsubmit="return confirm('Delete this booking?');">
                          @csrf
                          @method('DELETE')
                          <button class="mdash-icon-btn danger" type="submit" aria-label="Delete booking"><i class="bi bi-trash"></i></button>
                        </form>
                      </div>
                    </div>
                  @empty
                    <p class="mdash-empty">No bookings yet. Add your first booking to get started.</p>
                  @endforelse
                </div>
              </div>

              @if ($bookings->hasPages())
                <div class="mt-3">{{ $bookings->links() }}</div>
              @endif
            </div>
          </div>
        </div>
@endsection
