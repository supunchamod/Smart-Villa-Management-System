@extends('layouts.app')

@section('title', 'Landing Page | '.$globalSettings->villa_name.' Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Landing Page</h1></li>
            </ol>
          </nav>
          <div class="page-actions">
            <a class="btn btn-light" href="{{ route('public.villa', \Illuminate\Support\Str::slug($globalSettings->villa_name)) }}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> View Public Page</a>
            <a class="btn btn-primary" href="{{ route('landing-page.cabana-types.create') }}"><i class="bi bi-plus-lg"></i> Add Cabana Type</a>
          </div>
        </div>
        @if (session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="row g-4">
          <div class="col-12">
            <div class="panel">
              <div class="panel-head"><div><h2>Cabana Types</h2><p>The cabanas shown on your public booking page, with their pax &amp; board tier pricing</p></div></div>
              <div class="table-responsive">
                <table class="table align-middle dash-table">
                  <thead>
                    <tr>
                      <th>Cabana</th>
                      <th>Max Capacity</th>
                      <th>Room Record</th>
                      <th>Pricing Tiers</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($cabanaTypes as $cabanaType)
                      <tr>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            @if ($cabanaType->image_url)
                              <img src="{{ asset($cabanaType->image_url) }}" alt="{{ $cabanaType->name }}" style="width:44px;height:44px;object-fit:cover;border-radius:8px;">
                            @endif
                            <strong>{{ $cabanaType->name }}</strong>
                          </div>
                        </td>
                        <td>{{ $cabanaType->max_capacity }} guests</td>
                        <td>
                          @if ($cabanaType->room)
                            <small class="text-muted">{{ $cabanaType->room->name_or_number }}</small>
                          @else
                            <span class="text-muted small">No room record yet - save this cabana type again to create one</span>
                          @endif
                        </td>
                        <td>
                          @if ($cabanaType->pricingTiers->isEmpty())
                            <span class="text-muted small">No tiers set</span>
                          @else
                            <div class="d-flex flex-column gap-1">
                              @foreach ($cabanaType->pricingTiers as $tier)
                                <small>
                                  {{ $tier->min_pax === $tier->max_pax ? $tier->min_pax : $tier->min_pax.'-'.$tier->max_pax }} Pax:
                                  @if ($tier->cabana_only_price !== null) Cabana {{ $globalSettings->currency }} {{ number_format($tier->cabana_only_price, 0) }} @endif
                                  @if ($tier->half_board_price !== null) &middot; HB {{ $globalSettings->currency }} {{ number_format($tier->half_board_price, 0) }} @endif
                                  @if ($tier->full_board_price !== null) &middot; FB {{ $globalSettings->currency }} {{ number_format($tier->full_board_price, 0) }} @endif
                                </small>
                              @endforeach
                            </div>
                          @endif
                        </td>
                        <td><span class="status {{ $cabanaType->is_active ? 'paid' : 'pending' }}">{{ $cabanaType->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                          <div class="d-flex gap-2">
                            <a class="btn btn-sm btn-light" href="{{ route('landing-page.cabana-types.edit', $cabanaType) }}" aria-label="Edit cabana type"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('landing-page.cabana-types.destroy', $cabanaType) }}" onsubmit="return confirm('Delete this cabana type? This cannot be undone.');">
                              @csrf
                              @method('DELETE')
                              <button class="btn btn-sm btn-light text-danger" type="submit" aria-label="Delete cabana type"><i class="bi bi-trash"></i></button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    @empty
                      <tr><td colspan="6" class="text-center text-muted py-4">No cabana types yet. Add your first one to start showing it on the public booking page.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-4 mt-1">
          <div class="col-12">
            <div class="panel">
              <div class="panel-head"><div><h2>Meal Menu Options</h2><p>Selectable items guests can choose per meal on the public booking page</p></div></div>
              <div class="row g-4">
                @foreach ($mealTypes as $mealType)
                  <div class="col-md-4">
                    <h3 class="h6 text-capitalize">{{ $mealType }}</h3>
                    <ul class="list-unstyled d-flex flex-column gap-2 mb-3">
                      @forelse ($menusByType->get($mealType, collect()) as $menuItem)
                        <li class="d-flex align-items-center justify-content-between gap-2">
                          <span>{{ $menuItem->item_name }}</span>
                          <form method="POST" action="{{ route('landing-page.menu-items.destroy', $menuItem) }}" onsubmit="return confirm('Remove this menu item?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-light text-danger" type="submit" aria-label="Remove item"><i class="bi bi-x-lg"></i></button>
                          </form>
                        </li>
                      @empty
                        <li class="text-muted small">No items yet.</li>
                      @endforelse
                    </ul>
                    <form method="POST" action="{{ route('landing-page.menu-items.store') }}" class="d-flex gap-2">
                      @csrf
                      <input type="hidden" name="meal_type" value="{{ $mealType }}">
                      <input class="form-control form-control-sm" type="text" name="item_name" maxlength="255" placeholder="Add {{ $mealType }} item" required>
                      <button class="btn btn-sm btn-light" type="submit"><i class="bi bi-plus-lg"></i></button>
                    </form>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
@endsection
