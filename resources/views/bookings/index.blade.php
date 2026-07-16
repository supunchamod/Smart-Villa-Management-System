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
        <div class="row g-4">
          <div class="col-12">
            <div class="panel">
              <div class="panel-head"><div><h2>Bookings</h2><p>Reservations across all rooms and cabanas</p></div></div>
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
                      <tr>
                        <td><strong>{{ $booking->customer_name }}</strong></td>
                        <td>{{ $booking->room->name_or_number }}</td>
                        <td>{{ $booking->check_in->format('d M Y') }}</td>
                        <td>{{ $booking->check_out->format('d M Y') }}</td>
                        <td>{{ number_format($booking->total_amount, 2) }}</td>
                        <td>{{ number_format($booking->advance_payment, 2) }}</td>
                        <td>{{ number_format($booking->total_amount - $booking->advance_payment, 2) }}</td>
                        <td>
                          @php
                            $badge = ['confirmed' => 'new', 'checked_out' => 'won', 'cancelled' => 'stuck'][$booking->status] ?? 'new';
                          @endphp
                          <span class="deal-badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                        </td>
                        <td>
                          <div class="d-flex gap-2">
                            <a class="btn btn-sm btn-light" href="{{ route('bookings.edit', $booking) }}" aria-label="Edit booking"><i class="bi bi-pencil"></i></a>
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
              @if ($bookings->hasPages())
                <div class="mt-3">{{ $bookings->links() }}</div>
              @endif
            </div>
          </div>
        </div>
@endsection
