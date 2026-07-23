@extends('layouts.app')

@section('title', 'File Manager | '.$globalSettings->villa_name.' Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="index.html"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>File Manager</h1></li>
            </ol>
          </nav>
          <div class="page-actions"><button class="btn btn-primary"><i class="bi bi-cloud-arrow-up"></i> Upload File</button></div>
        </div>
        <h2 class="visually-hidden">Overview</h2>

        <div class="file-manager-hero panel">
          <div>
            <span class="eyebrow">Workspace Storage</span>
            <h2>Manage shared files, folders, and team documents.</h2>
            <p>Track storage usage, recent uploads, and important folders from one responsive file workspace.</p>
          </div>
          <div class="storage-card">
            <span>Storage Used</span>
            <strong>68%</strong>
            <div class="mini-track"><i style="width:68%"></i></div>
            <small>42.8 GB of 64 GB used</small>
          </div>
        </div>
        <div class="row g-4">
          <div class="col-sm-6 col-xl-3"><div class="summary-tile blue"><i class="bi bi-folder2-open"></i><div><strong>128</strong><span>Folders</span></div></div></div>
          <div class="col-sm-6 col-xl-3"><div class="summary-tile green"><i class="bi bi-file-earmark-text"></i><div><strong>2,486</strong><span>Documents</span></div></div></div>
          <div class="col-sm-6 col-xl-3"><div class="summary-tile amber"><i class="bi bi-image"></i><div><strong>742</strong><span>Media Files</span></div></div></div>
          <div class="col-sm-6 col-xl-3"><div class="summary-tile violet"><i class="bi bi-share"></i><div><strong>64</strong><span>Shared Items</span></div></div></div>
        </div>
        <div class="row g-4 mt-1">
          <div class="col-xl-8">
            <div class="panel">
              <div class="panel-head"><div><h2>Folders</h2><p>Quick access to active workspace folders</p></div><button class="btn btn-sm btn-light">New Folder</button></div>
              <div class="file-folder-grid">
                <article class="file-folder-card"><i class="bi bi-folder2-open"></i><div><strong>Product Design</strong><span>148 files</span></div><em>12.4 GB</em></article>
                <article class="file-folder-card"><i class="bi bi-folder2-open"></i><div><strong>Marketing Assets</strong><span>326 files</span></div><em>18.2 GB</em></article>
                <article class="file-folder-card"><i class="bi bi-folder2-open"></i><div><strong>Finance Reports</strong><span>84 files</span></div><em>6.8 GB</em></article>
                <article class="file-folder-card"><i class="bi bi-folder2-open"></i><div><strong>Client Handoffs</strong><span>212 files</span></div><em>9.6 GB</em></article>
              </div>
            </div>
          </div>
          <div class="col-xl-4">
            <div class="panel upload-panel">
              <div class="panel-head"><div><h2>Upload Queue</h2><p>Recently added files</p></div></div>
              <div class="upload-drop"><i class="bi bi-cloud-arrow-up"></i><strong>Drop files here</strong><span>PNG, PDF, ZIP, DOCX up to 80 MB</span></div>
              <div class="file-type-list">
                <span><i class="bi bi-file-earmark-pdf"></i><strong>Brand-guide.pdf</strong><em>4.8 MB</em></span>
                <span><i class="bi bi-file-earmark-zip"></i><strong>Landing-assets.zip</strong><em>28 MB</em></span>
                <span><i class="bi bi-file-earmark-spreadsheet"></i><strong>Q2-report.xlsx</strong><em>1.7 MB</em></span>
              </div>
            </div>
          </div>
        </div>
        <div class="row g-4 mt-1">
          <div class="col-12">
            <div class="panel">
              <div class="panel-head"><div><h2>Recent Files</h2><p>Latest files shared with the team</p></div><div class="segmented"><button>All</button><button class="active">Shared</button><button>Starred</button></div></div>
              <div class="table-responsive"><table class="table align-middle dash-table"><thead><tr><th>Name</th><th>Owner</th><th>Modified</th><th>Size</th><th>Status</th></tr></thead><tbody><tr><td><strong>Dashboard-wireframe.fig</strong></td><td><span class="person-line"><span class="rep-avatar has-photo"><img src="{{ asset('assets/img/team/team-2.jpg') }}" alt="Sara Ahmed"></span><span>Sara Ahmed</span></span></td><td>Today, 10:24</td><td>18.4 MB</td><td><span class="status paid">Shared</span></td></tr><tr><td><strong>Sales-export.csv</strong></td><td><span class="person-line"><span class="rep-avatar has-photo"><img src="{{ asset('assets/img/team/team-1.jpg') }}" alt="Donald Risher"></span><span>Donald Risher</span></span></td><td>Yesterday</td><td>2.2 MB</td><td><span class="status pending">Review</span></td></tr><tr><td><strong>Product-roadmap.pdf</strong></td><td><span class="person-line"><span class="rep-avatar has-photo"><img src="{{ asset('assets/img/team/team-4.jpg') }}" alt="Jon Lee"></span><span>Jon Lee</span></span></td><td>Jun 14, 2026</td><td>9.1 MB</td><td><span class="status paid">Shared</span></td></tr></tbody></table></div>
            </div>
          </div>
        </div>
@endsection
