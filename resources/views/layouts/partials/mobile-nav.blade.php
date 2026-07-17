<nav class="mobile-bottom-nav d-flex d-md-none" aria-label="Mobile navigation">
  <a class="mobile-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
    <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v9a1 1 0 0 0 1 1H9.5v-5a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v5h3a1 1 0 0 0 1-1v-9"/></svg>
    <span>Home</span>
  </a>
  @can('manage_bookings')
    <a class="mobile-nav-link {{ request()->routeIs('bookings.*') && ! request()->routeIs('bookings.create') ? 'active' : '' }}" href="{{ route('bookings.index') }}">
      <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/></svg>
      <span>Bookings</span>
    </a>
    <a class="mobile-nav-fab" href="{{ route('bookings.create') }}" aria-label="Add booking">
      <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
      <span>Add</span>
    </a>
  @endcan
  @can('manage_expenses')
    <a class="mobile-nav-link {{ request()->routeIs('expenses.index') ? 'active' : '' }}" href="{{ route('expenses.index') }}">
      <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M3.5 7.5A2 2 0 0 1 5.5 5.5h11a2 2 0 0 1 2 2v1h-1.5A2.5 2.5 0 0 0 14.5 11v2a2.5 2.5 0 0 0 2.5 2.5H20v1a2 2 0 0 1-2 2h-13a2 2 0 0 1-2-2z"/><path d="M20 8.5H16.5A2.5 2.5 0 0 0 14 11v2a2.5 2.5 0 0 0 2.5 2.5H20a1 1 0 0 0 1-1V9.5a1 1 0 0 0-1-1z"/><circle cx="16.75" cy="12.5" r=".9" fill="currentColor" stroke="none"/></svg>
      <span>Expenses</span>
    </a>
  @endcan
  @can('view_finance')
    <a class="mobile-nav-link {{ request()->routeIs(['reports', 'reports.generate']) ? 'active' : '' }}" href="{{ route('reports') }}">
      <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M10 20V4M16 20v-7M4 20h16"/></svg>
      <span>Reports</span>
    </a>
  @endcan
</nav>
