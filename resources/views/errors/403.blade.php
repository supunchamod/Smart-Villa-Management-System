@extends('layouts.app')

@section('title', 'Access Restricted | '.$globalSettings->villa_name.' Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Access Restricted</h1></li>
            </ol>
          </nav>
        </div>
        <div class="row g-4">
          <div class="col-12">
            <div class="empty-state">
              <i class="bi bi-shield-lock"></i>
              <h2>You don't have permission to view this page</h2>
              <p>{{ $exception->getMessage() ?: 'Ask the villa owner to grant you access from the Team page.' }}</p>
              <a class="btn btn-primary" href="{{ route('dashboard') }}"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
            </div>
          </div>
        </div>
@endsection
