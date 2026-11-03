@extends('layouts.app')

@section('title', 'Expenses | ' . ($globalSettings->villa_name ?? config('app.name')))

@section('content')
@php
    $currency = $globalSettings->currency;
    $money = fn ($amount) => $currency.' '.number_format((float) $amount, 2);
    $filtersActive = $filters['q'] !== '' || $filters['type'] !== 'all' || $filters['category'] !== '' || $filters['from'] !== '' || $filters['to'] !== '';

    // Re-open the modal with the user's input (and the linked booking's label) after a validation error.
    $oldBooking = old('booking_id') ? \App\Models\Booking::with('room')->find(old('booking_id')) : null;
    $oldBookingOption = $oldBooking ? \App\Http\Controllers\ExpenseController::bookingOption($oldBooking) : null;
@endphp
<div x-data="expenseManager(@js([
    'baseUrl' => url('/expenses'),
    'searchUrl' => route('expenses.booking-search'),
    'today' => today()->toDateString(),
    'hasErrors' => $errors->any(),
    'old' => [
        'id' => old('expense_id') ? (int) old('expense_id') : null,
        'category' => old('category', ''),
        'amount' => old('amount', ''),
        'description' => old('description', ''),
        'expense_date' => old('expense_date', ''),
        'booking' => $oldBookingOption,
    ],
]))">
  <div class="page-title">
    <nav class="page-breadcrumb" aria-label="breadcrumb">
      <ol>
        <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
        <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
        <li aria-current="page"><h1>Expenses</h1></li>
      </ol>
    </nav>
    <div class="page-actions"><button type="button" class="btn btn-primary" @click="openCreate()"><i class="bi bi-plus-lg"></i> Add Expense</button></div>
  </div>

  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger"><strong>The expense could not be saved.</strong> {{ $errors->first() }}</div>
  @endif

  {{-- Summary of the (filtered) list --}}
  <div class="ex-kpis">
    <div class="ex-kpi">
      <span class="ex-kpi-icon ex-kpi-blue"><i class="bi bi-receipt-cutoff"></i></span>
      <div><span>{{ $filtersActive ? 'Filtered total' : 'Total expenses' }}</span><strong>{{ $money($summary['total']) }}</strong><small>{{ $summary['count'] }} {{ \Illuminate\Support\Str::plural('record', $summary['count']) }}</small></div>
    </div>
    <div class="ex-kpi">
      <span class="ex-kpi-icon ex-kpi-amber"><i class="bi bi-building"></i></span>
      <div><span>General operating</span><strong>{{ $money($summary['general']) }}</strong><small>Rent, utilities, salaries…</small></div>
    </div>
    <div class="ex-kpi">
      <span class="ex-kpi-icon ex-kpi-green"><i class="bi bi-person-lines-fill"></i></span>
      <div><span>Booking direct costs</span><strong>{{ $money($summary['booking']) }}</strong><small>Reduce each booking's net profit</small></div>
    </div>
  </div>

  <div class="panel ex-panel">
    <form method="GET" action="{{ route('expenses.index') }}" class="ex-filters">
      <div class="ex-search">
        <i class="bi bi-search"></i>
        <input type="search" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Search category, description or guest…" aria-label="Search expenses">
      </div>
      <div class="ex-seg" role="group" aria-label="Expense type">
        @foreach (['all' => 'All', 'general' => 'General', 'booking' => 'Booking-linked'] as $value => $label)
          <label><input type="radio" name="type" value="{{ $value }}" @checked($filters['type'] === $value) onchange="this.form.submit()"><span>{{ $label }}</span></label>
        @endforeach
      </div>
      <select name="category" class="form-select ex-select" aria-label="Category" onchange="this.form.submit()">
        <option value="">All categories</option>
        @foreach ($categories as $category)
          <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ $category }}</option>
        @endforeach
      </select>
      <input type="date" name="from" value="{{ $filters['from'] }}" class="form-control ex-date" aria-label="From date" title="From">
      <input type="date" name="to" value="{{ $filters['to'] }}" class="form-control ex-date" aria-label="To date" title="To">
      <button type="submit" class="btn btn-light"><i class="bi bi-funnel"></i> Apply</button>
      @if ($filtersActive)
        <a href="{{ route('expenses.index') }}" class="btn btn-link ex-reset">Reset</a>
      @endif
    </form>

    <div class="table-responsive">
      <table class="table align-middle dash-table ex-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Category</th>
            <th>Description</th>
            <th>Linked to</th>
            <th class="text-end">Amount</th>
            <th><span class="visually-hidden">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($expenses as $expense)
            @php
                $booking = $expense->booking;
                $payload = [
                    'id' => $expense->id,
                    'category' => $expense->category,
                    'amount' => (float) $expense->amount,
                    'description' => $expense->description ?? '',
                    'expense_date' => $expense->expense_date->toDateString(),
                    'booking' => $booking ? \App\Http\Controllers\ExpenseController::bookingOption($booking) : null,
                ];
            @endphp
            <tr>
              <td class="ex-nowrap">{{ $expense->expense_date->format('d M Y') }}</td>
              <td><span class="ex-cat">{{ $expense->category }}</span></td>
              <td class="ex-desc">{{ $expense->description ?: '—' }}</td>
              <td>
                @if ($booking)
                  @can('manage_bookings')
                    <a class="ex-badge ex-badge-booking" href="{{ route('bookings.show', $booking) }}" title="Open booking"><i class="bi bi-link-45deg"></i> #{{ $booking->id }} · {{ $booking->customer_name }}</a>
                  @else
                    <span class="ex-badge ex-badge-booking"><i class="bi bi-link-45deg"></i> #{{ $booking->id }} · {{ $booking->customer_name }}</span>
                  @endcan
                @else
                  <span class="ex-badge ex-badge-general">General</span>
                @endif
              </td>
              <td class="text-end ex-amount">{{ number_format($expense->amount, 2) }}</td>
              <td>
                <div class="d-flex gap-2 justify-content-end">
                  <button type="button" class="btn btn-sm btn-light" aria-label="Edit expense" @click="openEdit(@js($payload))"><i class="bi bi-pencil"></i></button>
                  <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this expense?');">
                    @csrf
                    @method('DELETE')
                    @foreach ($filters as $key => $value)@if ($value !== '' && $value !== 'all')<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
                    <button class="btn btn-sm btn-light text-danger" type="submit" aria-label="Delete expense"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-5">{{ $filtersActive ? 'No expenses match these filters.' : 'No expenses recorded yet.' }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($expenses->hasPages())
      <div class="mt-3">{{ $expenses->links() }}</div>
    @endif
  </div>

  {{-- ============ Add / edit expense ============ --}}
  <div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down ex-dialog">
      <div class="modal-content dash-modal">
        {{-- data-live-form: main.js's "static demo" shim cancels the submit of any form without a real action attribute at load time; this form's action is set by Alpine, so it must opt out. --}}
        <form method="POST" :action="formAction" @submit="submit($event)" class="ex-form" data-live-form>
          @csrf
          <template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
          <input type="hidden" name="expense_id" :value="form.id">
          @foreach ($filters as $key => $value)@if ($value !== '' && $value !== 'all')<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
          <input type="hidden" name="expense_type" :value="linked ? 'booking' : 'general'">
          <input type="hidden" name="booking_id" :value="linked && form.booking ? form.booking.id : ''">

          <div class="modal-header">
            <div><span class="eyebrow">Expense</span><h2 class="modal-title" id="expenseModalTitle" x-text="form.id ? 'Edit Expense' : 'Add Expense'"></h2></div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div class="ex-type" role="group" aria-label="Expense type">
              <button type="button" :class="{ active: !linked }" @click="setLinked(false)"><i class="bi bi-building"></i><span>General expense<small>Villa operating cost</small></span></button>
              <button type="button" :class="{ active: linked }" @click="setLinked(true)"><i class="bi bi-person-lines-fill"></i><span>Booking direct cost<small>Linked to a guest's booking</small></span></button>
            </div>

            <div class="row g-3 mt-0">
              {{-- Searchable booking picker --}}
              <div class="col-12" x-show="linked" x-cloak>
                <label class="form-label" for="exBookingSearch">Booking <span class="text-danger">*</span></label>
                <div class="ex-picker" @click.outside="pickerOpen = false" @keydown.escape.stop="pickerOpen = false">
                  <template x-if="form.booking">
                    <div class="ex-picked">
                      <div><strong x-text="form.booking.label"></strong><small x-text="form.booking.sub"></small></div>
                      <button type="button" class="ex-picked-clear" @click="clearBooking()" aria-label="Choose a different booking"><i class="bi bi-x-lg"></i></button>
                    </div>
                  </template>
                  <template x-if="!form.booking">
                    <div>
                      <div class="ex-picker-input">
                        <i class="bi bi-search"></i>
                        <input id="exBookingSearch" type="text" class="form-control" x-model="pickerQuery" @input.debounce.250ms="search()" @focus="openPicker()" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="choose(results[active])" placeholder="Search guest, phone, room or booking #" autocomplete="off" role="combobox" aria-expanded="false" :aria-expanded="pickerOpen">
                      </div>
                      <ul class="ex-picker-list" x-show="pickerOpen" x-cloak role="listbox">
                        <li class="ex-picker-note" x-show="loading">Searching…</li>
                        <li class="ex-picker-note" x-show="!loading && results.length === 0">No bookings found.</li>
                        <template x-for="(option, i) in results" :key="option.id">
                          <li role="option" :class="{ active: i === active }" @mousedown.prevent="choose(option)" @mouseenter="active = i">
                            <strong x-text="option.label"></strong><small x-text="option.sub"></small>
                          </li>
                        </template>
                      </ul>
                    </div>
                  </template>
                </div>
                <div class="invalid-feedback d-block" x-show="errors.booking" x-text="errors.booking"></div>
                <div class="form-text">The cost is deducted from this booking's net profit.</div>
              </div>

              <div class="col-12">
                <label class="form-label" for="exCategory">Category <span class="text-danger">*</span></label>
                <input id="exCategory" class="form-control" list="expenseCategoryOptions" :class="errors.category ? 'is-invalid' : ''" name="category" x-model="form.category" @input="clearError('category')" placeholder="e.g. Utilities" maxlength="255">
                <datalist id="expenseCategoryOptions">
                  <template x-for="cat in categoryOptions" :key="cat"><option :value="cat"></option></template>
                </datalist>
                <div class="ex-chips">
                  <template x-for="cat in categoryOptions" :key="cat">
                    <button type="button" class="ex-chip" :class="{ on: form.category === cat }" @click="form.category = cat; clearError('category')" x-text="cat"></button>
                  </template>
                </div>
                <div class="invalid-feedback d-block" x-show="errors.category" x-text="errors.category"></div>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="exAmount">Amount ({{ $currency }}) <span class="text-danger">*</span></label>
                <input id="exAmount" class="form-control" :class="errors.amount ? 'is-invalid' : ''" type="number" step="0.01" min="0.01" inputmode="decimal" name="amount" x-model="form.amount" @input="clearError('amount')" placeholder="0.00">
                <div class="invalid-feedback d-block" x-show="errors.amount" x-text="errors.amount"></div>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="exDate">Date <span class="text-danger">*</span></label>
                <input id="exDate" class="form-control" :class="errors.expense_date ? 'is-invalid' : ''" type="date" name="expense_date" x-model="form.expense_date" @input="clearError('expense_date')">
                <div class="invalid-feedback d-block" x-show="errors.expense_date" x-text="errors.expense_date"></div>
              </div>
              <div class="col-12">
                <label class="form-label" for="exDescription">Description <span class="text-muted">(optional)</span></label>
                <textarea id="exDescription" class="form-control" name="description" rows="3" maxlength="1000" x-model="form.description" placeholder="What was this for?"></textarea>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> <span x-text="form.id ? 'Save Changes' : 'Add Expense'"></span></button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    function expenseManager(config) {
        const GENERAL = @js($generalCategories);
        const BOOKING = @js($bookingCategories);
        const blank = () => ({ id: null, category: '', amount: '', description: '', expense_date: config.today, booking: null });
        return {
            form: blank(),
            linked: false,
            errors: {},
            baseUrl: config.baseUrl,
            // booking picker
            pickerQuery: '',
            pickerOpen: false,
            results: [],
            active: 0,
            loading: false,
            requestId: 0,
            init() {
                if (config.hasErrors) {
                    this.form = { ...blank(), ...config.old, expense_date: config.old.expense_date || config.today };
                    this.linked = !!config.old.booking;
                    this.showModal();
                }
            },
            get categoryOptions() {
                return this.linked ? BOOKING : GENERAL;
            },
            get formAction() {
                return this.form.id ? (this.baseUrl + '/' + this.form.id) : this.baseUrl;
            },
            openCreate() {
                this.form = blank();
                this.linked = false;
                this.errors = {};
                this.pickerQuery = '';
                this.showModal();
            },
            openEdit(expense) {
                this.form = { ...blank(), ...expense };
                this.linked = !!expense.booking;
                this.errors = {};
                this.pickerQuery = '';
                this.showModal();
            },
            showModal() {
                this.$nextTick(() => bootstrap.Modal.getOrCreateInstance(document.getElementById('expenseModal')).show());
            },
            setLinked(value) {
                this.linked = value;
                this.clearError('booking');
                if (value && !this.form.booking) this.$nextTick(() => document.getElementById('exBookingSearch')?.focus());
            },
            clearError(key) {
                if (this.errors[key]) {
                    const { [key]: _removed, ...rest } = this.errors;
                    this.errors = rest;
                }
            },
            // ---- booking typeahead ----
            openPicker() {
                this.pickerOpen = true;
                if (this.results.length === 0) this.search();
            },
            async search() {
                this.pickerOpen = true;
                const id = ++this.requestId;
                this.loading = true;
                try {
                    const response = await fetch(config.searchUrl + '?q=' + encodeURIComponent(this.pickerQuery.trim()), { headers: { 'Accept': 'application/json' } });
                    const data = await response.json();
                    if (id === this.requestId) { this.results = data; this.active = 0; }
                } catch (e) {
                    if (id === this.requestId) this.results = [];
                } finally {
                    if (id === this.requestId) this.loading = false;
                }
            },
            move(step) {
                if (!this.results.length) return;
                this.active = (this.active + step + this.results.length) % this.results.length;
            },
            choose(option) {
                if (!option) return;
                this.form.booking = option;
                this.pickerOpen = false;
                this.pickerQuery = '';
                this.clearError('booking');
            },
            clearBooking() {
                this.form.booking = null;
                this.pickerQuery = '';
                this.$nextTick(() => { document.getElementById('exBookingSearch')?.focus(); this.openPicker(); });
            },
            validate() {
                this.errors = {};
                if (this.linked && !this.form.booking) this.errors.booking = 'Choose the booking this cost belongs to, or switch to a general expense.';
                if (!this.form.category || !String(this.form.category).trim()) this.errors.category = 'Category is required.';
                if (this.form.amount === '' || this.form.amount === null || Number(this.form.amount) <= 0) this.errors.amount = 'Enter an amount greater than 0.';
                if (!this.form.expense_date) this.errors.expense_date = 'Date is required.';
                return Object.keys(this.errors).length === 0;
            },
            submit(event) {
                if (!this.validate()) event.preventDefault();
            },
        };
    }
</script>
@endpush
