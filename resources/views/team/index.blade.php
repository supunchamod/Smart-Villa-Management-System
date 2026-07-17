@extends('layouts.app')

@section('title', 'Team | Dashora Admin Dashboard')

@section('content')
<div x-data="teamManager()">
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Team</h1></li>
            </ol>
          </nav>
          <div class="page-actions"><button type="button" class="btn btn-primary" @click="openCreate()"><i class="bi bi-plus-lg"></i> Add Manager</button></div>
        </div>

        @if (session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="row g-4">
          <div class="col-12">
            <div class="panel">
              <div class="panel-head"><div><h2>Staff &amp; Permissions</h2><p>Owners have full access. Managers only see what you grant them.</p></div></div>
              <div class="table-responsive">
                <table class="table align-middle dash-table">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Email</th>
                      <th>Role</th>
                      <th>Permissions</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($members as $member)
                      <tr>
                        <td><strong>{{ $member->name }}</strong></td>
                        <td>{{ $member->email }}</td>
                        <td><span class="deal-badge {{ $member->role === 'owner' ? 'won' : 'new' }}">{{ ucfirst($member->role) }}</span></td>
                        <td>
                          @if ($member->role === 'owner')
                            <span class="text-muted">All permissions</span>
                          @elseif (empty($member->permissions))
                            <span class="text-muted">None granted</span>
                          @else
                            <div class="d-flex flex-wrap gap-1">
                              @foreach ($member->permissions as $permission)
                                <span class="deal-badge new">{{ \App\Models\User::PERMISSIONS[$permission] ?? $permission }}</span>
                              @endforeach
                            </div>
                          @endif
                        </td>
                        <td>
                          @if ($member->role === 'manager')
                            <div class="d-flex gap-2">
                              <button type="button" class="btn btn-sm btn-light" aria-label="Edit manager"
                                      @click="openEdit({{ $member->id }}, @js($member->name), @js($member->email), @json($member->permissions ?? []))">
                                <i class="bi bi-pencil"></i>
                              </button>
                              <form method="POST" action="{{ route('team.destroy', $member) }}" onsubmit="return confirm('Remove this manager?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-light text-danger" type="submit" aria-label="Remove manager"><i class="bi bi-trash"></i></button>
                              </form>
                            </div>
                          @else
                            <span class="text-muted">—</span>
                          @endif
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="modal fade" id="teamModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content dash-modal">
              <form method="POST" :action="formAction" @submit="submit($event)" data-live-form>
                @csrf
                <template x-if="form.id">
                  <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="member_id" x-bind:value="form.id">
                <div class="modal-header">
                  <div><span class="eyebrow">Staff</span><h2 class="modal-title" x-text="form.id ? 'Edit Manager' : 'Add Manager'"></h2></div>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Full name</label>
                      <input class="form-control" :class="errors.name ? 'is-invalid' : ''" name="name" x-model="form.name" @input="validate()">
                      <div class="invalid-feedback d-block" x-show="errors.name" x-text="errors.name"></div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Email address</label>
                      <input class="form-control" :class="errors.email ? 'is-invalid' : ''" type="email" name="email" x-model="form.email" @input="validate()">
                      <div class="invalid-feedback d-block" x-show="errors.email" x-text="errors.email"></div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Password <small class="text-muted" x-show="form.id">(leave blank to keep current)</small></label>
                      <input class="form-control" :class="errors.password ? 'is-invalid' : ''" type="password" name="password" x-model="form.password" @input="validate()">
                      <div class="invalid-feedback d-block" x-show="errors.password" x-text="errors.password"></div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Confirm password</label>
                      <input class="form-control" type="password" name="password_confirmation" x-model="form.password_confirmation" @input="validate()">
                    </div>
                    <div class="col-12">
                      <label class="form-label">Permissions</label>
                      <div class="row g-2">
                        @foreach (\App\Models\User::PERMISSIONS as $key => $label)
                          <div class="col-md-6">
                            <label class="d-flex align-items-center gap-2">
                              <input type="checkbox" name="permissions[]" value="{{ $key }}" x-model="form.permissions">
                              <span>{{ $label }}</span>
                            </label>
                          </div>
                        @endforeach
                      </div>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> <span x-text="form.id ? 'Save Changes' : 'Add Manager'"></span></button>
                </div>
              </form>
            </div>
          </div>
        </div>
</div>
@endsection

@push('scripts')
<script>
    function teamManager() {
        return {
            form: { id: null, name: '', email: '', password: '', password_confirmation: '', permissions: [] },
            errors: {},
            baseUrl: '{{ url('/team') }}',
            init() {
                @if ($errors->any())
                    this.form = {
                        id: {{ old('member_id') ? (int) old('member_id') : 'null' }},
                        name: @json(old('name', '')),
                        email: @json(old('email', '')),
                        password: '',
                        password_confirmation: '',
                        permissions: @json(old('permissions', [])),
                    };
                    this.showModal();
                @endif
            },
            openCreate() {
                this.form = { id: null, name: '', email: '', password: '', password_confirmation: '', permissions: [] };
                this.errors = {};
                this.showModal();
            },
            openEdit(id, name, email, permissions) {
                this.form = { id: id, name: name, email: email, password: '', password_confirmation: '', permissions: permissions };
                this.errors = {};
                this.showModal();
            },
            showModal() {
                this.$nextTick(() => bootstrap.Modal.getOrCreateInstance(document.getElementById('teamModal')).show());
            },
            get formAction() {
                return this.form.id ? (this.baseUrl + '/' + this.form.id) : this.baseUrl;
            },
            validate() {
                this.errors = {};
                if (!this.form.name || !String(this.form.name).trim()) {
                    this.errors.name = 'Name is required.';
                }
                if (!this.form.email || !String(this.form.email).trim()) {
                    this.errors.email = 'Email is required.';
                }
                if (!this.form.id && !this.form.password) {
                    this.errors.password = 'Password is required for a new manager.';
                } else if (this.form.password && this.form.password !== this.form.password_confirmation) {
                    this.errors.password = 'Passwords do not match.';
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
