@extends('layouts.app')

@section('title', 'Expenses | '.$globalSettings->villa_name.' Admin Dashboard')

@section('content')
<div x-data="expenseManager()">
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

        <div class="row g-4">
          <div class="col-12">
            <div class="panel">
              <div class="panel-head"><div><h2>Expenses</h2><p>Operating costs and outgoing payments</p></div></div>
              <div class="table-responsive">
                <table class="table align-middle dash-table">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Category</th>
                      <th>Description</th>
                      <th class="text-end">Amount</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($expenses as $expense)
                      <tr>
                        <td>{{ $expense->expense_date->format('d M Y') }}</td>
                        <td>{{ $expense->category }}</td>
                        <td>{{ $expense->description ?: '—' }}</td>
                        <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                        <td>
                          <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-light" aria-label="Edit expense"
                                    @click="openEdit({{ $expense->id }}, @js($expense->category), {{ (float) $expense->amount }}, @js($expense->description ?? ''), @js($expense->expense_date->toDateString()))">
                              <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this expense?');">
                              @csrf
                              @method('DELETE')
                              <button class="btn btn-sm btn-light text-danger" type="submit" aria-label="Delete expense"><i class="bi bi-trash"></i></button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    @empty
                      <tr><td colspan="5" class="text-center text-muted py-4">No expenses recorded yet.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
              @if ($expenses->hasPages())
                <div class="mt-3">{{ $expenses->links() }}</div>
              @endif
            </div>
          </div>
        </div>

        <div class="modal fade" id="expenseModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content dash-modal">
              <form method="POST" :action="formAction" @submit="submit($event)" data-live-form>
                @csrf
                <template x-if="form.id">
                  <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="expense_id" x-bind:value="form.id">
                <div class="modal-header">
                  <div><span class="eyebrow">Expense</span><h2 class="modal-title" x-text="form.id ? 'Edit Expense' : 'Add Expense'"></h2></div>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="row g-3">
                    <div class="col-12">
                      <label class="form-label">Category</label>
                      <input class="form-control" :class="errors.category ? 'is-invalid' : ''" name="category" x-model="form.category" @input="validate()">
                      <div class="invalid-feedback d-block" x-show="errors.category" x-text="errors.category"></div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Amount</label>
                      <input class="form-control" :class="errors.amount ? 'is-invalid' : ''" type="number" step="0.01" min="0" name="amount" x-model="form.amount" @input="validate()">
                      <div class="invalid-feedback d-block" x-show="errors.amount" x-text="errors.amount"></div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Date</label>
                      <input class="form-control" :class="errors.expense_date ? 'is-invalid' : ''" type="date" name="expense_date" x-model="form.expense_date" @input="validate()">
                      <div class="invalid-feedback d-block" x-show="errors.expense_date" x-text="errors.expense_date"></div>
                    </div>
                    <div class="col-12">
                      <label class="form-label">Description</label>
                      <textarea class="form-control" name="description" rows="3" x-model="form.description"></textarea>
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
    function expenseManager() {
        return {
            form: { id: null, category: '', amount: '', description: '', expense_date: '' },
            errors: {},
            baseUrl: '{{ url('/expenses') }}',
            init() {
                @if ($errors->any())
                    this.form = {
                        id: {{ old('expense_id') ? (int) old('expense_id') : 'null' }},
                        category: @json(old('category', '')),
                        amount: @json(old('amount', '')),
                        description: @json(old('description', '')),
                        expense_date: @json(old('expense_date', '')),
                    };
                    this.showModal();
                @endif
            },
            openCreate() {
                this.form = { id: null, category: '', amount: '', description: '', expense_date: '' };
                this.errors = {};
                this.showModal();
            },
            openEdit(id, category, amount, description, expenseDate) {
                this.form = { id: id, category: category, amount: amount, description: description, expense_date: expenseDate };
                this.errors = {};
                this.showModal();
            },
            showModal() {
                this.$nextTick(() => bootstrap.Modal.getOrCreateInstance(document.getElementById('expenseModal')).show());
            },
            get formAction() {
                return this.form.id ? (this.baseUrl + '/' + this.form.id) : this.baseUrl;
            },
            validate() {
                this.errors = {};
                if (!this.form.category || !String(this.form.category).trim()) {
                    this.errors.category = 'Category is required.';
                }
                if (this.form.amount === '' || this.form.amount === null || Number(this.form.amount) <= 0) {
                    this.errors.amount = 'Enter a valid amount greater than 0.';
                }
                if (!this.form.expense_date) {
                    this.errors.expense_date = 'Date is required.';
                }
                return Object.keys(this.errors).length === 0;
            },
            submit(event) {
                if (!this.validate()) {
                    event.preventDefault();
                }
            }
        };
    }
</script>
@endpush
