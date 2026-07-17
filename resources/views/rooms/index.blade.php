@extends('layouts.app')

@section('title', 'Rooms | Dashora Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Rooms</h1></li>
            </ol>
          </nav>
          <div class="page-actions"><a class="btn btn-primary" href="{{ route('rooms.create') }}"><i class="bi bi-plus-lg"></i> Add Room</a></div>
        </div>
        @if (session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        <div class="row g-4">
          <div class="col-12">
            <div class="panel">
              <div class="panel-head"><div><h2>Rooms &amp; Cabanas</h2><p>Manage room inventory, pricing, and availability</p></div></div>
              <div class="table-responsive">
                <table class="table align-middle dash-table">
                  <thead>
                    <tr>
                      <th>Room / Number</th>
                      <th>Type</th>
                      <th>Price / Night</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($rooms as $room)
                      <tr>
                        <td><strong>{{ $room->name_or_number }}</strong></td>
                        <td>{{ $room->type }}</td>
                        <td>{{ $globalSettings->currency }} {{ number_format($room->price_per_night, 2) }}</td>
                        <td><span class="status {{ $room->status === 'available' ? 'paid' : 'pending' }}">{{ ucfirst($room->status) }}</span></td>
                        <td>
                          <div class="d-flex gap-2">
                            <a class="btn btn-sm btn-light" href="{{ route('rooms.edit', $room) }}" aria-label="Edit room"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('rooms.toggle-status', $room) }}">
                              @csrf
                              @method('PATCH')
                              <button class="btn btn-sm btn-light" type="submit" title="Toggle availability"><i class="bi bi-arrow-repeat"></i></button>
                            </form>
                            <form method="POST" action="{{ route('rooms.destroy', $room) }}" onsubmit="return confirm('Delete this room?');">
                              @csrf
                              @method('DELETE')
                              <button class="btn btn-sm btn-light text-danger" type="submit" aria-label="Delete room"><i class="bi bi-trash"></i></button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    @empty
                      <tr><td colspan="5" class="text-center text-muted py-4">No rooms yet. Add your first room to get started.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
              @if ($rooms->hasPages())
                <div class="mt-3">{{ $rooms->links() }}</div>
              @endif
            </div>
          </div>
        </div>
@endsection
