@extends('layouts.app')

@section('title', 'Search Results | '.$globalSettings->villa_name.' Admin Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/search-results.css') }}">
@endpush

@section('content')
<div class="sr">
  <div class="page-title">
    <nav class="page-breadcrumb" aria-label="breadcrumb">
      <ol>
        <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
        <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
        <li aria-current="page"><h1>Search Results</h1></li>
      </ol>
    </nav>
  </div>

  <div class="sr-header">
    <span class="sr-accent-bar" aria-hidden="true"></span>
    <span class="sr-eyebrow">Global Search</span>
    @if ($query !== '')
      <h2 class="sr-title">Search Results for: &ldquo;{{ $query }}&rdquo;</h2>
      <p class="sr-sub">{{ $totalCount }} matching {{ $totalCount === 1 ? 'record' : 'records' }} found</p>
    @else
      <h2 class="sr-title">Search Villa Cabana</h2>
      <p class="sr-sub">Type a guest name, booking reference, cabana, or transaction ID above to get started.</p>
    @endif
  </div>

  @if ($query !== '' && $totalCount === 0)
    <div class="sr-empty">
      <div class="sr-empty-icon"><i class="bi bi-search"></i></div>
      <h3>No matching records found.</h3>
      <p>Try a different guest name, booking reference, room, or transaction ID.</p>
    </div>
  @elseif ($totalCount > 0)
    @if ($bookings->isNotEmpty())
      <div class="sr-panel mb-4">
        <div class="sr-panel-head">
          <div><h2>Found Bookings</h2><p>Guests &amp; reservations matching &ldquo;{{ $query }}&rdquo;</p></div>
          <span class="sr-count-badge">{{ $bookings->count() }}</span>
        </div>
        <div class="table-responsive">
          <table class="table align-middle sr-table">
            <thead>
              <tr>
                <th>Reference</th>
                <th>Guest</th>
                <th>Room</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($bookings as $booking)
                @php
                  $badge = ['pending' => 'pending', 'confirmed' => 'new', 'checked_out' => 'won', 'cancelled' => 'stuck'][$booking->status] ?? 'new';
                @endphp
                <tr>
                  <td><a href="{{ route('bookings.show', $booking) }}" class="sr-link">BKG-{{ str_pad($booking->id, 6, '0', STR_PAD_LEFT) }}</a></td>
                  <td><strong>{{ $booking->customer_name }}</strong></td>
                  <td>{{ $booking->room->name_or_number }}</td>
                  <td>{{ $booking->check_in->format('d M Y') }}</td>
                  <td>{{ $booking->check_out->format('d M Y') }}</td>
                  <td><span class="deal-badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endif

    @if ($rooms->isNotEmpty())
      <div class="sr-panel mb-4">
        <div class="sr-panel-head">
          <div><h2>Found Cabanas &amp; Rooms</h2><p>Room profiles matching &ldquo;{{ $query }}&rdquo;</p></div>
          <span class="sr-count-badge">{{ $rooms->count() }}</span>
        </div>
        <div class="sr-room-grid">
          @foreach ($rooms as $room)
            @php
              $roomBadge = ['available' => 'high', 'maintenance' => 'low'][$room->status] ?? 'standard';
            @endphp
            <a href="{{ route('rooms.edit', $room) }}" class="sr-room-card">
              <strong>{{ $room->name_or_number }}</strong>
              <small>{{ $room->type }}</small>
              <span class="sr-status-badge sr-status-{{ $roomBadge }}">{{ ucfirst($room->status) }}</span>
            </a>
          @endforeach
        </div>
      </div>
    @endif

    @if ($transactions->isNotEmpty())
      @php $canOpenBooking = auth()->user()->can('manage_bookings'); @endphp
      <div class="sr-panel mb-4">
        <div class="sr-panel-head">
          <div><h2>Found Transactions</h2><p>Financial records matching &ldquo;{{ $query }}&rdquo;</p></div>
          <span class="sr-count-badge">{{ $transactions->count() }}</span>
        </div>
        <div class="table-responsive">
          <table class="table align-middle sr-table">
            <thead>
              <tr>
                <th>Transaction ID</th>
                <th>Date</th>
                <th>Guest</th>
                <th>Type</th>
                <th>Amount</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($transactions as $event)
                <tr>
                  <td>
                    @if ($canOpenBooking)
                      <a href="{{ route('bookings.show', $event['booking']) }}" class="sr-link">{{ $event['transaction_id'] }}</a>
                    @else
                      <strong>{{ $event['transaction_id'] }}</strong>
                    @endif
                  </td>
                  <td>{{ $event['date']->format('d M Y') }}</td>
                  <td>{{ $event['booking']->customer_name }}</td>
                  <td>{{ $event['type'] }}</td>
                  <td>{{ $globalSettings->currency }} {{ number_format($event['amount'], 2) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endif
  @endif
</div>
@endsection
