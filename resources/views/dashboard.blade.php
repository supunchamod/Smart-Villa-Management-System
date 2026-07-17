@extends('layouts.app')

@section('title', 'Villa Cabana Management Dashboard | Dashora Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Villa Cabana Management Dashboard</h1></li>
            </ol>
          </nav>
        </div>
        <h2 class="visually-hidden">Overview</h2>
        <div class="crm-kpi-grid">
          <div class="crm-kpi-card metric-primary"><div><span>Confirmed Bookings</span><h3>{{ $confirmedBookings }}</h3></div><i class="bi bi-journal-check bg-primary-soft"></i></div>
          <div class="crm-kpi-card metric-success"><div><span>Available Rooms</span><h3>{{ $availableRooms }}</h3></div><i class="bi bi-door-open bg-success-soft"></i></div>
          <div class="crm-kpi-card metric-info"><div><span>Monthly Expenses</span><h3>{{ $globalSettings->currency }} {{ number_format($monthlyExpenses, 2) }}</h3>@if ($expensesTrend !== null)<small class="text-info"><i class="bi bi-caret-{{ $expensesTrend >= 0 ? 'up' : 'down' }}-fill"></i> {{ $expensesTrend >= 0 ? '+' : '' }}{{ $expensesTrend }}%</small>@endif</div><i class="bi bi-receipt-cutoff bg-info-soft"></i></div>
          <div class="crm-kpi-card metric-warning"><div><span>Monthly Revenue</span><h3>{{ $globalSettings->currency }} {{ number_format($monthlyRevenue, 2) }}</h3>@if ($revenueTrend !== null)<small class="text-warning"><i class="bi bi-caret-{{ $revenueTrend >= 0 ? 'up' : 'down' }}-fill"></i> {{ $revenueTrend >= 0 ? '+' : '' }}{{ $revenueTrend }}%</small>@endif</div><i class="bi bi-cash-stack bg-warning-soft"></i></div>
          <div class="crm-kpi-card metric-success"><div><span>Checked Out Today</span><h3>{{ $checkedOutToday }}</h3></div><i class="bi bi-check2-circle bg-success-soft"></i></div>
        </div>
<div class="row g-4 mt-1">
  <div class="col-xl-8">
    <div class="panel">
      <div class="panel-head"><div><h2>Income &amp; Expenses Overview</h2><p>Daily income and expenses for the last 7 days</p></div><div class="segmented"><button>All</button><button class="active">6M</button><button>1Y</button></div></div>
      <div class="balance-grid">
        <div class="balance-stat"><span>Total Bookings</span><strong>{{ $totalBookings }}</strong><small>All time</small></div>
        <div class="balance-stat"><span>Confirmed Bookings</span><strong>{{ $confirmedBookings }}</strong><small>Currently confirmed</small></div>
        <div class="balance-stat"><span>Total Rooms</span><strong>{{ $totalRooms }}</strong><small>Villa inventory</small></div>
      </div>
      <div x-data="dashboardChart(@json($chartLabels), @json($chartIncome), @json($chartExpenses))" x-init="render()">
        <canvas class="chart-md" x-ref="canvas"></canvas>
      </div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="panel crm-widget">
      <div class="panel-head"><div><h2>Upcoming Check-ins</h2><p>Next guests due to arrive</p></div></div>
      <div class="activity-list">
        @forelse ($upcomingCheckIns as $booking)
          <div class="crm-activity">
            <div class="date-chip"><strong>{{ $booking->check_in->format('d') }}</strong><span>{{ $booking->check_in->format('M') }}</span></div>
            <div><small>Check-in</small><strong>{{ $booking->customer_name }}</strong><small>{{ $booking->room->name_or_number }}</small></div>
          </div>
        @empty
          <p class="text-muted small mb-0">No upcoming check-ins scheduled.</p>
        @endforelse
      </div>
    </div>
  </div>
</div>
<div class="row g-4 mt-1">
  <div class="col-xl-8">
    <div class="panel">
      <div class="panel-head"><div><h2>Active Booking List</h2><p>Guest stays and payment status</p></div><button class="btn btn-sm btn-light">Export Report</button></div>
      <div class="table-responsive">
        <table class="table align-middle dash-table">
          <thead>
            <tr>
              <th>Guest &amp; Room</th>
              <th>Guest</th>
              <th>Payment Progress</th>
              <th>Room Type</th>
              <th>Status</th>
              <th>Checkout Date</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($activeBookings as $booking)
              @php
                $statusBadge = ['confirmed' => 'new', 'checked_out' => 'won', 'cancelled' => 'stuck'][$booking->status] ?? 'new';
                $statusLabel = ['confirmed' => 'Confirmed', 'checked_out' => 'Completed', 'cancelled' => 'Cancelled'][$booking->status] ?? ucfirst($booking->status);
              @endphp
              <tr>
                <td><strong>{{ $booking->customer_name }} - {{ $booking->room->name_or_number }}</strong></td>
                <td><span class="person-line"><span class="rep-avatar">{{ $booking->customer_initials }}</span><span>{{ $booking->customer_name }}</span></span></td>
                <td><div class="table-progress"><span>{{ $booking->payment_progress }}%</span><div class="progress"><div class="progress-bar" style="width:{{ $booking->payment_progress }}%"></div></div></div></td>
                <td>{{ $booking->room->type }}</td>
                <td><span class="deal-badge {{ $statusBadge }}">{{ $statusLabel }}</span></td>
                <td>{{ $booking->check_out->format('d M Y') }}</td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted py-4">No bookings yet. Add your first booking to get started.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="panel crm-widget">
      <div class="panel-head"><div><h2>Room Status Analysis</h2><p>Live room availability breakdown</p></div></div>
      <div class="health-score"><strong>{{ $totalRooms }}</strong><span>Total Rooms</span></div>
      <div class="roadmap-list">
        <div class="roadmap-item green"><span>Available</span><div><strong>{{ $availableRooms }} Rooms</strong><div class="progress"><div class="progress-bar" style="width:{{ $totalRooms > 0 ? round($availableRooms / $totalRooms * 100) : 0 }}%"></div></div></div><em>{{ $totalRooms > 0 ? round($availableRooms / $totalRooms * 100) : 0 }}%</em></div>
        <div class="roadmap-item blue"><span>Occupied</span><div><strong>{{ $occupiedRooms }} Rooms</strong><div class="progress"><div class="progress-bar" style="width:{{ $totalRooms > 0 ? round($occupiedRooms / $totalRooms * 100) : 0 }}%"></div></div></div><em>{{ $totalRooms > 0 ? round($occupiedRooms / $totalRooms * 100) : 0 }}%</em></div>
        <div class="roadmap-item amber"><span>Maintenance</span><div><strong>{{ $maintenanceRooms }} Rooms</strong><div class="progress"><div class="progress-bar" style="width:{{ $totalRooms > 0 ? round($maintenanceRooms / $totalRooms * 100) : 0 }}%"></div></div></div><em>{{ $totalRooms > 0 ? round($maintenanceRooms / $totalRooms * 100) : 0 }}%</em></div>
      </div>
    </div>
  </div>
</div>
<div class="row g-4 mt-1">
  <div class="col-xl-7"><div class="panel"><div class="panel-head"><div><h2>My Tasks</h2><p>Task status by owner and deadline</p></div><div class="segmented"><button class="active">All</button><button>Progress</button><button>Pending</button></div></div><div class="table-responsive"><table class="table align-middle dash-table"><thead><tr><th>Name</th><th>Deadline</th><th>Status</th><th>Assignee</th></tr></thead><tbody><tr><td><strong>Create new Admin Template</strong></td><td>03 Nov 2026</td><td><span class="deal-badge won">Completed</span></td><td><span class="person-line"><span class="rep-avatar has-photo"><img src="{{ asset('assets/img/team/team-3.jpg') }}" alt="Marketing Coordinator"></span><span>Marketing Coordinator</span></span></td></tr><tr><td><strong>Administrative Analyst</strong></td><td>17 Nov 2026</td><td><span class="deal-badge new">Progress</span></td><td><span class="person-line"><span class="rep-avatar has-photo"><img src="{{ asset('assets/img/team/team-1.jpg') }}" alt="Donald Risher"></span><span>Donald Risher</span></span></td></tr><tr><td><strong>E-commerce Landing Page</strong></td><td>10 Dec 2026</td><td><span class="deal-badge stuck">Pending</span></td><td><span class="person-line"><span class="rep-avatar has-photo"><img src="{{ asset('assets/img/team/team-2.jpg') }}" alt="Jansh Brown"></span><span>Jansh Brown</span></span></td></tr><tr><td><strong>UI/UX Design</strong></td><td>22 Dec 2026</td><td><span class="deal-badge new">Progress</span></td><td><span class="person-line"><span class="rep-avatar has-photo"><img src="{{ asset('assets/img/team/team-3.jpg') }}" alt="Carroll Adams"></span><span>Carroll Adams</span></span></td></tr><tr><td><strong>Projects Design</strong></td><td>31 Dec 2026</td><td><span class="deal-badge stuck">Pending</span></td><td><span class="person-line"><span class="rep-avatar has-photo"><img src="{{ asset('assets/img/team/team-4.jpg') }}" alt="William Pinto"></span><span>William Pinto</span></span></td></tr></tbody></table></div></div></div>
  <div class="col-xl-5"><div class="panel crm-widget team-members-widget"><div class="panel-head"><div><h2>Team Members</h2><p>Workload and task contribution</p></div><button class="btn btn-sm btn-light">30 Days</button></div><div class="member-row"><span class="rep-avatar has-photo"><img src="{{ asset('assets/img/team/team-1.jpg') }}" alt="Donald Risher"></span><div><strong>Donald Risher</strong><small>Product Manager</small></div><b>110h : 150h</b><em>258</em></div><div class="member-row"><span class="rep-avatar has-photo"><img src="{{ asset('assets/img/team/team-2.jpg') }}" alt="Jansh Brown"></span><div><strong>Jansh Brown</strong><small>Lead Developer</small></div><b>83h : 150h</b><em>105</em></div><div class="member-row"><span class="rep-avatar has-photo"><img src="{{ asset('assets/img/team/team-3.jpg') }}" alt="Carroll Adams"></span><div><strong>Carroll Adams</strong><small>Lead Designer</small></div><b>58h : 150h</b><em>75</em></div><div class="member-row"><span class="rep-avatar has-photo"><img src="{{ asset('assets/img/team/team-4.jpg') }}" alt="William Pinto"></span><div><strong>William Pinto</strong><small>UI/UX Designer</small></div><b>96h : 150h</b><em>85</em></div></div></div>
</div>
@endsection

@push('scripts')
<script>
    function dashboardChart(labels, income, expenses) {
        return {
            chart: null,
            render() {
                this.chart = new Chart(this.$refs.canvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            { label: 'Income', data: income, backgroundColor: '#2563eb', borderRadius: 8 },
                            { label: 'Expenses', data: expenses, backgroundColor: '#14b8a6', borderRadius: 8 }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: { padding: { top: 6 } },
                        plugins: {
                            legend: { position: 'top', labels: { boxWidth: 10, usePointStyle: true, padding: 22 } }
                        },
                        scales: {
                            x: { grid: { color: 'transparent' }, ticks: { color: '#94a3b8' } },
                            y: { grid: { color: 'rgba(148, 163, 184, .22)' }, ticks: { color: '#94a3b8' }, beginAtZero: true }
                        }
                    }
                });
            }
        };
    }
</script>
@endpush
