@extends('layouts.app')

@section('title', 'Add Booking | '.$globalSettings->villa_name.' Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li><a href="{{ route('bookings.index') }}">Bookings</a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Add Booking</h1></li>
            </ol>
          </nav>
        </div>
        <div class="row g-4">
          <div class="col-xl-9">
            <div class="panel">
              <div class="panel-head"><div><h2>Add Booking</h2><p>Reserve a room for a customer</p></div></div>
              <form method="POST" action="{{ route('bookings.store') }}">
                @csrf
                @include('bookings._form')
              </form>
            </div>
          </div>
        </div>
@endsection
