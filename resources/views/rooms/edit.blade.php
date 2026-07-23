@extends('layouts.app')

@section('title', 'Edit Room | '.$globalSettings->villa_name.' Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li><a href="{{ route('rooms.index') }}">Rooms</a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Edit Room</h1></li>
            </ol>
          </nav>
        </div>
        <div class="row g-4">
          <div class="col-xl-8">
            <div class="panel">
              <div class="panel-head"><div><h2>Edit Room</h2><p>{{ $room->name_or_number }}</p></div></div>
              <form method="POST" action="{{ route('rooms.update', $room) }}">
                @csrf
                @method('PUT')
                @include('rooms._form')
              </form>
            </div>
          </div>
        </div>
@endsection
