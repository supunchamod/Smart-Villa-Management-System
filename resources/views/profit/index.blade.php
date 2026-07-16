@extends('layouts.app')

@section('title', 'Profit Analyzer | Dashora Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Profit Analyzer</h1></li>
            </ol>
          </nav>
          <div class="page-actions">
            <div class="segmented">
              <button type="button" class="{{ $period === 'daily' ? 'active' : '' }}" onclick="window.location='{{ route('profit.index', ['period' => 'daily']) }}'">Daily</button>
              <button type="button" class="{{ $period === 'weekly' ? 'active' : '' }}" onclick="window.location='{{ route('profit.index', ['period' => 'weekly']) }}'">Weekly</button>
              <button type="button" class="{{ $period === 'monthly' ? 'active' : '' }}" onclick="window.location='{{ route('profit.index', ['period' => 'monthly']) }}'">Monthly</button>
            </div>
          </div>
        </div>

        <div class="row g-4">
          <div class="col-sm-6 col-xl-4"><div class="metric-card metric-success"><div><span>Total Revenue</span><h3>{{ number_format($totalRevenue, 2) }}</h3></div><i class="bi bi-graph-up-arrow bg-success-soft"></i></div></div>
          <div class="col-sm-6 col-xl-4"><div class="metric-card metric-warning"><div><span>Total Expenses</span><h3>{{ number_format($totalExpenses, 2) }}</h3></div><i class="bi bi-receipt bg-warning-soft"></i></div></div>
          <div class="col-sm-6 col-xl-4"><div class="metric-card {{ $netProfit >= 0 ? 'metric-success' : 'metric-warning' }}"><div><span>Net Profit</span><h3>{{ number_format($netProfit, 2) }}</h3></div><i class="bi bi-cash-coin {{ $netProfit >= 0 ? 'bg-success-soft' : 'bg-warning-soft' }}"></i></div></div>
        </div>

        <div class="row g-4 mt-1">
          <div class="col-12">
            <div class="panel">
              <div class="panel-head"><div><h2>Income vs Expenses</h2><p>Day-by-day comparison for the selected period</p></div></div>
              <div x-data="profitChart(@json($chartLabels), @json($chartIncome), @json($chartExpenses))" x-init="render()">
                <canvas x-ref="canvas" style="width: 100%; height: 340px;"></canvas>
              </div>
            </div>
          </div>
        </div>
@endsection

@push('scripts')
<script>
    function profitChart(labels, income, expenses) {
        return {
            chart: null,
            render() {
                this.chart = new Chart(this.$refs.canvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            { label: 'Income', data: income, backgroundColor: '#5278ff', borderRadius: 6 },
                            { label: 'Expenses', data: expenses, backgroundColor: '#dc2626', borderRadius: 6 }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top' }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
        };
    }
</script>
@endpush
