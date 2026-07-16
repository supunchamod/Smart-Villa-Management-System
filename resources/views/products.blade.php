@extends('layouts.app')

@section('title', 'Products | Dashora Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="index.html"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Products</h1></li>
            </ol>
          </nav>
          <div class="page-actions"><button class="btn btn-light" data-export-page><i class="bi bi-download"></i> Export</button><button class="btn btn-primary" data-create-open data-create-type="products" data-create-label="Add Product"><i class="bi bi-plus-lg"></i> Add Product</button></div>
        </div>
        <h2 class="visually-hidden">Overview</h2>
        
<div class="row g-4">
  <div class="col-sm-6 col-xl-3"><div class="metric-card metric-success"><div><span>Revenue</span><h3>$128.4K</h3><small class="text-success"><i class="bi bi-caret-up-fill"></i> +18.2% this month</small></div><i class="bi bi-graph-up-arrow bg-success-soft"></i></div></div><div class="col-sm-6 col-xl-3"><div class="metric-card metric-primary"><div><span>Orders</span><h3>8,642</h3><small class="text-primary"><i class="bi bi-caret-up-fill"></i> +9.7% this month</small></div><i class="bi bi-cart-check bg-primary-soft"></i></div></div><div class="col-sm-6 col-xl-3"><div class="metric-card metric-info"><div><span>Customers</span><h3>24,918</h3><small class="text-info"><i class="bi bi-caret-up-fill"></i> +12.4% this month</small></div><i class="bi bi-people bg-info-soft"></i></div></div><div class="col-sm-6 col-xl-3"><div class="metric-card metric-warning"><div><span>Conversion</span><h3>7.82%</h3><small class="text-warning"><i class="bi bi-caret-down-fill"></i> -1.1% this month</small></div><i class="bi bi-lightning-charge bg-warning-soft"></i></div></div>
</div><div class="row g-4 mt-1"><div class="col-xl-8"><div class="panel"><div class="panel-head"><div><h2>Product Catalog</h2><p>High priority records requiring attention</p></div><button class="btn btn-sm btn-light">Manage</button></div><div class="table-responsive"><table class="table align-middle dash-table"><thead><tr><th>ID</th><th>Customer</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead><tbody><tr><td><strong>#DS-1024</strong></td><td>Nexa Analytics</td><td>May 22, 2026</td><td>$3,420</td><td><span class="status paid">Paid</span></td></tr><tr><td><strong>#DS-1023</strong></td><td>Orbit CRM</td><td>May 21, 2026</td><td>$1,980</td><td><span class="status pending">Pending</span></td></tr><tr><td><strong>#DS-1022</strong></td><td>StudioFlow</td><td>May 20, 2026</td><td>$6,100</td><td><span class="status paid">Paid</span></td></tr><tr><td><strong>#DS-1021</strong></td><td>CloudPeak</td><td>May 19, 2026</td><td>$840</td><td><span class="status overdue">Overdue</span></td></tr></tbody></table></div></div></div><div class="col-xl-4"><div class="panel"><div class="panel-head"><div><h2>Segment Health</h2><p>Current mix by status</p></div></div><canvas class="chart-md" data-chart="traffic"></canvas></div></div></div>
@endsection
