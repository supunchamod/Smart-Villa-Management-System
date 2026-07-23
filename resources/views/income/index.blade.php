@extends('layouts.app')

@section('title', 'Income | '.$globalSettings->villa_name.' Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Income</h1></li>
            </ol>
          </nav>
        </div>

        <div class="row g-4">
          <div class="col-sm-6 col-xl-4"><div class="metric-card metric-success"><div><span>Total Income</span><h3>{{ number_format($total, 2) }}</h3></div><i class="bi bi-cash-stack bg-success-soft"></i></div></div>
          <div class="col-sm-6 col-xl-4"><div class="metric-card metric-primary"><div><span>Advance Payments</span><h3>{{ number_format($advanceTotal, 2) }}</h3></div><i class="bi bi-wallet2 bg-primary-soft"></i></div></div>
          <div class="col-sm-6 col-xl-4"><div class="metric-card metric-info"><div><span>Final Settlements</span><h3>{{ number_format($settlementTotal, 2) }}</h3></div><i class="bi bi-check2-circle bg-info-soft"></i></div></div>
        </div>

        <div class="row g-4 mt-1">
          <div class="col-12">
            <div class="panel">
              <div class="panel-head">
                <div><h2>Cash Flow</h2><p>Advance payments and final settlements from bookings</p></div>
              </div>
              <form class="row g-3 mb-3" method="GET" action="{{ route('income.index') }}">
                <div class="col-sm-4 col-md-3">
                  <label class="form-label">From</label>
                  <input class="form-control" type="date" name="from" value="{{ $from }}">
                </div>
                <div class="col-sm-4 col-md-3">
                  <label class="form-label">To</label>
                  <input class="form-control" type="date" name="to" value="{{ $to }}">
                </div>
                <div class="col-sm-4 col-md-3 d-flex align-items-end gap-2">
                  <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                  <a class="btn btn-light" href="{{ route('income.index') }}">Reset</a>
                </div>
              </form>
              <div class="table-responsive">
                <table class="table align-middle dash-table">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Type</th>
                      <th>Customer</th>
                      <th>Room</th>
                      <th class="text-end">Amount</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($rows as $row)
                      <tr>
                        <td>{{ $row['date']->format('d M Y') }}</td>
                        <td><span class="deal-badge {{ $row['type'] === 'Advance Payment' ? 'new' : 'won' }}">{{ $row['type'] }}</span></td>
                        <td>{{ $row['booking']->customer_name }}</td>
                        <td>{{ $row['booking']->room->name_or_number }}</td>
                        <td class="text-end">{{ number_format($row['amount'], 2) }}</td>
                      </tr>
                    @empty
                      <tr><td colspan="5" class="text-center text-muted py-4">No income recorded for this period.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
@endsection
