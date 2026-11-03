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
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-sm-down co-dialog">
    <div class="modal-content dash-modal">
      <form
        method="POST"
        action="{{ route('bookings.checkout', $booking) }}"
        x-data="checkoutModal(@js([
            'currency' => $currency,
            'baseAmount' => (float) $booking->total_amount,
            // Extra charges already on the booking (e.g. safari lunch / drone) - part of the bill, not editable here.
            'existing' => $booking->extras->map(fn ($extra) => ['name' => $extra->item_name, 'detail' => $extra->quantity > 1 ? $extra->quantity.' × '.number_format((float) $extra->unit_price, 2) : null, 'amount' => (float) $extra->line_total])->values()->all(),
            'advance' => (float) $booking->advance_payment,
            'saved' => $booking->expenses->map(fn ($expense) => $expense->toListItem())->values()->all(),
            'today' => today()->toDateString(),
            'categories' => \App\Models\Expense::BOOKING_CATEGORIES,
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
              <div class="co-line-main">
                <strong>{{ $booking->room ? $booking->room->name_or_number : $booking->safari_tour_name }}</strong>
                <small>{{ $baseLabel }}</small>
              </div>
              <strong class="co-amount" x-text="money(baseAmount)"></strong>
            </div>
          </div>

          <div class="co-section">
            <div class="co-section-title">
              <span>Extra charges</span>
              <small>Anything consumed during the stay</small>
            </div>

            <div class="co-chips">
              <template x-for="preset in presets" :key="preset">
                <button type="button" class="co-chip" @click="addItem(preset)"><i class="bi bi-plus-lg"></i><span x-text="preset"></span></button>
              </template>
              <button type="button" class="co-chip co-chip-add" @click="addItem('')"><i class="bi bi-plus-circle"></i><span>Add custom item</span></button>
            </div>

            <div class="co-empty" x-show="items.length === 0">No extra charges added. Use a quick-add button above, or add a custom item.</div>

            <div class="co-rows" x-show="items.length > 0">
              <div class="co-row co-row-head" aria-hidden="true">
                <span>Item</span><span>Qty</span><span>Unit price</span><span class="text-end">Amount</span><span></span>
              </div>
              <template x-for="(item, index) in items" :key="item.key">
                <div class="co-row">
                  <input type="text" class="form-control co-name" :name="`extras[${index}][item_name]`" x-model="item.name" placeholder="Item name (e.g. Dinner)" aria-label="Item name" maxlength="120" required>
                  <label class="co-field co-qty" data-label="Qty">
                    <input type="number" class="form-control" :name="`extras[${index}][quantity]`" x-model.number="item.quantity" min="1" max="999" step="1" inputmode="numeric" aria-label="Quantity" required>
                  </label>
                  <label class="co-field co-price" data-label="Unit price">
                    <input type="number" class="form-control" :name="`extras[${index}][unit_price]`" x-model.number="item.price" min="0" step="0.01" inputmode="decimal" placeholder="0.00" aria-label="Unit price" required>
                  </label>
                  <span class="co-row-total" x-text="money(lineTotal(item))"></span>
                  <button type="button" class="co-remove" @click="removeItem(index)" aria-label="Remove item"><i class="bi bi-trash3"></i></button>
                </div>
              </template>
            </div>
          </div>

          {{-- Direct costs the villa pays for this stay. Internal: never added to the guest's bill or balance. --}}
          <div class="co-section">
            <div class="co-section-title">
              <span>Booking expenses / direct costs</span>
              <small>Internal – not billed to the guest</small>
            </div>

            <ul class="co-saved" x-show="saved.length > 0">
              <template x-for="cost in saved" :key="cost.id">
                <li class="co-saved-row">
                  <div class="co-saved-main"><strong x-text="cost.category"></strong><small x-show="cost.description" x-text="cost.description"></small><small class="co-saved-date" x-text="cost.date"></small></div>
                  <span class="co-num" x-text="money(cost.amount)"></span>
                  <button type="button" class="co-remove" @click="deleteSaved(cost)" :disabled="deleting === cost.id" aria-label="Delete expense"><i class="bi bi-trash3"></i></button>
                </li>
              </template>
            </ul>

            <div class="co-chips">
              <template x-for="cat in categories" :key="cat">
                <button type="button" class="co-chip" @click="addCost(cat)"><i class="bi bi-plus-lg"></i><span x-text="cat"></span></button>
              </template>
            </div>

            <div class="co-rows co-cost-rows" x-show="costs.length > 0">
              <template x-for="(cost, index) in costs" :key="cost.key">
                <div class="co-row co-cost-row">
                  <input type="text" class="form-control co-name" :name="`booking_expenses[${index}][category]`" x-model="cost.category" list="coCostCategories{{ $modalId }}" placeholder="Category (e.g. Transport)" aria-label="Expense category" maxlength="100" required>
                  <input type="text" class="form-control co-desc" :name="`booking_expenses[${index}][description]`" x-model="cost.description" placeholder="Description (optional)" aria-label="Expense description" maxlength="255">
                  <label class="co-field co-price" data-label="Amount">
                    <input type="number" class="form-control" :name="`booking_expenses[${index}][amount]`" x-model.number="cost.amount" min="0.01" step="0.01" inputmode="decimal" placeholder="0.00" aria-label="Expense amount" required>
                  </label>
                  <label class="co-field co-date" data-label="Date">
                    <input type="date" class="form-control" :name="`booking_expenses[${index}][expense_date]`" x-model="cost.expense_date" :max="today" aria-label="Expense date">
                  </label>
                  <button type="button" class="co-remove" @click="removeCost(index)" aria-label="Remove expense"><i class="bi bi-x-lg"></i></button>
                </div>
              </template>
            </div>
            <datalist id="coCostCategories{{ $modalId }}"><template x-for="cat in categories" :key="cat"><option :value="cat"></option></template></datalist>

            <div class="co-empty" x-show="saved.length === 0 && costs.length === 0">No direct costs recorded for this booking.</div>

            <div class="co-margin" x-show="costsTotal > 0 || saved.length > 0">
              <div class="co-sum-row"><span>Total direct costs</span><span class="co-num" x-text="money(costsTotal)"></span></div>
              <div class="co-sum-row co-margin-profit" :class="{ 'is-loss': profit < 0 }"><span>Profit on this booking</span><strong class="co-num" x-text="money(profit)"></strong></div>
            </div>
          </div>

          <div class="co-summary">
            <div class="co-sum-row"><span>Room charges</span><span class="co-num" x-text="money(baseAmount)"></span></div>
            <template x-for="(item, i) in existing" :key="'ex' + i">
              <div class="co-sum-row co-sum-item">
                <span class="co-sum-item-name" x-text="item.name + (item.detail ? ' (' + item.detail + ')' : '')"></span>
                <span class="co-num" x-text="money(item.amount)"></span>
              </div>
            </template>
            <template x-for="item in billedItems" :key="item.key">
              <div class="co-sum-row co-sum-item">
                <span class="co-sum-item-name" x-text="item.name.trim() + (item.quantity > 1 ? ' × ' + item.quantity : '')"></span>
                <span class="co-num" x-text="money(lineTotal(item))"></span>
              </div>
            </template>
            <div class="co-sum-row co-sum-sub"><span>Subtotal</span><strong class="co-num" x-text="money(subtotal)"></strong></div>
            <div class="co-sum-row"><span>Less: advance paid</span><span class="co-num" x-text="'− ' + money(advance)"></span></div>
            <div class="co-sum-due" :class="{ 'is-settled': balance <= 0 }">
              <span>Balance due</span>
              <strong class="co-num" x-text="money(Math.max(balance, 0))"></strong>
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
            existing: config.existing || [],
            advance: config.advance,
            currency: config.currency,
            presets: ['Meals', 'BBQ', 'Safari Jeep', 'Beverages', 'Laundry'],
            items: [],
            saved: config.saved || [],
            categories: config.categories || [],
            today: config.today,
            costs: [],
            deleting: null,
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
            addCost(category) {
                this.costs.push({ key: ++nextKey, category: category || '', description: '', amount: '', expense_date: this.today });
                this.$nextTick(() => {
                    const selector = category ? '.co-cost-row input[name$="[amount]"]' : '.co-cost-row input[name$="[category]"]';
                    const fields = this.$el.querySelectorAll(selector);
                    if (fields.length) fields[fields.length - 1].focus();
                });
            },
            removeCost(index) {
                this.costs.splice(index, 1);
            },
            async deleteSaved(cost) {
                if (this.deleting || !window.confirm('Delete this expense?')) return;
                this.deleting = cost.id;
                try {
                    const response = await fetch(cost.url, {
                        method: 'DELETE',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    });
                    if (!response.ok) throw new Error('Request failed');
                    this.saved = this.saved.filter((c) => c.id !== cost.id);
                } catch (e) {
                    window.alert('Could not delete this expense. Please try again.');
                } finally {
                    this.deleting = null;
                }
            },
            removeItem(index) {
                this.items.splice(index, 1);
            },
            lineTotal(item) {
                return (Number(item.quantity) || 0) * (Number(item.price) || 0);
            },
            // Extras that will appear on the bill: named, with an amount.
            get billedItems() {
                return this.items.filter((item) => String(item.name).trim() && this.lineTotal(item) > 0);
            },
            get extrasTotal() {
                return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
            },
            get existingTotal() {
                return this.existing.reduce((sum, item) => sum + (Number(item.amount) || 0), 0);
            },
            get subtotal() {
                return this.baseAmount + this.existingTotal + this.extrasTotal;
            },
            get balance() {
                return Math.round((this.subtotal - this.advance) * 100) / 100;
            },
            get costsTotal() {
                return this.saved.reduce((sum, c) => sum + (Number(c.amount) || 0), 0)
                    + this.costs.reduce((sum, c) => sum + (Number(c.amount) || 0), 0);
            },
            // What the booking earns for the villa after its own direct costs (internal figure).
            get profit() {
                return Math.round((this.subtotal - this.costsTotal) * 100) / 100;
            },
            get hasIncompleteItem() {
                return this.costs.some((c) => !String(c.category).trim() || !(Number(c.amount) > 0))
                    || this.items.some((item) => !String(item.name).trim() || item.price === '' || item.price < 0 || !(Number(item.quantity) >= 1));
            },
            money(value) {
                return this.currency + ' ' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
        };
    }
  </script>
  @endpush
@endonce
