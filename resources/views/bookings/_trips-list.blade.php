{{-- Scheduled safari trips (date, package, pickup time, pax, notes). Props: $trips --}}
@if ($trips->isEmpty())
  <p class="text-muted small mb-0">No safari trips scheduled.</p>
@else
  <ul class="trip-list-view">
    @foreach ($trips as $trip)
      <li>
        <div class="trip-date"><i class="bi bi-calendar-event"></i> {{ $trip->safari_date->format('D, d M Y') }}</div>
        <div class="trip-main">
          <strong>{{ $trip->tour_name }}</strong>
          <small>
            @if ($trip->pickup_time)<span><i class="bi bi-clock"></i> Pickup {{ $trip->pickup_time_short }}</span>@endif
            <span><i class="bi bi-people"></i> {{ $trip->pax_count }} pax</span>
          </small>
          @if ($trip->notes)<small>{{ $trip->notes }}</small>@endif
        </div>
      </li>
    @endforeach
  </ul>
@endif
