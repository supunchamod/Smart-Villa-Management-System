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
    <div class="modal-content dash-modal" x-data="bookingBill(@js([
        'billId' => $billId,
        'fileName' => $fileName,
        'chatNumber' => $chatNumber,
        'customer' => $booking->customer_name,
        'currency' => $currency,
        'revenue' => (float) $booking->grand_total,
        'storeUrl' => $booking->status === 'cancelled' ? null : route('bookings.expenses.store', $booking),
        'costs' => $booking->expenses->map(fn ($expense) => $expense->toListItem())->values()->all(),
        'categories' => \App\Models\Expense::BOOKING_CATEGORIES,
        'today' => today()->toDateString(),
    ]))">
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
              @if ($booking->room)<div class="vb-strong">{{ $booking->room->name_or_number }} <span class="vb-muted">· {{ $booking->room->type }}</span></div>@elseif ($booking->stay_hotel_name)<div class="vb-strong">{{ $booking->stay_hotel_name }} <span class="vb-muted">· Stay hotel</span></div>@endif
              @if ($isStay)
                <div class="vb-muted">{{ $booking->check_in->format('d M Y') }} → {{ $booking->check_out->format('d M Y') }} ({{ $booking->nights }} {{ \Illuminate\Support\Str::plural('night', $booking->nights) }})</div>
              @endif
              @if ($isStay && $booking->board_type_label)<div class="vb-muted">Board: {{ $booking->board_type_label }}</div>@endif
              @if ($isStay && $guests > 0)<div class="vb-muted">{{ $guests }} {{ \Illuminate\Support\Str::plural('guest', $guests) }}</div>@endif
              @if ($hasSafari && $booking->safari_tour_name)
                <div class="vb-strong">{{ $booking->safari_tour_name }}</div>
                <div class="vb-muted">{{ optional($booking->safari_date)->format('d M Y') }}@if ($booking->safari_vehicle_details) · {{ $booking->safari_vehicle_details }}@endif</div>
                @if ($booking->pickup_location || $booking->pickup_time)<div class="vb-muted">Pickup: {{ $booking->pickup_location }}@if ($booking->pickup_time) at {{ $booking->pickup_time_short }}@endif</div>@endif
                @if ($booking->group_pax_size)<div class="vb-muted">{{ $booking->group_pax_size }} pax</div>@endif
                @if ($booking->include_lunch || $booking->include_drone_videos)<div class="vb-muted">Includes: {{ collect([$booking->include_lunch ? 'Lunch' : null, $booking->include_drone_videos ? 'Drone videos' : null])->filter()->implode(', ') }}</div>@endif
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

        @if ($booking->special_notes)
          <section class="vc-notes">
            <h3><i class="bi bi-chat-left-text"></i> Special Notes</h3>
            <p>{{ $booking->special_notes }}</p>
          </section>
        @endif

        {{-- ======== Booking expenses / direct costs (internal; deliberately outside the exported bill) ======== --}}
        <section class="vc-costs">
          <header class="vc-costs-head">
            <div>
              <h3>Booking Expenses / Direct Costs</h3>
              <p>Costs the villa pays for this booking. Not on the guest bill.</p>
            </div>
            <span class="vc-costs-total" x-text="money(costsTotal)"></span>
          </header>

          <ul class="vc-costs-list" x-show="costs.length > 0">
            <template x-for="cost in costs" :key="cost.id">
              <li class="vc-cost-row">
                <div class="vc-cost-main">
                  <strong x-text="cost.category"></strong>
                  <small x-show="cost.description" x-text="cost.description"></small>
                  <small class="vc-cost-date" x-text="cost.date"></small>
                </div>
                <span class="vc-cost-amt" x-text="money(cost.amount)"></span>
                <button type="button" class="vc-cost-del" @click="deleteCost(cost)" :disabled="deleting === cost.id" aria-label="Delete expense"><i class="bi bi-trash3"></i></button>
              </li>
            </template>
          </ul>
          <p class="vc-costs-empty" x-show="costs.length === 0">No direct costs recorded for this booking yet.</p>

          <form class="vc-cost-form" x-show="storeUrl" @submit.prevent="addCost()">
            <input type="text" class="form-control" x-model="form.category" list="vcCats{{ $booking->id }}" placeholder="Category" aria-label="Category" maxlength="100" required>
            <datalist id="vcCats{{ $booking->id }}"><template x-for="cat in categories" :key="cat"><option :value="cat"></option></template></datalist>
            <input type="text" class="form-control vc-cost-desc" x-model="form.description" placeholder="Description (optional)" aria-label="Description" maxlength="255">
            <input type="number" class="form-control" x-model.number="form.amount" min="0.01" step="0.01" inputmode="decimal" placeholder="Amount" aria-label="Amount" required>
            <input type="date" class="form-control" x-model="form.expense_date" :max="today" aria-label="Date">
            <button type="submit" class="btn btn-primary" :disabled="adding"><i class="bi bi-plus-lg"></i> <span x-text="adding ? 'Adding…' : 'Add'"></span></button>
          </form>
          <p class="vb-error" x-show="costError" x-text="costError" x-cloak></p>

          <div class="vc-costs-profit" :class="{ 'is-loss': profit < 0 }">
            <span>Net profit on this booking <small>(room + extras − direct costs)</small></span>
            <strong x-text="money(profit)"></strong>
          </div>
        </section>
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
            // ---- booking expenses (internal direct costs) ----
            costs: config.costs || [],
            categories: config.categories || [],
            storeUrl: config.storeUrl,
            today: config.today,
            form: { category: '', description: '', amount: '', expense_date: config.today },
            adding: false,
            deleting: null,
            costError: '',
            money(value) {
                return config.currency + ' ' + Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            get costsTotal() {
                return this.costs.reduce((sum, c) => sum + (Number(c.amount) || 0), 0);
            },
            get profit() {
                return Math.round((config.revenue - this.costsTotal) * 100) / 100;
            },
            headers() {
                return { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content };
            },
            async addCost() {
                if (this.adding) return;
                this.adding = true; this.costError = '';
                try {
                    const response = await fetch(this.storeUrl, { method: 'POST', headers: this.headers(), body: JSON.stringify(this.form) });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        const first = data.errors ? Object.values(data.errors)[0][0] : null;
                        throw new Error(first || data.message || 'Could not add the expense.');
                    }
                    this.costs.push(data.expense);
                    this.form = { category: '', description: '', amount: '', expense_date: this.today };
                } catch (e) {
                    this.costError = e.message;
                } finally {
                    this.adding = false;
                }
            },
            async deleteCost(cost) {
                if (this.deleting || !window.confirm('Delete this expense?')) return;
                this.deleting = cost.id; this.costError = '';
                try {
                    const response = await fetch(cost.url, { method: 'DELETE', headers: this.headers() });
                    if (!response.ok) throw new Error('Could not delete the expense.');
                    this.costs = this.costs.filter((c) => c.id !== cost.id);
                } catch (e) {
                    this.costError = e.message;
                } finally {
                    this.deleting = null;
                }
            },
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
