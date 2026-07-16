@extends('layouts.app')

@section('title', 'Settings | Dashora Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="index.html"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Settings</h1></li>
            </ol>
          </nav>
          <div class="page-actions"><button class="btn btn-primary" data-create-open data-create-type="settings" data-create-label="Save Preset"><i class="bi bi-plus-lg"></i> Save Preset</button></div>
        </div>
        <div class="row g-4"><div class="col-xl-8"><div class="panel"><div class="panel-head"><div><h2>Workspace Settings</h2><p>General preferences and account controls</p></div></div><form class="row g-3"><div class="col-md-6"><label class="form-label">Workspace name</label><input class="form-control" value="Dashora Studio"></div><div class="col-md-6"><label class="form-label">Timezone</label><select class="form-select"><option>Asia/Dhaka</option><option>UTC</option></select></div><div class="col-12"><label class="form-label">Description</label><textarea class="form-control" rows="4">Premium admin dashboard workspace.</textarea></div><div class="col-12"><button class="btn btn-primary">Save Changes</button></div></form></div></div><div class="col-xl-4"><div class="panel"><div class="panel-head"><div><h2>Activity Timeline</h2><p>Live operational events</p></div></div><div class="timeline"><div class="timeline-item"><span></span><div><strong>New enterprise lead assigned to CRM pipeline</strong><small>2 min ago</small></div></div><div class="timeline-item"><span></span><div><strong>Invoice #DS-1024 was paid successfully</strong><small>18 min ago</small></div></div><div class="timeline-item"><span></span><div><strong>Inventory alert triggered for Pro License</strong><small>43 min ago</small></div></div><div class="timeline-item"><span></span><div><strong>Q2 executive report generated</strong><small>1 hr ago</small></div></div></div></div></div></div>
@endsection
