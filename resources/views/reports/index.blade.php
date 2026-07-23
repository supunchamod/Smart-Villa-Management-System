@extends('layouts.app')

@section('title', 'Reports | '.$globalSettings->villa_name.' Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Reports</h1></li>
            </ol>
          </nav>
        </div>

        <div class="row g-4">
          <div class="col-xl-7">
            <div class="panel">
              <div class="panel-head"><div><h2>Generate Financial Report</h2><p>Select a report type and date range, then download the PDF</p></div></div>
              <form method="GET" action="{{ route('reports.generate') }}" target="_blank" x-data="{ period: 'monthly' }">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Report Type</label>
                    <select class="form-select" name="report_type">
                      <option value="income">Income Report</option>
                      <option value="expense">Expense Report</option>
                      <option value="profit_loss" selected>Full Profit &amp; Loss Statement</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Date Range</label>
                    <select class="form-select" name="period" x-model="period">
                      <option value="daily">Daily (Today)</option>
                      <option value="weekly">Weekly (Last 7 Days)</option>
                      <option value="monthly" selected>Monthly (Current Month)</option>
                      <option value="custom">Custom Range</option>
                    </select>
                  </div>
                  <template x-if="period === 'custom'">
                    <div class="col-md-6">
                      <label class="form-label">From</label>
                      <input class="form-control" type="date" name="from">
                    </div>
                  </template>
                  <template x-if="period === 'custom'">
                    <div class="col-md-6">
                      <label class="form-label">To</label>
                      <input class="form-control" type="date" name="to">
                    </div>
                  </template>
                </div>
                <div class="mt-3">
                  <button class="btn btn-primary" type="submit"><i class="bi bi-file-earmark-pdf"></i> Generate PDF Report</button>
                </div>
              </form>
            </div>
          </div>
          <div class="col-xl-5">
            <div class="panel">
              <div class="panel-head"><div><h2>About These Reports</h2><p>What each statement includes</p></div></div>
              <ul class="mb-0">
                <li><strong>Income Report</strong> — every advance payment and final settlement collected in the period.</li>
                <li><strong>Expense Report</strong> — every logged operating expense in the period.</li>
                <li><strong>Full Profit &amp; Loss Statement</strong> — both of the above, plus total revenue, total expenses, and net profit.</li>
              </ul>
            </div>
          </div>
        </div>
@endsection
