@php
    $openSection = match (true) {
        request()->routeIs('dashboard') => 'dashboards',
        request()->routeIs('calendar') => 'apps',
        request()->routeIs('bookings.*') => 'commerce',
        request()->routeIs(['income.index', 'expenses.index', 'profit.index', 'reports', 'reports.generate']) => 'finance',
        request()->routeIs(['settings', 'team']) => 'pages',
        default => null,
    };
@endphp
<aside class="mini-rail" aria-label="Quick navigation">
    <a href="{{ route('settings') }}" aria-label="Settings"><i class="bi bi-gear"></i></a>
  </aside>
    <aside class="sidebar" id="sidebar">
      <a class="brand" href="{{ route('dashboard') }}" aria-label="{{ $globalSettings->villa_name }} home">
        @if ($globalSettings->villa_logo)
          <span class="brand-mark"><img src="{{ asset('storage/'.$globalSettings->villa_logo) }}" alt="{{ $globalSettings->villa_name }}" style="width:100%;height:100%;object-fit:cover;border-radius:8px;"></span>
        @else
          <span class="brand-mark">{{ strtoupper(substr($globalSettings->villa_name, 0, 1)) }}</span>
        @endif
        <div><strong>{{ $globalSettings->villa_name }}</strong><small>Admin Suite</small></div>
      </a>
      <nav class="sidebar-nav">
        <div class="nav-section nav-accordion {{ $openSection === 'dashboards' ? 'open' : '' }}">
          <button class="nav-accordion-toggle" type="button" data-nav-accordion aria-expanded="{{ $openSection === 'dashboards' ? 'true' : 'false' }}"><span><i class="bi bi-speedometer2"></i>Dashboards</span><i class="bi bi-chevron-down"></i></button>
          <div class="nav-accordion-panel">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>

          </div>
        </div>

        @can('manage_bookings')
        <div class="nav-section nav-accordion {{ $openSection === 'apps' ? 'open' : '' }}">
          <button class="nav-accordion-toggle" type="button" data-nav-accordion aria-expanded="{{ $openSection === 'apps' ? 'true' : 'false' }}"><span><i class="bi bi-grid"></i>Apps</span><i class="bi bi-chevron-down"></i></button>
          <div class="nav-accordion-panel">
            <a class="nav-link {{ request()->routeIs('calendar') ? 'active' : '' }}" href="{{ route('calendar') }}"><i class="bi bi-calendar3"></i><span>Bookings Calendar</span></a>

          </div>
        </div>
        @endcan

        @can('manage_bookings')
        <div class="nav-section nav-accordion {{ $openSection === 'commerce' ? 'open' : '' }}">
          <button class="nav-accordion-toggle" type="button" data-nav-accordion aria-expanded="{{ $openSection === 'commerce' ? 'true' : 'false' }}"><span><i class="bi bi-bag"></i>Commerce</span><i class="bi bi-chevron-down"></i></button>
          <div class="nav-accordion-panel">
            <a class="nav-link {{ request()->routeIs('bookings.create') ? 'active' : '' }}" href="{{ route('bookings.create') }}"><i class="bi bi-journal-plus"></i><span>Add Bookings</span></a>
            <a class="nav-link {{ request()->routeIs('bookings.*') && ! request()->routeIs('bookings.create') ? 'active' : '' }}" href="{{ route('bookings.index') }}"><i class="bi bi-journal-check"></i><span>Manage Bookings</span></a>

          </div>
        </div>
        @endcan

        @canany(['manage_expenses', 'view_finance'])
        <div class="nav-section nav-accordion {{ $openSection === 'finance' ? 'open' : '' }}">
          <button class="nav-accordion-toggle" type="button" data-nav-accordion aria-expanded="{{ $openSection === 'finance' ? 'true' : 'false' }}"><span><i class="bi bi-cash-coin"></i>Finance</span><i class="bi bi-chevron-down"></i></button>
          <div class="nav-accordion-panel">
            @can('view_finance')
              <a class="nav-link {{ request()->routeIs('income.index') ? 'active' : '' }}" href="{{ route('income.index') }}"><i class="bi bi-cash-stack"></i><span>Income</span></a>
            @endcan
            @can('manage_expenses')
              <a class="nav-link {{ request()->routeIs('expenses.index') ? 'active' : '' }}" href="{{ route('expenses.index') }}"><i class="bi bi-receipt-cutoff"></i><span>Expenses</span></a>
            @endcan
            @can('view_finance')
              <a class="nav-link {{ request()->routeIs('profit.index') ? 'active' : '' }}" href="{{ route('profit.index') }}"><i class="bi bi-graph-up-arrow"></i><span>Profit Analyzer</span></a>
              <a class="nav-link {{ request()->routeIs(['reports', 'reports.generate']) ? 'active' : '' }}" href="{{ route('reports') }}"><i class="bi bi-clipboard-data"></i><span>Reports</span></a>
            @endcan

          </div>
        </div>
        @endcanany

        <div class="nav-section nav-accordion {{ $openSection === 'pages' ? 'open' : '' }}">
          <button class="nav-accordion-toggle" type="button" data-nav-accordion aria-expanded="{{ $openSection === 'pages' ? 'true' : 'false' }}"><span><i class="bi bi-layers"></i>Pages</span><i class="bi bi-chevron-down"></i></button>
          <div class="nav-accordion-panel">
            <a class="nav-link {{ request()->routeIs('settings') ? 'active' : '' }}" href="{{ route('settings') }}"><i class="bi bi-gear"></i><span>Settings</span></a>
            @can('manage-team')
              <a class="nav-link {{ request()->routeIs('team') ? 'active' : '' }}" href="{{ route('team') }}"><i class="bi bi-person-workspace"></i><span>Team</span></a>
            @endcan

          </div>
        </div>
      </nav>
    </aside>
