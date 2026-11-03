{{--
  "View Details" modal for a booking (opened by the eye icon in the bookings list).

  Props: $booking (with room + extras).

  Shows the booking as a branded guest bill (.vb-sheet) and offers:
    - Download PDF Invoice   (server-side DomPDF, same invoice as elsewhere)
    - Download Bill Image    (html2canvas snapshot of the bill, client-side)
    - Share on WhatsApp      (Web Share with the image where supported, else
                              saves the image and opens the guest's chat)

  The bill itself deliberately uses fixed light colours and no media
  queries, so the exported image looks identical regardless of the viewer's
  theme or screen size.
--}}
@php
    $currency = $globalSettings->currency;
    $money = fn ($amount) => $currency.' '.number_format((float) $amount, 2);
    $isStay = $booking->booking_type !== 'safari_only';
    $hasSafari = $booking->booking_type !== 'accommodation_only';
    $isCheckedOut = $booking->status === 'checked_out';
    $statusLabels = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'checked_out' => 'Checked Out', 'cancelled' => 'Cancelled'];
    $methodLabels = ['cash' => 'Cash', 'card' => 'Card', 'bank_transfer' => 'Bank Transfer'];
    $guests = ((int) $booking->guests_adults) + ((int) $booking->guests_children);
    $billId = 'billSheet'.$booking->id;
    $pdfUrl = $booking->status === 'cancelled'
        ? null
        : route($isCheckedOut ? 'bookings.invoice.final' : 'bookings.invoice.confirmation', [$booking, 'download' => 1]);
    $chatNumber = $booking->customer_phone
        ? str_replace('@c.us', '', app(\App\Services\WhatsAppService::class)->formatPhoneNumber($booking->customer_phone))
        : null;
    $fileName = 'bill-'.$booking->id.'-'.\Illuminate\Support\Str::slug($booking->customer_name).'.png';
@endphp
<div class="modal fade" id="quickViewModal{{ $booking->id }}" tabindex="-1" aria-labelledby="quickViewTitle{{ $booking->id }}" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down vb-dialog">
    <div class="modal-content dash-modal" x-data="bookingBill(@js(['billId' => $billId, 'fileName' => $fileName, 'chatNumber' => $chatNumber, 'customer' => $booking->customer_name]))">
      <div class="modal-header">
        <div>
          <span class="eyebrow">Booking #{{ $booking->id }}</span>
          <h2 class="modal-title" id="quickViewTitle{{ $booking->id }}">{{ $booking->customer_name }}</h2>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body vb-body">
        {{-- ======== The guest bill (this block is what gets exported as an image) ======== --}}
        <div class="vb-sheet" id="{{ $billId }}">
          <div class="vb-head">
            <div class="vb-brand">
              @if ($globalSettings->logo_url)
                <img class="vb-logo" src="{{ $globalSettings->logo_url }}" alt="">
              @endif
              <div>
                <div class="vb-villa">{{ $globalSettings->villa_name }}</div>
                <div class="vb-contact">
                  @if ($globalSettings->address){{ $globalSettings->address }}<br>@endif
                  @if ($globalSettings->phone_number){{ $globalSettings->phone_number }}@endif
                  @if ($globalSettings->phone_number && $globalSettings->email) · @endif
                  @if ($globalSettings->email){{ $globalSettings->email }}@endif
                </div>
              </div>
            </div>
            <div class="vb-doc">
              <div class="vb-doc-title">{{ $isCheckedOut ? 'FINAL BILL' : 'BOOKING STATEMENT' }}</div>
              <div class="vb-doc-meta">Ref: BKG-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</div>
              <div class="vb-doc-meta">Date: {{ ($booking->checked_out_at ?? now())->format('d M Y') }}</div>
            </div>
          </div>

          <div class="vb-parties">
            <div class="vb-party">
              <div class="vb-label">Billed to</div>
              <div class="vb-strong">{{ $booking->customer_name }}</div>
              @if ($booking->customer_phone)<div class="vb-muted">{{ $booking->customer_phone }}</div>@endif
              @if ($booking->customer_email)<div class="vb-muted">{{ $booking->customer_email }}</div>@endif
            </div>
            <div class="vb-party">
              <div class="vb-label">{{ $isStay ? 'Stay details' : 'Safari details' }}</div>
              @if ($booking->room)<div class="vb-strong">{{ $booking->room->name_or_number }} <span class="vb-muted">· {{ $booking->room->type }}</span></div>@endif
              @if ($isStay)
                <div class="vb-muted">{{ $booking->check_in->format('d M Y') }} → {{ $booking->check_out->format('d M Y') }} ({{ $booking->nights }} {{ \Illuminate\Support\Str::plural('night', $booking->nights) }})</div>
              @endif
              @if ($isStay && $booking->board_type_label)<div class="vb-muted">Board: {{ $booking->board_type_label }}</div>@endif
              @if ($isStay && $guests > 0)<div class="vb-muted">{{ $guests }} {{ \Illuminate\Support\Str::plural('guest', $guests) }}</div>@endif
              @if ($hasSafari && $booking->safari_tour_name)
                <div class="vb-strong">{{ $booking->safari_tour_name }}</div>
                <div class="vb-muted">{{ optional($booking->safari_date)->format('d M Y') }}@if ($booking->safari_vehicle_details) · {{ $booking->safari_vehicle_details }}@endif</div>
              @endif
            </div>
          </div>

          <div class="vb-items">
            <div class="vb-items-head"><span>Description</span><span>Amount</span></div>
            <div class="vb-item">
              <div class="vb-item-main">
                <strong>{{ $isStay ? 'Room charges' : 'Safari charges' }}</strong>
                @if ($isStay)<small>{{ $booking->nights }} {{ \Illuminate\Support\Str::plural('night', $booking->nights) }} × {{ $money((float) $booking->total_amount / max(1, $booking->nights)) }}</small>@endif
              </div>
              <span class="vb-item-amt">{{ $money($booking->total_amount) }}</span>
            </div>
            @if ($booking->extras->isNotEmpty())
              <div class="vb-group">Extra charges</div>
              @foreach ($booking->extras as $extra)
                <div class="vb-item">
                  <div class="vb-item-main">
                    <strong>{{ $extra->item_name }}</strong>
                    <small>{{ $extra->quantity }} × {{ $money($extra->unit_price) }}</small>
                  </div>
                  <span class="vb-item-amt">{{ $money($extra->line_total) }}</span>
                </div>
              @endforeach
            @endif
          </div>

          <div class="vb-totals">
            <div class="vb-row vb-row-sub"><span>Subtotal</span><span>{{ $money($booking->grand_total) }}</span></div>
            <div class="vb-row"><span>Advance paid</span><span>− {{ $money($booking->advance_payment) }}</span></div>
            @if ($isCheckedOut)
              <div class="vb-row"><span>Balance settled at checkout</span><span>{{ $money($booking->final_settlement_amount) }}</span></div>
            @endif
            <div class="vb-due {{ $booking->remaining_balance > 0 ? '' : 'is-paid' }}">
              <span>Balance due</span><strong>{{ $money($booking->remaining_balance) }}</strong>
            </div>
          </div>

          <div class="vb-foot">
            <span class="vb-status vb-status-{{ $booking->remaining_balance > 0 ? ((float) $booking->advance_payment > 0 ? 'partial' : 'unpaid') : 'paid' }}">{{ $booking->payment_status_label }}</span>
            <span class="vb-muted">
              Status: {{ $statusLabels[$booking->status] ?? ucfirst($booking->status) }}
              @if ($isCheckedOut && $booking->payment_method) · Paid by {{ $methodLabels[$booking->payment_method] ?? ucfirst(str_replace('_', ' ', $booking->payment_method)) }}@endif
            </span>
          </div>
          <div class="vb-thanks">Thank you for staying with {{ $globalSettings->villa_name }}.</div>
        </div>
      </div>

      <div class="modal-footer vb-footer">
        @if ($pdfUrl)
          <a class="btn btn-primary" href="{{ $pdfUrl }}"><i class="bi bi-file-earmark-pdf"></i> Download PDF Invoice</a>
        @endif
        <button type="button" class="btn btn-outline-primary" @click="downloadImage()" :disabled="busy">
          <i class="bi bi-image"></i> <span x-text="busy ? 'Preparing…' : 'Download Bill Image'"></span>
        </button>
        @if ($chatNumber)
          <button type="button" class="btn btn-outline-success" @click="shareWhatsApp()" :disabled="busy">
            <i class="bi bi-whatsapp"></i> Share on WhatsApp
          </button>
        @endif
        <div class="vb-footer-links">
          <a href="{{ route('bookings.show', $booking) }}">Full details</a>
          <a href="{{ route('bookings.edit', $booking) }}">Edit</a>
        </div>
        <p class="vb-error" x-show="error" x-text="error" x-cloak></p>
      </div>
    </div>
  </div>
</div>

@once
  @push('scripts')
  <script src="{{ asset('assets/vendor/html2canvas/html2canvas.min.js') }}"></script>
  <script>
    function bookingBill(config) {
        return {
            busy: false,
            error: '',
            // Renders the bill off-screen at a fixed width so the image is the same
            // size whether staff are on a phone or a desktop.
            async render() {
                const source = document.getElementById(config.billId);
                if (!source) throw new Error('Bill not found.');
                if (typeof html2canvas !== 'function') throw new Error('Image export is unavailable.');
                if (document.fonts && document.fonts.ready) await document.fonts.ready;

                const stage = document.createElement('div');
                stage.style.cssText = 'position:fixed;left:-10000px;top:0;width:720px;background:#fff;';
                const clone = source.cloneNode(true);
                clone.removeAttribute('id');
                clone.classList.add('vb-export');
                stage.appendChild(clone);
                document.body.appendChild(stage);
                try {
                    return await html2canvas(clone, { scale: 2, backgroundColor: '#ffffff', useCORS: true, logging: false });
                } finally {
                    stage.remove();
                }
            },
            toBlob(canvas) {
                return new Promise((resolve, reject) => canvas.toBlob((b) => (b ? resolve(b) : reject(new Error('Could not create the image.'))), 'image/png'));
            },
            save(blob) {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = config.fileName;
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(() => URL.revokeObjectURL(url), 2000);
            },
            async downloadImage() {
                this.busy = true; this.error = '';
                try {
                    this.save(await this.toBlob(await this.render()));
                } catch (e) {
                    console.error(e);
                    this.error = 'Could not create the bill image. Please try again.';
                } finally {
                    this.busy = false;
                }
            },
            async shareWhatsApp() {
                this.busy = true; this.error = '';
                try {
                    const blob = await this.toBlob(await this.render());
                    const file = new File([blob], config.fileName, { type: 'image/png' });
                    if (navigator.canShare && navigator.canShare({ files: [file] })) {
                        // Phones: opens the share sheet with the image attached - pick WhatsApp.
                        await navigator.share({ files: [file], title: 'Guest bill', text: 'Bill for ' + config.customer });
                    } else {
                        // Desktop: save the image, then open the guest's WhatsApp chat to attach it.
                        this.save(blob);
                        window.open('https://wa.me/' + config.chatNumber, '_blank', 'noopener');
                    }
                } catch (e) {
                    if (e && e.name === 'AbortError') return; // user closed the share sheet
                    console.error(e);
                    this.error = 'Could not share the bill. Please download the image instead.';
                } finally {
                    this.busy = false;
                }
            },
        };
    }
  </script>
  @endpush
@endonce
