@extends('layouts.app')

@section('title', 'Projects | Dashora Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="index.html"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Projects</h1></li>
            </ol>
          </nav>
          <div class="page-actions"><button class="btn btn-primary" data-create-open data-create-type="projects" data-create-label="Create Project"><i class="bi bi-plus-lg"></i> Create Project</button></div>
        </div>
        <h2 class="visually-hidden">Overview</h2>
<div class="project-summary">
  <div class="summary-tile blue"><i class="bi bi-kanban"></i><div><strong>24</strong><span>Active Projects</span></div></div><div class="summary-tile green"><i class="bi bi-calendar-check"></i><div><strong>18</strong><span>On Schedule</span></div></div><div class="summary-tile amber"><i class="bi bi-exclamation-triangle"></i><div><strong>4</strong><span>At Risk</span></div></div><div class="summary-tile violet"><i class="bi bi-check2-circle"></i><div><strong>126</strong><span>Completed</span></div></div>
</div>
<div class="panel local-filter-bar mt-4"><div class="search local-search"><i class="bi bi-search"></i><input type="search" placeholder="Search projects by name, owner, status..." aria-label="Search projects" data-local-search="#projectGrid"></div><div class="filter-meta"><span data-local-count>4 projects</span></div></div>
<div class="row g-4 mt-1" id="projectGrid"><div class="col-md-6 col-xl-3" data-local-item><div class="project-card modern-card">
  <div class="project-cover tone-1"><i class="bi bi-kanban"></i><span>Design</span></div>
  <div class="project-body">
    <div class="project-meta"><span>Product</span><em>High</em></div>
    <h3>Launch redesign</h3><p>Design review and stakeholder signoff</p>
    <div class="project-progress"><div><span>Progress</span><strong>82%</strong></div><div class="progress"><div class="progress-bar" style="width:82%"></div></div></div>
    <div class="project-foot"><div class="avatar-stack"><span class="has-photo"><img src="{{ asset('assets/img/team/team-2.jpg') }}" alt="Sara Ahmed"></span><span class="has-photo"><img src="{{ asset('assets/img/team/team-3.jpg') }}" alt="Maya Rahman"></span><span class="has-photo"><img src="{{ asset('assets/img/team/team-4.jpg') }}" alt="Jon Lee"></span></div><small>May 28</small></div>
  </div>
</div></div><div class="col-md-6 col-xl-3" data-local-item><div class="project-card modern-card">
  <div class="project-cover tone-2"><i class="bi bi-kanban"></i><span>Mobile</span></div>
  <div class="project-body">
    <div class="project-meta"><span>Commerce</span><em>Medium</em></div>
    <h3>Mobile checkout</h3><p>Checkout flow and payment QA</p>
    <div class="project-progress"><div><span>Progress</span><strong>64%</strong></div><div class="progress"><div class="progress-bar" style="width:64%"></div></div></div>
    <div class="project-foot"><div class="avatar-stack"><span class="has-photo"><img src="{{ asset('assets/img/team/team-2.jpg') }}" alt="Sara Ahmed"></span><span class="has-photo"><img src="{{ asset('assets/img/team/team-3.jpg') }}" alt="Maya Rahman"></span><span class="has-photo"><img src="{{ asset('assets/img/team/team-4.jpg') }}" alt="Jon Lee"></span></div><small>Jun 04</small></div>
  </div>
</div></div><div class="col-md-6 col-xl-3" data-local-item><div class="project-card modern-card">
  <div class="project-cover tone-3"><i class="bi bi-kanban"></i><span>Data</span></div>
  <div class="project-body">
    <div class="project-meta"><span>Platform</span><em>Low</em></div>
    <h3>Data warehouse</h3><p>Warehouse sync and data quality</p>
    <div class="project-progress"><div><span>Progress</span><strong>38%</strong></div><div class="progress"><div class="progress-bar" style="width:38%"></div></div></div>
    <div class="project-foot"><div class="avatar-stack"><span class="has-photo"><img src="{{ asset('assets/img/team/team-2.jpg') }}" alt="Sara Ahmed"></span><span class="has-photo"><img src="{{ asset('assets/img/team/team-3.jpg') }}" alt="Maya Rahman"></span><span class="has-photo"><img src="{{ asset('assets/img/team/team-4.jpg') }}" alt="Jon Lee"></span></div><small>Jun 18</small></div>
  </div>
</div></div><div class="col-md-6 col-xl-3" data-local-item><div class="project-card modern-card">
  <div class="project-cover tone-4"><i class="bi bi-kanban"></i><span>Portal</span></div>
  <div class="project-body">
    <div class="project-meta"><span>Partner</span><em>High</em></div>
    <h3>Partner portal</h3><p>Partner onboarding portal polish</p>
    <div class="project-progress"><div><span>Progress</span><strong>91%</strong></div><div class="progress"><div class="progress-bar" style="width:91%"></div></div></div>
    <div class="project-foot"><div class="avatar-stack"><span class="has-photo"><img src="{{ asset('assets/img/team/team-2.jpg') }}" alt="Sara Ahmed"></span><span class="has-photo"><img src="{{ asset('assets/img/team/team-3.jpg') }}" alt="Maya Rahman"></span><span class="has-photo"><img src="{{ asset('assets/img/team/team-4.jpg') }}" alt="Jon Lee"></span></div><small>May 30</small></div>
  </div>
</div></div><div class="col-12" data-local-empty hidden><div class="empty-state local-empty"><i class="bi bi-search"></i><h2>No projects found</h2><p>Try searching by project, department, priority, progress, or due date.</p></div></div></div>
<div class="row g-4 mt-1"><div class="col-xl-8"><div class="panel project-board-panel"><div class="panel-head"><div><h2>Project Roadmap</h2><p>Delivery status by initiative</p></div><button class="btn btn-sm btn-light">View all</button></div><div class="roadmap-list"><div class="roadmap-item blue"><span>Discovery</span><div><strong>Brand system refresh</strong><div class="progress"><div class="progress-bar" style="width:42%"></div></div></div><em>42%</em></div><div class="roadmap-item green"><span>Build</span><div><strong>Checkout optimization</strong><div class="progress"><div class="progress-bar" style="width:68%"></div></div></div><em>68%</em></div><div class="roadmap-item amber"><span>QA</span><div><strong>Data warehouse sync</strong><div class="progress"><div class="progress-bar" style="width:76%"></div></div></div><em>76%</em></div><div class="roadmap-item violet"><span>Launch</span><div><strong>Partner portal</strong><div class="progress"><div class="progress-bar" style="width:91%"></div></div></div><em>91%</em></div></div></div></div><div class="col-xl-4"><div class="panel"><div class="panel-head"><div><h2>Activity Timeline</h2><p>Live operational events</p></div></div><div class="timeline"><div class="timeline-item"><span></span><div><strong>New enterprise lead assigned to CRM pipeline</strong><small>2 min ago</small></div></div><div class="timeline-item"><span></span><div><strong>Invoice #DS-1024 was paid successfully</strong><small>18 min ago</small></div></div><div class="timeline-item"><span></span><div><strong>Inventory alert triggered for Pro License</strong><small>43 min ago</small></div></div><div class="timeline-item"><span></span><div><strong>Q2 executive report generated</strong><small>1 hr ago</small></div></div></div></div></div></div>
@endsection
