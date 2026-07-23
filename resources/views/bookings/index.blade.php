@extends('layouts.app')

@section('title', 'Bookings | Dashora Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Bookings</h1></li>
            </ol>
          </nav>
          <div class="page-actions"><a class="btn btn-primary" href="{{ route('bookings.create') }}"><i class="bi bi-plus-lg"></i> Add Booking</a></div>
        </div>
        @if (session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <div class="row g-4">
          <div class="col-12">
            <div class="panel" x-data="{ tab: '{{ in_array(request('tab'), ['all', 'pending', 'active', 'completed', 'cancelled', 'today_checkins', 'today_checkouts', 'balance_due'], true) ? request('tab') : 'all' }}' }">
              <div class="panel-head"><div><h2>Bookings</h2><p>Reservations across all rooms and cabanas</p></div></div>

              <div class="booking-tabs" role="tablist" aria-label="Filter bookings by status">
                <button type="button" class="booking-tab" role="tab" :class="{ active: tab === 'all' }" :aria-selected="tab === 'all'" @click="tab = 'all'">All Bookings</button>
                <button type="button" class="booking-tab" role="tab" :class="{ active: tab === 'today_checkins' }" :aria-selected="tab === 'today_checkins'" @click="tab = 'today_checkins'">
                  Today's Check-ins
                  @if ($todayCheckInsCount > 0)
                    <span class="tab-count-badge tab-count-badge-green">{{ $todayCheckInsCount }}</span>
                  @endif
                </button>
                <button type="button" class="booking-tab" role="tab" :class="{ active: tab === 'today_checkouts' }" :aria-selected="tab === 'today_checkouts'" @click="tab = 'today_checkouts'">
                  Today's Checkouts
                  @if ($todayCheckOutsCount > 0)
                    <span class="tab-count-badge tab-count-badge-blue">{{ $todayCheckOutsCount }}</span>
                  @endif
                </button>
                <button type="button" class="booking-tab" role="tab" :class="{ active: tab === 'pending' }" :aria-selected="tab === 'pending'" @click="tab = 'pending'">
                  Pending Enquiries
                  @if ($pendingBookingsCount > 0)
                    <span class="tab-count-badge tab-count-badge-yellow">{{ $pendingBookingsCount }}</span>
                  @endif
                </button>
                <button type="button" class="booking-tab" role="tab" :class="{ active: tab === 'balance_due' }" :aria-selected="tab === 'balance_due'" @click="tab = 'balance_due'">
                  Balance Due
                  @if ($balanceDueCount > 0)
                    <span class="tab-count-badge tab-count-badge-red">{{ $balanceDueCount }}</span>
                  @endif
                </button>
                <button type="button" class="booking-tab" role="tab" :class="{ active: tab === 'active' }" :aria-selected="tab === 'active'" @click="tab = 'active'">Active</button>
                <button type="button" class="booking-tab" role="tab" :class="{ active: tab === 'completed' }" :aria-selected="tab === 'completed'" @click="tab = 'completed'">Completed</button>
                <button type="button" class="booking-tab" role="tab" :class="{ active: tab === 'cancelled' }" :aria-selected="tab === 'cancelled'" @click="tab = 'cancelled'">Cancelled</button>
              </div>

              <div class="d-none d-md-block">
                <div class="table-responsive">
                  <table class="table align-middle dash-table">
                    <thead>
                      <tr>
                        <th>Customer</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Total</th>
                        <th>Advance</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      @php $wa = app(\App\Services\WhatsAppService::class); @endphp
                      @forelse ($bookings as $booking)
                        @php
                          $today = today();
                          $rowTabs = ['all', ['pending' => 'pending', 'checked_out' => 'completed', 'cancelled' => 'cancelled'][$booking->status] ?? 'active'];
                          if ($booking->status === 'confirmed' && $booking->check_in->isSameDay($today)) $rowTabs[] = 'today_checkins';
                          if ($booking->status === 'confirmed' && $booking->check_out->isSameDay($today)) $rowTabs[] = 'today_checkouts';
                          if ($booking->status === 'confirmed' && (float) $booking->advance_payment < (float) $booking->total_amount) $rowTabs[] = 'balance_due';
                          $badge = ['pending' => 'pending', 'confirmed' => 'new', 'checked_out' => 'won', 'cancelled' => 'stuck'][$booking->status] ?? 'new';
                        @endphp
                        <tr x-show="@json($rowTabs).includes(tab)">
                          <td><strong>{{ $booking->customer_name }}</strong></td>
                          <td>{{ $booking->room->name_or_number }}</td>
                          <td>{{ $booking->check_in->format('d M Y') }}</td>
                          <td>{{ $booking->check_out->format('d M Y') }}</td>
                          <td>{{ number_format($booking->total_amount, 2) }}</td>
                          <td>{{ number_format($booking->advance_payment, 2) }}</td>
                          <td>{{ number_format($booking->remaining_balance, 2) }}</td>
                          <td>
                            <span class="deal-badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                          </td>
                          <td>
                            <div class="booking-row-actions">
                              @if ($booking->status === 'pending')
                                <form method="POST" action="{{ route('bookings.confirm', $booking) }}">
                                  @csrf
                                  <button class="btn btn-sm btn-success" type="submit"><i class="bi bi-check-lg"></i> Accept</button>
                                </form>
                                <form method="POST" action="{{ route('bookings.decline', $booking) }}" onsubmit="return confirm('Decline this booking request?');">
                                  @csrf
                                  <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-x-lg"></i> Decline</button>
                                </form>
                              @elseif ($booking->status === 'confirmed')
                                <button type="button" class="btn btn-sm {{ $booking->remaining_balance > 0 ? 'btn-warning' : 'btn-success' }}" data-bs-toggle="modal" data-bs-target="#checkoutModal{{ $booking->id }}">
                                  <i class="bi bi-box-arrow-right"></i> {{ $booking->remaining_balance > 0 ? 'Collect Balance & Checkout' : 'Checkout' }}
                                </button>
                              @endif

                              @if ($booking->status !== 'cancelled' && $booking->status !== 'pending')
                                <a class="btn btn-sm btn-outline-primary" href="{{ route($booking->status === 'checked_out' ? 'bookings.invoice.final' : 'bookings.invoice.confirmation', $booking) }}" target="_blank" rel="noopener" data-bs-toggle="tooltip" title="Download Invoice / Confirmation PDF" aria-label="Download invoice"><i class="bi bi-file-earmark-pdf"></i></a>
                              @endif

                              @if ($booking->customer_phone)
                                <div class="dropdown">
                                  <button class="btn btn-sm btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Send WhatsApp message" aria-label="Send WhatsApp message"><i class="bi bi-whatsapp"></i></button>
                                  <ul class="dropdown-menu dropdown-menu-end wa-dropdown-menu">
                                    <li>
                                      <a class="dropdown-item wa-send-btn" href="{{ $wa->getConfirmationUrl($booking) }}" target="_blank" rel="noopener" data-booking-id="{{ $booking->id }}" data-wa-type="confirmation">
                                        <span>🟢 Send Confirmation</span>
                                        @if ($booking->wa_confirmation_sent_at)<span class="wa-sent-badge">Sent {{ $booking->wa_confirmation_sent_at->format('h:i A') }}</span>@endif
                                      </a>
                                    </li>
                                    <li>
                                      <a class="dropdown-item wa-send-btn" href="{{ $wa->getPreCheckinReminderUrl($booking) }}" target="_blank" rel="noopener" data-booking-id="{{ $booking->id }}" data-wa-type="reminder">
                                        <span>🔵 Send Pre-Checkin Reminder</span>
                                        @if ($booking->wa_reminder_sent_at)<span class="wa-sent-badge">Sent {{ $booking->wa_reminder_sent_at->format('h:i A') }}</span>@endif
                                      </a>
                                    </li>
                                    <li>
                                      <a class="dropdown-item wa-send-btn" href="{{ $wa->getCheckinDetailsUrl($booking) }}" target="_blank" rel="noopener" data-booking-id="{{ $booking->id }}" data-wa-type="checkin">
                                        <span>📍 Send Location &amp; Check-in Info</span>
                                        @if ($booking->wa_checkin_sent_at)<span class="wa-sent-badge">Sent {{ $booking->wa_checkin_sent_at->format('h:i A') }}</span>@endif
                                      </a>
                                    </li>
                                    <li>
                                      <a class="dropdown-item wa-send-btn" href="{{ $wa->getCheckoutThankYouUrl($booking) }}" target="_blank" rel="noopener" data-booking-id="{{ $booking->id }}" data-wa-type="thankyou">
                                        <span>⭐ Send Thank You &amp; Review Request</span>
                                        @if ($booking->wa_thankyou_sent_at)<span class="wa-sent-badge">Sent {{ $booking->wa_thankyou_sent_at->format('h:i A') }}</span>@endif
                                      </a>
                                    </li>
                                  </ul>
                                </div>
                              @endif

                              <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#quickViewModal{{ $booking->id }}" aria-label="Quick view"><i class="bi bi-eye"></i></button>

                              <div class="dropdown">
                                <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions"><i class="bi bi-three-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                  <li><a class="dropdown-item" href="{{ route('bookings.edit', $booking) }}"><i class="bi bi-pencil me-2"></i>Edit Booking</a></li>
                                  <li><hr class="dropdown-divider"></li>
                                  <li>
                                    <form method="POST" action="{{ route('bookings.destroy', $booking) }}" onsubmit="return confirm('Delete this booking?');">
                                      @csrf
                                      @method('DELETE')
                                      <button class="dropdown-item text-danger" type="submit"><i class="bi bi-trash me-2"></i>Delete Booking</button>
                                    </form>
                                  </li>
                                </ul>
                              </div>
                            </div>
                          </td>
                        </tr>
                      @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No bookings yet. Add your first booking to get started.</td></tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="d-block d-md-none">
                <div class="mdash-booking-list">
                  @forelse ($bookings as $booking)
                    @php
                      $today = today();
                      $rowTabs = ['all', ['pending' => 'pending', 'checked_out' => 'completed', 'cancelled' => 'cancelled'][$booking->status] ?? 'active'];
                      if ($booking->status === 'confirmed' && $booking->check_in->isSameDay($today)) $rowTabs[] = 'today_checkins';
                      if ($booking->status === 'confirmed' && $booking->check_out->isSameDay($today)) $rowTabs[] = 'today_checkouts';
                      if ($booking->status === 'confirmed' && (float) $booking->advance_payment < (float) $booking->total_amount) $rowTabs[] = 'balance_due';
                      $badge = ['pending' => 'pending', 'confirmed' => 'new', 'checked_out' => 'won', 'cancelled' => 'stuck'][$booking->status] ?? 'new';
                    @endphp
                    <div class="mdash-booking-card stacked" x-show="@json($rowTabs).includes(tab)">
                      <div class="mdash-card-top">
                        <span class="mdash-avatar sm">{{ $booking->customer_initials }}</span>
                        <div class="mdash-booking-info">
                          <strong>{{ $booking->customer_name }}</strong>
                          <small>{{ $booking->room->name_or_number }} &middot; {{ $booking->check_in->format('d M') }} - {{ $booking->check_out->format('d M Y') }}</small>
                        </div>
                        <span class="deal-badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                      </div>

                      <div class="mdash-card-meta">
                        <span>Total <strong>{{ number_format($booking->total_amount, 2) }}</strong></span>
                        <span>Balance <strong>{{ number_format($booking->remaining_balance, 2) }}</strong></span>
                      </div>

                      @if ($booking->status === 'pending')
                        <div class="booking-request-actions">
                          <form method="POST" action="{{ route('bookings.confirm', $booking) }}">
                            @csrf
                            <button type="submit" class="mdash-action-btn mdash-action-btn-success"><i class="bi bi-check-lg"></i> Accept &amp; Confirm</button>
                          </form>
                          <form method="POST" action="{{ route('bookings.decline', $booking) }}" onsubmit="return confirm('Decline this booking request?');">
                            @csrf
                            <button type="submit" class="mdash-action-btn mdash-action-btn-outline-danger"><i class="bi bi-x-lg"></i> Decline</button>
                          </form>
                        </div>
                      @elseif ($booking->status === 'confirmed')
                        <a
                          href="{{ route('bookings.invoice.confirmation', $booking) }}"
                          class="mdash-action-btn mdash-action-btn-primary"
                          target="_blank"
                          rel="noopener"
                        ><i class="bi bi-file-earmark-arrow-down"></i> Download Confirmation PDF</a>

                        <button
                          type="button"
                          class="mdash-action-btn {{ $booking->remaining_balance > 0 ? 'mdash-action-btn-amber' : 'mdash-action-btn-success' }}"
                          data-bs-toggle="modal"
                          data-bs-target="#checkoutModal{{ $booking->id }}"
                        ><i class="bi bi-box-arrow-right"></i> {{ $booking->remaining_balance > 0 ? 'Collect Balance & Checkout' : 'Checkout' }}</button>

                        @include('bookings.partials.wa-mobile-dropdown', ['booking' => $booking])
                      @elseif ($booking->status === 'checked_out')
                        <a
                          href="{{ route('bookings.invoice.final', $booking) }}"
                          class="mdash-action-btn mdash-action-btn-success"
                          target="_blank"
                          rel="noopener"
                        ><i class="bi bi-file-earmark-check"></i> Download Final Invoice PDF</a>

                        @include('bookings.partials.wa-mobile-dropdown', ['booking' => $booking])
                      @endif

                      <div class="mdash-card-actions">
                        <button class="mdash-icon-btn" type="button" data-bs-toggle="modal" data-bs-target="#quickViewModal{{ $booking->id }}" aria-label="Quick view"><i class="bi bi-eye"></i></button>
                        <a class="mdash-icon-btn" href="{{ route('bookings.edit', $booking) }}" aria-label="Edit booking"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('bookings.destroy', $booking) }}" onsubmit="return confirm('Delete this booking?');">
                          @csrf
                          @method('DELETE')
                          <button class="mdash-icon-btn danger" type="submit" aria-label="Delete booking"><i class="bi bi-trash"></i></button>
                        </form>
                      </div>
                    </div>
                  @empty
                    <p class="mdash-empty">No bookings yet. Add your first booking to get started.</p>
                  @endforelse
                </div>
              </div>

              @if ($bookings->hasPages())
                <div class="mt-3">{{ $bookings->links() }}</div>
              @endif
            </div>
          </div>
        </div>

        {{-- Checkout & Quick View modals - one per booking on this page, shared by
             both the desktop table and mobile cards above. --}}
        @foreach ($bookings as $booking)
          @if ($booking->status === 'confirmed')
            <div class="modal fade" id="checkoutModal{{ $booking->id }}" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content dash-modal">
                  <form method="POST" action="{{ route('bookings.checkout', $booking) }}">
                    @csrf
                    <div class="modal-header">
                      <div><span class="eyebrow">Checkout</span><h2 class="modal-title">Checkout {{ $booking->customer_name }}?</h2></div>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="balance-grid mb-3">
                        <div class="balance-stat"><span>Total Amount</span><strong>{{ $globalSettings->currency }} {{ number_format($booking->total_amount, 2) }}</strong></div>
                        <div class="balance-stat"><span>Advance Paid</span><strong>{{ $globalSettings->currency }} {{ number_format($booking->advance_payment, 2) }}</strong></div>
                        <div class="balance-stat"><span>Balance Due</span><strong>{{ $globalSettings->currency }} {{ number_format($booking->remaining_balance, 2) }}</strong></div>
                      </div>
                      @if ($booking->remaining_balance > 0)
                        <div class="mb-3">
                          <label class="form-label" for="payment_method{{ $booking->id }}">Payment method for the balance received</label>
                          <select class="form-select" name="payment_method" id="payment_method{{ $booking->id }}">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                          </select>
                        </div>
                        <p class="text-muted small mb-0">This confirms the remaining balance has been received. The booking will be marked as checked out and the final invoice will be generated.</p>
                      @else
                        <p class="text-muted small mb-0">This booking is already paid in full. Confirming will mark it as checked out and generate the final invoice.</p>
                      @endif
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> Confirm Checkout &amp; Complete</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          @endif

          @php
            $badge = ['pending' => 'pending', 'confirmed' => 'new', 'checked_out' => 'won', 'cancelled' => 'stuck'][$booking->status] ?? 'new';
          @endphp
          <div class="modal fade" id="quickViewModal{{ $booking->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content dash-modal">
                <div class="modal-header">
                  <div><span class="eyebrow">Quick View</span><h2 class="modal-title">{{ $booking->customer_name }}</h2></div>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="qv-section-label">Guest</div>
                  <div class="qv-row"><span>Phone</span><strong>{{ $booking->customer_phone ?: '—' }}</strong></div>
                  <div class="qv-row"><span>Email</span><strong>{{ $booking->customer_email ?: '—' }}</strong></div>

                  <div class="qv-section-label">Stay</div>
                  <div class="qv-row"><span>Cabana</span><strong>{{ $booking->room->name_or_number }}</strong></div>
                  <div class="qv-row"><span>Check-in</span><strong>{{ $booking->check_in->format('d M Y') }}</strong></div>
                  <div class="qv-row"><span>Check-out</span><strong>{{ $booking->check_out->format('d M Y') }}</strong></div>
                  @if ($booking->board_type_label)
                    <div class="qv-row"><span>Board Type</span><strong>{{ $booking->board_type_label }}</strong></div>
                  @endif
                  <div class="qv-row"><span>Status</span><span class="deal-badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span></div>

                  <div class="qv-section-label">Payment</div>
                  <div class="qv-row"><span>Total Amount</span><strong>{{ $globalSettings->currency }} {{ number_format($booking->total_amount, 2) }}</strong></div>
                  <div class="qv-row"><span>Advance Paid</span><strong>{{ $globalSettings->currency }} {{ number_format($booking->advance_payment, 2) }}</strong></div>
                  <div class="qv-row"><span>Balance Due</span><strong>{{ $globalSettings->currency }} {{ number_format($booking->remaining_balance, 2) }}</strong></div>
                  <div class="qv-row"><span>Payment Status</span><strong>{{ $booking->payment_status_label }}</strong></div>

                  <div class="qv-section-label">WhatsApp Messages</div>
                  <div class="qv-row"><span>🟢 Confirmation</span><strong>{{ $booking->wa_confirmation_sent_at ? 'Sent at '.$booking->wa_confirmation_sent_at->format('h:i A') : 'Not sent' }}</strong></div>
                  <div class="qv-row"><span>🔵 Pre-Checkin Reminder</span><strong>{{ $booking->wa_reminder_sent_at ? 'Sent at '.$booking->wa_reminder_sent_at->format('h:i A') : 'Not sent' }}</strong></div>
                  <div class="qv-row"><span>📍 Location &amp; Check-in Info</span><strong>{{ $booking->wa_checkin_sent_at ? 'Sent at '.$booking->wa_checkin_sent_at->format('h:i A') : 'Not sent' }}</strong></div>
                  <div class="qv-row"><span>⭐ Thank You &amp; Review</span><strong>{{ $booking->wa_thankyou_sent_at ? 'Sent at '.$booking->wa_thankyou_sent_at->format('h:i A') : 'Not sent' }}</strong></div>
                </div>
                <div class="modal-footer">
                  <a class="btn btn-light" href="{{ route('bookings.edit', $booking) }}"><i class="bi bi-pencil"></i> Edit</a>
                  @if ($booking->status !== 'cancelled')
                    <a class="btn btn-outline-primary" href="{{ route($booking->status === 'checked_out' ? 'bookings.invoice.final' : 'bookings.invoice.confirmation', $booking) }}" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf"></i> Download PDF</a>
                  @endif
                  @if ($booking->customer_phone)
                    <div class="dropdown">
                      <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-whatsapp"></i> Send WhatsApp</button>
                      <ul class="dropdown-menu dropdown-menu-end wa-dropdown-menu">
                        <li><a class="dropdown-item wa-send-btn" href="{{ $wa->getConfirmationUrl($booking) }}" target="_blank" rel="noopener" data-booking-id="{{ $booking->id }}" data-wa-type="confirmation"><span>🟢 Send Confirmation</span></a></li>
                        <li><a class="dropdown-item wa-send-btn" href="{{ $wa->getPreCheckinReminderUrl($booking) }}" target="_blank" rel="noopener" data-booking-id="{{ $booking->id }}" data-wa-type="reminder"><span>🔵 Send Pre-Checkin Reminder</span></a></li>
                        <li><a class="dropdown-item wa-send-btn" href="{{ $wa->getCheckinDetailsUrl($booking) }}" target="_blank" rel="noopener" data-booking-id="{{ $booking->id }}" data-wa-type="checkin"><span>📍 Send Location &amp; Check-in Info</span></a></li>
                        <li><a class="dropdown-item wa-send-btn" href="{{ $wa->getCheckoutThankYouUrl($booking) }}" target="_blank" rel="noopener" data-booking-id="{{ $booking->id }}" data-wa-type="thankyou"><span>⭐ Send Thank You &amp; Review Request</span></a></li>
                      </ul>
                    </div>
                  @endif
                </div>
              </div>
            </div>
          </div>
        @endforeach
@endsection

@push('scripts')
  @include('partials.whatsapp-log-script')
@endpush
