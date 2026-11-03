{{--
  Checkout & payment modal for a confirmed booking.

  Props: $booking (with room), $modalId (unique DOM id).

  Posts to bookings.checkout with the extra charges (extras[i][item_name|
  quantity|unit_price]) and the payment method. The server recomputes every
  figure; the totals shown here are a live preview of
      balance = room charges + extras - advance paid
--}}
@php
    $currency = $globalSettings->currency;
    $isStay = $booking->booking_type !== 'safari_only';
    $nights = $booking->nights;
    $baseLabel = $isStay
        ? $nights.' '.\Illuminate\Support\Str::plural('night', $nights).' × '.$currency.' '.number_format((float) $booking->total_amount / max(1, $nights), 2)
        : 'Safari booking';
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content dash-modal">
      <form
        method="POST"
        action="{{ route('bookings.checkout', $booking) }}"
        x-data="checkoutModal(@js([
            'currency' => $currency,
            'baseAmount' => (float) $booking->total_amount,
            'advance' => (float) $booking->advance_payment,
        ]))"
        @submit="submitting = true"
      >
        @csrf
        <div class="modal-header">
          <div>
            <span class="eyebrow">Checkout &amp; Payment</span>
            <h2 class="modal-title">{{ $booking->customer_name }}</h2>
            <div class="co-meta">
              <span><i class="bi bi-door-open"></i> {{ $booking->room_label }}</span>
              @if ($isStay)
                <span><i class="bi bi-calendar3"></i> {{ $booking->check_in->format('d M') }} → {{ $booking->check_out->format('d M Y') }}</span>
              @endif
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="co-section">
            <div class="co-section-title">Room charges</div>
            <div class="co-line">
              <div>
                <strong>{{ $booking->room ? $booking->room->name_or_number : $booking->safari_tour_name }}</strong>
                <small>{{ $baseLabel }}</small>
              </div>
              <strong x-text="money(baseAmount)"></strong>
            </div>
          </div>

          <div class="co-section">
            <div class="co-section-title">
              Extra charges
              <small>Anything consumed during the stay</small>
            </div>

            <div class="co-chips">
              <template x-for="preset in presets" :key="preset">
                <button type="button" class="co-chip" @click="addItem(preset)"><i class="bi bi-plus-lg"></i> <span x-text="preset"></span></button>
              </template>
            </div>

            <div class="co-empty" x-show="items.length === 0">No extra charges added. Use a quick-add button above, or add a custom item.</div>

            <div class="co-rows" x-show="items.length > 0">
              <div class="co-row co-row-head">
                <span>Item</span><span>Qty</span><span>Unit price</span><span class="text-end">Amount</span><span></span>
              </div>
              <template x-for="(item, index) in items" :key="item.key">
                <div class="co-row">
                  <input type="text" class="form-control form-control-sm" :name="`extras[${index}][item_name]`" x-model="item.name" placeholder="Item name (e.g. Dinner)" maxlength="120" required>
                  <input type="number" class="form-control form-control-sm" :name="`extras[${index}][quantity]`" x-model.number="item.quantity" min="1" max="999" step="1" required>
                  <input type="number" class="form-control form-control-sm" :name="`extras[${index}][unit_price]`" x-model.number="item.price" min="0" step="0.01" placeholder="0.00" required>
                  <span class="co-row-total" x-text="money(lineTotal(item))"></span>
                  <button type="button" class="co-remove" @click="removeItem(index)" aria-label="Remove item"><i class="bi bi-x-lg"></i></button>
                </div>
              </template>
            </div>

            <button type="button" class="btn btn-sm btn-light mt-2" @click="addItem('')"><i class="bi bi-plus-circle"></i> Add custom item</button>
          </div>

          <div class="co-summary">
            <div class="co-sum-row"><span>Room charges</span><span x-text="money(baseAmount)"></span></div>
            <div class="co-sum-row" x-show="extrasTotal > 0"><span>Extra charges</span><span x-text="money(extrasTotal)"></span></div>
            <div class="co-sum-row co-sum-sub"><span>Subtotal</span><strong x-text="money(subtotal)"></strong></div>
            <div class="co-sum-row"><span>Less: advance paid</span><span x-text="'− ' + money(advance)"></span></div>
            <div class="co-sum-row co-sum-due" :class="{ 'is-settled': balance <= 0 }">
              <span>Balance due</span>
              <strong x-text="money(Math.max(balance, 0))"></strong>
            </div>
            <div class="co-sum-note" x-show="balance < 0">The advance already covers this bill by <span x-text="money(-balance)"></span>. No balance is collected.</div>
          </div>

          <div class="co-section" x-show="balance > 0">
            <div class="co-section-title">Payment method</div>
            <div class="co-methods">
              @foreach (['cash' => ['Cash', 'bi-cash-coin'], 'card' => ['Card', 'bi-credit-card'], 'bank_transfer' => ['Bank Transfer', 'bi-bank']] as $value => [$label, $icon])
                <label class="co-method">
                  <input type="radio" name="payment_method" value="{{ $value }}" x-model="method" :required="balance > 0">
                  <span><i class="bi {{ $icon }}"></i> {{ $label }}</span>
                </label>
              @endforeach
            </div>
          </div>

          <p class="text-muted small mb-0">
            Confirming marks this booking as checked out, saves the extra charges to the bill, and sends the final invoice to the guest on WhatsApp (when automatic sending is enabled).
          </p>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="submitting || hasIncompleteItem">
            <i class="bi bi-check2-circle"></i>
            <span x-text="submitting ? 'Processing…' : (balance > 0 ? 'Collect ' + money(balance) + ' & Checkout' : 'Complete Checkout')"></span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@once
  @push('scripts')
  <script>
    function checkoutModal(config) {
        let nextKey = 0;
        return {
            baseAmount: config.baseAmount,
            advance: config.advance,
            currency: config.currency,
            presets: ['Meals', 'BBQ', 'Safari Jeep', 'Beverages', 'Laundry'],
            items: [],
            method: 'cash',
            submitting: false,
            addItem(name) {
                this.items.push({ key: ++nextKey, name: name, quantity: 1, price: '' });
                this.$nextTick(() => {
                    // A preset only needs its price; a custom item needs a name first.
                    const selector = name ? '.co-row input[name$="[unit_price]"]' : '.co-row input[name$="[item_name]"]';
                    const fields = this.$el.querySelectorAll(selector);
                    if (fields.length) fields[fields.length - 1].focus();
                });
            },
            removeItem(index) {
                this.items.splice(index, 1);
            },
            lineTotal(item) {
                return (Number(item.quantity) || 0) * (Number(item.price) || 0);
            },
            get extrasTotal() {
                return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
            },
            get subtotal() {
                return this.baseAmount + this.extrasTotal;
            },
            get balance() {
                return Math.round((this.subtotal - this.advance) * 100) / 100;
            },
            get hasIncompleteItem() {
                return this.items.some((item) => !String(item.name).trim() || item.price === '' || item.price < 0 || !(Number(item.quantity) >= 1));
            },
            money(value) {
                return this.currency + ' ' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
        };
    }
  </script>
  @endpush
@endonce
