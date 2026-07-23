<aside class="mini-rail" aria-label="Quick navigation">
    <a href="{{ route('settings') }}" aria-label="Settings"><i class="bi bi-gear"></i></a>
  </aside>
    <aside class="sidebar" id="sidebar">
      <a class="brand" href="{{ route('dashboard') }}" aria-label="{{ $globalSettings->villa_name }} home">
        @if ($globalSettings->logo_url)
          <span class="brand-mark"><img src="{{ $globalSettings->logo_url }}" alt="{{ $globalSettings->villa_name }}" style="width:100%;height:100%;object-fit:cover;border-radius:8px;"></span>
        @else
          <span class="brand-mark">{{ strtoupper(substr($globalSettings->villa_name, 0, 1)) }}</span>
        @endif
        <div><strong>{{ $globalSettings->villa_name }}</strong><small>Admin Suite</small></div>
      </a>
      <nav class="sidebar-nav">
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
        @can('manage_bookings')
          <a class="nav-link {{ request()->routeIs('bookings.create') ? 'active' : '' }}" href="{{ route('bookings.create') }}"><i class="bi bi-journal-plus"></i><span>Add Bookings</span></a>
          <a class="nav-link {{ request()->routeIs('bookings.*') && ! request()->routeIs('bookings.create') ? 'active' : '' }}" href="{{ route('bookings.index') }}"><i class="bi bi-journal-check"></i><span>Manage Bookings</span></a>
          <a class="nav-link {{ request()->routeIs('calendar') ? 'active' : '' }}" href="{{ route('calendar') }}"><i class="bi bi-calendar3"></i><span>Bookings Calendar</span></a>
          <a class="nav-link {{ request()->routeIs('landing-page.*') ? 'active' : '' }}" href="{{ route('landing-page.index') }}"><i class="bi bi-easel"></i><span>Landing Page</span></a>
        @endcan
        @can('manage_expenses')
          <a class="nav-link {{ request()->routeIs('expenses.index') ? 'active' : '' }}" href="{{ route('expenses.index') }}"><i class="bi bi-receipt-cutoff"></i><span>Expenses</span></a>
        @endcan
        @can('view_finance')
          <a class="nav-link {{ request()->routeIs('income.index') ? 'active' : '' }}" href="{{ route('income.index') }}"><i class="bi bi-cash-stack"></i><span>Income</span></a>
          <a class="nav-link {{ request()->routeIs('profit.index') ? 'active' : '' }}" href="{{ route('profit.index') }}"><i class="bi bi-graph-up-arrow"></i><span>Profit Analyzer</span></a>
          <a class="nav-link {{ request()->routeIs(['reports', 'reports.generate']) ? 'active' : '' }}" href="{{ route('reports') }}"><i class="bi bi-clipboard-data"></i><span>Reports</span></a>
        @endcan
        @can('manage-team')
          <a class="nav-link {{ request()->routeIs('team') ? 'active' : '' }}" href="{{ route('team') }}"><i class="bi bi-person-workspace"></i><span>Team</span></a>
          <a class="nav-link {{ request()->routeIs('admin.whatsapp.index') ? 'active' : '' }}" href="{{ route('admin.whatsapp.index') }}"><i class="bi bi-whatsapp"></i><span>WhatsApp</span></a>
        @endcan
        <a class="nav-link {{ request()->routeIs('settings') ? 'active' : '' }}" href="{{ route('settings') }}"><i class="bi bi-gear"></i><span>Settings</span></a>
      </nav>
    </aside>
