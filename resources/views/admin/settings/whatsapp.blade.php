@extends('layouts.app')

@section('title', 'WhatsApp | '.$globalSettings->villa_name.' Admin Dashboard')

@section('content')
<div x-data="whatsappSettings(@js($status), @js($qr))" x-init="startPolling()">
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>WhatsApp</h1></li>
            </ol>
          </nav>
        </div>

        @if (session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row g-4">
          <div class="col-xl-8">
            <div class="panel">
              <div class="panel-head">
                <div><h2><i class="bi bi-whatsapp"></i> WhatsApp Connection</h2><p>Link a WhatsApp number so booking notifications and invoices can be sent automatically.</p></div>
                <span class="deal-badge" :class="badgeClass"><span class="dot" :style="'background:' + dotColor"></span><span x-text="statusLabel"></span></span>
              </div>

              <div class="text-center py-4">
                <template x-if="status === 'WORKING'">
                  <div>
                    <i class="bi bi-check-circle-fill text-success" style="font-size:3rem;"></i>
                    <p class="mt-3 mb-4">Your WhatsApp number is connected and ready to send messages.</p>
                    <form method="POST" action="{{ route('admin.whatsapp.disconnect') }}" onsubmit="return confirm('Disconnect WhatsApp? You will need to scan the QR code again to reconnect.');">
                      @csrf
                      <button class="btn btn-danger" type="submit"><i class="bi bi-box-arrow-right"></i> Disconnect WhatsApp</button>
                    </form>
                  </div>
                </template>

                <div id="qr-code-container" x-show="status === 'SCAN_QR_CODE'" style="display: none;">
                  <p class="mb-3">Scan this QR code with WhatsApp on your phone (<strong>Linked Devices &rarr; Link a Device</strong>) to connect.</p>
                  <img id="qr-image" :src="qr" x-show="qr" alt="WhatsApp QR code" style="width:240px;height:240px;border:1px solid #e5e7eb;border-radius:12px;padding:8px;">
                  <div x-show="!qr" class="d-flex flex-column align-items-center gap-2" style="width:240px;height:240px;margin:0 auto;justify-content:center;">
                    <span class="spinner-border text-primary" role="status" aria-hidden="true"></span>
                    <small class="text-muted">Waiting for QR code&hellip;</small>
                  </div>
                  <p class="text-muted small mt-3 mb-0">This page refreshes automatically every few seconds &mdash; no need to reload.</p>
                </div>

                <template x-if="status !== 'WORKING' && status !== 'SCAN_QR_CODE'">
                  <div>
                    <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size:3rem;"></i>
                    <p class="mt-3 mb-0">WhatsApp session is <strong x-text="status"></strong>. Waiting for it to start&hellip;</p>
                  </div>
                </template>
              </div>
            </div>
          </div>

          <div class="col-xl-4">
            <div class="panel">
              <div class="panel-head"><div><h2>Status Reference</h2><p>What each state means</p></div></div>
              <div class="timeline">
                <div class="timeline-item"><span></span><div><strong>Connected</strong><small>The device is linked and messages can be sent.</small></div></div>
                <div class="timeline-item"><span></span><div><strong>QR Scan Required</strong><small>Scan the QR code from WhatsApp to link a device.</small></div></div>
                <div class="timeline-item"><span></span><div><strong>Disconnected / Stopped</strong><small>No device is linked. A new QR code will appear shortly.</small></div></div>
              </div>
            </div>
          </div>
        </div>
</div>
@endsection

@push('scripts')
<script>
    function whatsappSettings(initialStatus, initialQr) {
        return {
            status: initialStatus,
            qr: initialQr,
            pollTimer: null,
            statusUrl: '{{ route('admin.whatsapp.status') }}',
            get statusLabel() {
                return {
                    WORKING: 'Connected',
                    SCAN_QR_CODE: 'QR Scan Required',
                    STARTING: 'Starting…',
                    STOPPED: 'Disconnected',
                    FAILED: 'Connection Failed',
                }[this.status] ?? this.status;
            },
            get badgeClass() {
                if (this.status === 'WORKING') return 'won';
                if (this.status === 'SCAN_QR_CODE' || this.status === 'STARTING') return 'pending';
                return 'stuck';
            },
            get dotColor() {
                if (this.status === 'WORKING') return '#2fa84f';
                if (this.status === 'SCAN_QR_CODE' || this.status === 'STARTING') return '#b45309';
                return '#dc2626';
            },
            startPolling() {
                this.poll();
                this.pollTimer = setInterval(() => this.poll(), 4000);
            },
            async poll() {
                try {
                    const response = await fetch(this.statusUrl, { headers: { Accept: 'application/json' } });
                    if (!response.ok) {
                        return;
                    }
                    const data = await response.json();
                    this.status = data.status;
                    this.qr = data.qr;
                } catch (error) {
                    console.error('Failed to refresh WhatsApp status', error);
                }
            },
        };
    }
</script>
@endpush
