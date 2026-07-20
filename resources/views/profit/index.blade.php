@extends('layouts.app')

@section('title', 'Profit Analyzer | Villa Cabana')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/profit-analyzer.css') }}">
@endpush

@section('content')
@php
  $donutPalette = ['#002d62', '#e8b22d', '#1c7ed6', '#0f9d78', '#a15c07', '#7c3aed', '#dc2626', '#64748b'];
@endphp
<div class="pa">
  <div class="page-title">
    <nav class="page-breadcrumb" aria-label="breadcrumb">
      <ol>
        <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
        <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
        <li aria-current="page"><h1>Profit Analyzer</h1></li>
      </ol>
    </nav>
    <div class="page-actions">
      <div class="pa-filter-bar" x-data="{ showCustom: {{ $range === 'custom' ? 'true' : 'false' }} }">
        <div class="pa-filter-tabs" role="tablist" aria-label="Date range">
          <a href="{{ route('profit.index', ['range' => 'today']) }}" class="pa-filter-tab {{ $range === 'today' ? 'active' : '' }}">Today</a>
          <a href="{{ route('profit.index', ['range' => 'week']) }}" class="pa-filter-tab {{ $range === 'week' ? 'active' : '' }}">This Week</a>
          <a href="{{ route('profit.index', ['range' => 'month']) }}" class="pa-filter-tab {{ $range === 'month' ? 'active' : '' }}">This Month</a>
          <button type="button" class="pa-filter-tab {{ $range === 'custom' ? 'active' : '' }}" @click="showCustom = !showCustom">
            <i class="bi bi-calendar-range me-1"></i> Custom Range
          </button>
        </div>
        <form method="GET" action="{{ route('profit.index') }}" class="pa-custom-range" x-show="showCustom" x-cloak>
          <input type="hidden" name="range" value="custom">
          <input type="date" name="from" value="{{ $rangeFrom }}" class="form-control form-control-sm" required>
          <span class="text-muted small">to</span>
          <input type="date" name="to" value="{{ $rangeTo }}" class="form-control form-control-sm" required>
          <button type="submit" class="btn btn-sm pa-btn-gold"><i class="bi bi-check2"></i> Apply</button>
        </form>
      </div>
    </div>
  </div>

  {{-- Top row: core financial metrics --}}
  <div class="row">
    <div class="col-12 col-md-6 col-lg-3 mb-4">
      <div class="pa-card">
        <div class="pa-card-top"><span class="pa-card-icon pa-icon-navy"><i class="bi bi-graph-up-arrow"></i></span></div>
        <span class="pa-card-label">Total Revenue <em>මුළු ආදායම</em></span>
        <h3 class="pa-card-value">{{ $globalSettings->currency }} {{ number_format($totalRevenue, 2) }}</h3>
        <p class="pa-card-caption">Room bookings, cabana stays & F&amp;B settlements for {{ $rangeLabel }}</p>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3 mb-4">
      <div class="pa-card">
        <div class="pa-card-top"><span class="pa-card-icon pa-icon-rose"><i class="bi bi-receipt-cutoff"></i></span></div>
        <span class="pa-card-label">Total Expenses <em>මුළු වියදම්</em></span>
        <h3 class="pa-card-value">{{ $globalSettings->currency }} {{ number_format($totalExpenses, 2) }}</h3>
        <p class="pa-card-caption">Maintenance, staff, utilities &amp; inventory for {{ $rangeLabel }}</p>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3 mb-4">
      <div class="pa-card">
        <div class="pa-card-top"><span class="pa-card-icon pa-icon-ocean"><i class="bi bi-piggy-bank"></i></span></div>
        <span class="pa-card-label">Gross Profit <em>සමස්ත ලාභය</em></span>
        <h3 class="pa-card-value">{{ $globalSettings->currency }} {{ number_format($grossProfit, 2) }}</h3>
        <p class="pa-card-caption">Revenue minus direct costs (housekeeping, F&amp;B, inventory: {{ $globalSettings->currency }} {{ number_format($directCosts, 2) }})</p>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3 mb-4">
      <div class="pa-card pa-card-gold">
        <div class="pa-card-top"><span class="pa-card-icon pa-icon-gold"><i class="bi bi-trophy"></i></span></div>
        <span class="pa-card-label">Net Profit <em>ශුද්ධ ලාභය</em></span>
        <h3 class="pa-card-value">{{ $globalSettings->currency }} {{ number_format($netProfit, 2) }}</h3>
        <p class="pa-card-caption">Left over after every operational deduction ({{ $globalSettings->currency }} {{ number_format($operationalCosts, 2) }} fixed/operational costs)</p>
      </div>
    </div>
  </div>

  {{-- Middle row: trend + expense distribution --}}
  <div class="row">
    <div class="col-lg-8 mb-4">
      <div
        class="pa-panel"
        x-data="trendChart(@json($weeklyLabels), @json($weeklyRevenue), @json($weeklyNetProfit), @json($monthlyLabels), @json($monthlyRevenue), @json($monthlyNetProfit), @js($granularity))"
        x-init="render()"
      >
        <div class="pa-panel-head">
          <div><h2>Revenue vs Net Profit Timeline</h2><p>Trailing 12 periods of history, independent of the date-range filter above</p></div>
          <div class="pa-segmented">
            <button type="button" :class="{ active: unit === 'weekly' }" @click="setUnit('weekly')">Weekly</button>
            <button type="button" :class="{ active: unit === 'monthly' }" @click="setUnit('monthly')">Monthly</button>
          </div>
        </div>
        <canvas x-ref="canvas" style="width: 100%; height: 360px;"></canvas>
      </div>
    </div>
    <div class="col-lg-4 mb-4">
      <div
        class="pa-panel"
        x-data="expenseDonut(@json($expenseBreakdown->pluck('category')), @json($expenseBreakdown->pluck('amount')), @json($donutPalette))"
        x-init="render()"
      >
        <div class="pa-panel-head"><div><h2>Expense Distribution</h2><p>Where the villa spends the most in {{ $rangeLabel }}</p></div></div>
        @if ($expenseBreakdown->isEmpty())
          <p class="pa-empty">No expenses recorded for {{ $rangeLabel }}.</p>
        @else
          <canvas x-ref="canvas" style="width: 100%; height: 220px;"></canvas>
          <ul class="pa-legend">
            @foreach ($expenseBreakdown as $i => $row)
              <li>
                <span class="pa-legend-dot" style="background: {{ $donutPalette[$i % count($donutPalette)] }}"></span>
                <span>{{ $row['category'] }}</span>
                <strong>{{ $globalSettings->currency }} {{ number_format($row['amount'], 2) }}</strong>
              </li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>
  </div>

  {{-- Bottom row: revenue ledger & financial logs, plus upcoming bookings --}}
  <div class="row">
    <div class="col-lg-8 mb-4">
      <div class="pa-panel h-100">
        <div class="pa-panel-head">
          <div><h2>Revenue Ledger &amp; Financial Logs</h2><p>{{ $rangeLabel }} &middot; bookings, check-ins &amp; service revenue with allocated cost &amp; margin</p></div>
        </div>

        <div class="d-none d-md-block">
          <div class="table-responsive">
            <table class="table align-middle pa-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Transaction ID</th>
                  <th>Source</th>
                  <th>Gross Revenue</th>
                  <th>Expense Allocated</th>
                  <th>Net Profit</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($ledger as $row)
                  @php
                    $statusMeta = [
                      'high' => ['label' => 'High Margin', 'class' => 'pa-badge-high'],
                      'standard' => ['label' => 'Standard', 'class' => 'pa-badge-standard'],
                      'low' => ['label' => 'Low Margin', 'class' => 'pa-badge-low'],
                    ][$row['status']];
                  @endphp
                  <tr>
                    <td>{{ $row['date']->format('d M Y') }}</td>
                    <td><strong>{{ $row['transaction_id'] }}</strong><br><small class="text-muted">{{ $row['customer_name'] }}</small></td>
                    <td>{{ $row['source'] }}</td>
                    <td>{{ $globalSettings->currency }} {{ number_format($row['gross_revenue'], 2) }}</td>
                    <td>{{ $globalSettings->currency }} {{ number_format($row['expense_allocated'], 2) }}</td>
                    <td>{{ $globalSettings->currency }} {{ number_format($row['net_profit'], 2) }}</td>
                    <td><span class="pa-badge {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span></td>
                  </tr>
                @empty
                  <tr><td colspan="7" class="text-center text-muted py-4">No transactions recorded for {{ $rangeLabel }}.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div class="d-block d-md-none">
          <div class="pa-ledger-list">
            @forelse ($ledger as $row)
              @php
                $statusMeta = [
                  'high' => ['label' => 'High Margin', 'class' => 'pa-badge-high'],
                  'standard' => ['label' => 'Standard', 'class' => 'pa-badge-standard'],
                  'low' => ['label' => 'Low Margin', 'class' => 'pa-badge-low'],
                ][$row['status']];
              @endphp
              <div class="pa-ledger-card">
                <div class="pa-ledger-card-top">
                  <div>
                    <strong>{{ $row['transaction_id'] }}</strong>
                    <small>{{ $row['customer_name'] }} &middot; {{ $row['source'] }} &middot; {{ $row['date']->format('d M Y') }}</small>
                  </div>
                  <span class="pa-badge {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
                </div>
                <div class="pa-ledger-figures">
                  <span>Gross <strong>{{ $globalSettings->currency }} {{ number_format($row['gross_revenue'], 2) }}</strong></span>
                  <span>Allocated <strong>{{ $globalSettings->currency }} {{ number_format($row['expense_allocated'], 2) }}</strong></span>
                  <span>Net <strong>{{ $globalSettings->currency }} {{ number_format($row['net_profit'], 2) }}</strong></span>
                </div>
              </div>
            @empty
              <p class="pa-empty">No transactions recorded for {{ $rangeLabel }}.</p>
            @endforelse
          </div>
        </div>

        @if ($ledger->hasPages())
          <div class="mt-3">{{ $ledger->links() }}</div>
        @endif
      </div>
    </div>

    <div class="col-12 col-lg-4 mb-4">
      <div class="pa-panel h-100 d-flex flex-column justify-content-between">
        <div>
          <span class="pa-accent-bar" aria-hidden="true"></span>
          <span class="pa-eyebrow">Expected Revenue</span>
          <h2 class="pa-section-title">Upcoming Bookings</h2>
          <p class="pa-section-sub">Next scheduled check-ins &amp; forecasted revenue</p>

          <div class="pa-upcoming-list">
            @forelse ($upcomingBookings as $booking)
              @php $nights = max(1, $booking->check_in->diffInDays($booking->check_out)); @endphp
              <div class="pa-upcoming-card mb-3">
                <div class="pa-upcoming-date">
                  <span class="pa-upcoming-date-day">{{ $booking->check_in->format('d') }}</span>
                  <span class="pa-upcoming-date-month">{{ $booking->check_in->format('M') }}</span>
                </div>
                <div class="pa-upcoming-body">
                  <strong class="pa-upcoming-title">{{ $booking->room->name_or_number }}</strong>
                  <small class="pa-upcoming-meta">Guest: {{ $booking->customer_name }} &bull; {{ $nights }} Night{{ $nights === 1 ? '' : 's' }}</small>
                </div>
                <span class="pa-upcoming-revenue">{{ $globalSettings->currency }} {{ number_format($booking->total_amount, 0) }}</span>
              </div>
            @empty
              <p class="pa-empty">No upcoming check-ins scheduled.</p>
            @endforelse
          </div>
        </div>

        <a href="{{ route('calendar') }}" class="pa-upcoming-cta">View Booking Calendar &rarr;</a>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    function trendChart(weeklyLabels, weeklyRevenue, weeklyNetProfit, monthlyLabels, monthlyRevenue, monthlyNetProfit, initialUnit) {
        return {
            chart: null,
            unit: initialUnit,
            datasets: {
                weekly: { labels: weeklyLabels, revenue: weeklyRevenue, netProfit: weeklyNetProfit },
                monthly: { labels: monthlyLabels, revenue: monthlyRevenue, netProfit: monthlyNetProfit },
            },
            render() {
                const data = this.datasets[this.unit];
                this.chart = new Chart(this.$refs.canvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Revenue',
                                data: data.revenue,
                                borderColor: '#1c7ed6',
                                backgroundColor: 'rgba(28, 126, 214, .14)',
                                fill: true,
                                tension: .4,
                                pointRadius: 3,
                                pointBackgroundColor: '#1c7ed6',
                                borderWidth: 2,
                            },
                            {
                                label: 'Net Profit',
                                data: data.netProfit,
                                borderColor: '#e8b22d',
                                backgroundColor: 'rgba(232, 178, 45, .18)',
                                fill: true,
                                tension: .4,
                                pointRadius: 3,
                                pointBackgroundColor: '#e8b22d',
                                borderWidth: 2,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { position: 'top', labels: { boxWidth: 10, usePointStyle: true, padding: 20 } },
                        },
                        scales: {
                            x: { grid: { color: 'transparent' }, ticks: { color: '#94a3b8' } },
                            y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, .22)' }, ticks: { color: '#94a3b8' } },
                        },
                    },
                });
            },
            setUnit(unit) {
                if (this.unit === unit || !this.chart) return;

                this.unit = unit;
                const data = this.datasets[unit];

                this.chart.data.labels = data.labels;
                this.chart.data.datasets[0].data = data.revenue;
                this.chart.data.datasets[1].data = data.netProfit;
                this.chart.update();
            },
        };
    }

    function expenseDonut(labels, data, palette) {
        return {
            chart: null,
            render() {
                if (!labels.length) return;

                this.chart = new Chart(this.$refs.canvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: labels.map((_, i) => palette[i % palette.length]),
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '68%',
                        plugins: { legend: { display: false } },
                    },
                });
            },
        };
    }
</script>
@endpush
