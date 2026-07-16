@extends('layouts.app')

@section('title', 'Customer Details | Dashora Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="index.html"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Customer Details</h1></li>
            </ol>
          </nav>
          <div class="page-actions"><button class="btn btn-primary" data-create-open data-create-type="customer-details" data-create-label="Add Note"><i class="bi bi-plus-lg"></i> Add Note</button></div>
        </div>
        <div class="customer-hero panel">
  <div class="customer-identity">
    <span class="profile-avatar has-photo"><img src="{{ asset('assets/img/team/team-6.jpg') }}" alt="Ariana Reed"></span>
    <div><span class="eyebrow">Enterprise Account</span><h2>Ariana Reed</h2><p>VP Operations at Acme Studio</p></div>
  </div>
  <div class="customer-actions"><button class="btn btn-light"><i class="bi bi-envelope"></i> Message</button><button class="btn btn-primary"><i class="bi bi-pencil-square"></i> Edit Customer</button></div>
</div>
<div class="customer-detail-grid">
  <aside class="customer-sidebar">
    <div class="panel customer-contact">
      <h3>Contact</h3>
      <div class="contact-row"><i class="bi bi-envelope"></i><span>ariana@acmestudio.com</span></div><div class="contact-row"><i class="bi bi-telephone"></i><span>+1 415 882 4021</span></div><div class="contact-row"><i class="bi bi-geo-alt"></i><span>San Francisco, CA</span></div><div class="contact-row"><i class="bi bi-building"></i><span>Acme Studio Inc.</span></div>
    </div>
    <div class="panel customer-health">
      <h3>Account Health</h3>
      <div class="health-score"><strong>92</strong><span>Excellent</span></div>
      <div class="mini-track"><i style="width:92%"></i></div>
      <p>High product adoption, active expansion signals, and no open billing risk.</p>
    </div>
  </aside>
  <section class="customer-main">
    <div class="row g-4">
      <div class="col-sm-6 col-xl-3"><div class="metric-card metric-success"><div><span>Lifetime Value</span><h3>$84,260</h3><small class="text-success"><i class="bi bi-caret-up-fill"></i> +18%</small></div><i class="bi bi-cash-stack bg-success-soft"></i></div></div><div class="col-sm-6 col-xl-3"><div class="metric-card metric-primary"><div><span>Open Orders</span><h3>12</h3><small class="text-primary"><i class="bi bi-caret-up-fill"></i> +4</small></div><i class="bi bi-bag-check bg-primary-soft"></i></div></div><div class="col-sm-6 col-xl-3"><div class="metric-card metric-info"><div><span>Invoices Paid</span><h3>46</h3><small class="text-info"><i class="bi bi-caret-up-fill"></i> 100%</small></div><i class="bi bi-receipt bg-info-soft"></i></div></div><div class="col-sm-6 col-xl-3"><div class="metric-card metric-warning"><div><span>Support SLA</span><h3>98%</h3><small class="text-warning"><i class="bi bi-caret-up-fill"></i> +6%</small></div><i class="bi bi-headset bg-warning-soft"></i></div></div>
    </div>
    <div class="row g-4 mt-1">
      <div class="col-xl-7"><div class="panel"><div class="panel-head"><div><h2>Revenue Trend</h2><p>Account spend over the last eight months</p></div></div><canvas class="chart-lg" data-chart="revenue"></canvas></div></div>
      <div class="col-xl-5"><div class="panel"><div class="panel-head"><div><h2>Relationship Timeline</h2><p>Recent account activity</p></div></div><div class="timeline"><div class="timeline-item"><span></span><div><strong>Renewal proposal sent to Ariana</strong><small>Today, 09:40</small></div></div><div class="timeline-item"><span></span><div><strong>Invoice #DS-1024 paid successfully</strong><small>Yesterday</small></div></div><div class="timeline-item"><span></span><div><strong>Support ticket resolved within SLA</strong><small>May 21, 2026</small></div></div><div class="timeline-item"><span></span><div><strong>Expansion opportunity moved to proposal</strong><small>May 20, 2026</small></div></div></div></div></div>
    </div>
    <div class="row g-4 mt-1">
      <div class="col-xl-8"><div class="panel"><div class="panel-head"><div><h2>Recent Account Orders</h2><p>High priority records requiring attention</p></div><button class="btn btn-sm btn-light">Manage</button></div><div class="table-responsive"><table class="table align-middle dash-table"><thead><tr><th>ID</th><th>Customer</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead><tbody><tr><td><strong>#DS-1024</strong></td><td>Nexa Analytics</td><td>May 22, 2026</td><td>$3,420</td><td><span class="status paid">Paid</span></td></tr><tr><td><strong>#DS-1023</strong></td><td>Orbit CRM</td><td>May 21, 2026</td><td>$1,980</td><td><span class="status pending">Pending</span></td></tr><tr><td><strong>#DS-1022</strong></td><td>StudioFlow</td><td>May 20, 2026</td><td>$6,100</td><td><span class="status paid">Paid</span></td></tr><tr><td><strong>#DS-1021</strong></td><td>CloudPeak</td><td>May 19, 2026</td><td>$840</td><td><span class="status overdue">Overdue</span></td></tr></tbody></table></div></div></div>
      <div class="col-xl-4"><div class="panel note-panel"><div class="panel-head"><div><h2>Account Notes</h2><p>Internal customer context</p></div></div><div class="note-item"><i class="bi bi-stickies"></i><p>Prefers quarterly executive summaries.</p></div><div class="note-item"><i class="bi bi-stickies"></i><p>Interested in analytics add-on for finance team.</p></div><div class="note-item"><i class="bi bi-stickies"></i><p>Legal approved updated MSA on May 18.</p></div></div></div>
    </div>
  </section>
</div>
@endsection
