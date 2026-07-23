@extends('layouts.app')

@section('title', 'Edit Cabana Type | Dashora Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li><a href="{{ route('landing-page.index') }}">Landing Page</a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Edit Cabana Type</h1></li>
            </ol>
          </nav>
        </div>
        <div class="row g-4">
          <div class="col-xl-10">
            <div class="panel">
              <div class="panel-head"><div><h2>Edit Cabana Type</h2><p>{{ $cabanaType->name }}</p></div></div>
              <form method="POST" action="{{ route('landing-page.cabana-types.update', $cabanaType) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('admin.landing-page._cabana_type_form')
              </form>
            </div>
          </div>
        </div>
@endsection
