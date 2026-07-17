@extends('layouts.app')

@section('title', 'Booking Details | Dashora Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li><a href="{{ route('bookings.index') }}">Bookings</a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>{{ $booking->customer_name }}</h1></li>
            </ol>
          </nav>
          <div class="page-actions booking-detail-actions" x-data>
            <a class="btn btn-light" href="{{ route('bookings.edit', $booking) }}"><i class="bi bi-pencil"></i> Edit</a>
            @if ($booking->status !== 'cancelled')
              <a class="btn btn-light" href="{{ route('bookings.invoice.confirmation', $booking) }}" target="_blank"><i class="bi bi-file-earmark-pdf"></i> Confirmation Invoice</a>
            @endif
            @if ($booking->status === 'checked_out')
              <a class="btn btn-light" href="{{ route('bookings.invoice.final', $booking) }}" target="_blank"><i class="bi bi-file-earmark-check"></i> Final Invoice</a>
            @endif
            @if ($booking->status === 'confirmed')
              <button type="button" class="btn btn-primary" @click="bootstrap.Modal.getOrCreateInstance(document.getElementById('checkoutModal')).show()"><i class="bi bi-box-arrow-right"></i> Checkout</button>
            @endif
          </div>
        </div>

        @if (session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row g-4 booking-detail-grid">
          <div class="col-xl-7">
            <div class="panel">
              <div class="panel-head"><div><h2>Booking Details</h2><p>Customer and stay information</p></div></div>
              <div class="row g-3 booking-info-grid">
                <div class="col-md-6"><small class="text-muted d-block">Customer</small><strong>{{ $booking->customer_name }}</strong></div>
                <div class="col-md-6"><small class="text-muted d-block">Room</small><strong>{{ $booking->room->name_or_number }} ({{ $booking->room->type }})</strong></div>
                <div class="col-md-6"><small class="text-muted d-block">Email</small><strong>{{ $booking->customer_email ?: '—' }}</strong></div>
                <div class="col-md-6"><small class="text-muted d-block">Phone</small><strong>{{ $booking->customer_phone ?: '—' }}</strong></div>
                <div class="col-md-6"><small class="text-muted d-block">Check-in</small><strong>{{ $booking->check_in->format('d M Y') }}</strong></div>
                <div class="col-md-6"><small class="text-muted d-block">Check-out</small><strong>{{ $booking->check_out->format('d M Y') }}</strong></div>
                <div class="col-md-6">
                  <small class="text-muted d-block">Status</small>
                  @php
                    $badge = ['confirmed' => 'new', 'checked_out' => 'won', 'cancelled' => 'stuck'][$booking->status] ?? 'new';
                  @endphp
                  <span class="deal-badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                </div>
              </div>
            </div>
          </div>
          <div class="col-xl-5">
            <div class="panel booking-detail-balance">
              <div class="panel-head"><div><h2>Payment Summary</h2><p>Total, advance, and outstanding balance</p></div></div>
              <div class="balance-grid">
                <div class="balance-stat"><span>Total Amount</span><strong>{{ number_format($booking->total_amount, 2) }}</strong></div>
                <div class="balance-stat"><span>Advance Payment</span><strong>{{ number_format($booking->advance_payment, 2) }}</strong></div>
                <div class="balance-stat"><span>Remaining Balance</span><strong>{{ number_format($booking->remaining_balance, 2) }}</strong></div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content dash-modal">
              <div class="modal-header">
                <div><span class="eyebrow">Confirm Checkout</span><h2 class="modal-title">Mark this booking as fully paid?</h2></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <p>This confirms the remaining balance of <strong>{{ number_format($booking->remaining_balance, 2) }}</strong> has been received from {{ $booking->customer_name }}. The booking will be marked as checked out and the final invoice will be generated.</p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ route('bookings.checkout', $booking) }}">
                  @csrf
                  <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> Yes, Payment Received</button>
                </form>
              </div>
            </div>
          </div>
        </div>
@endsection
