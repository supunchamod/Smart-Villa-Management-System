@extends('layouts.app')

@section('title', 'Edit Booking | '.$globalSettings->villa_name.' Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li><a href="{{ route('bookings.index') }}">Bookings</a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Edit Booking</h1></li>
            </ol>
          </nav>
        </div>
        <div class="row g-4">
          <div class="col-xl-9">
            <div class="panel">
              <div class="panel-head"><div><h2>Edit Booking</h2><p>{{ $booking->customer_name }}</p></div></div>
              <form method="POST" action="{{ route('bookings.update', $booking) }}">
                @csrf
                @method('PUT')
                @include('bookings._form')
              </form>
            </div>
          </div>
        </div>
@endsection
