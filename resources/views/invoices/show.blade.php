@extends('layouts.app')

@section('title', 'Invoice Details | '.$globalSettings->villa_name.' Admin Dashboard')

@section('content')
        <div class="page-title">
          <nav class="page-breadcrumb" aria-label="breadcrumb">
            <ol>
              <li><a class="page-breadcrumb-home" href="index.html"><i class="bi bi-house-door"></i><span>Home</span></a></li>
              <li class="page-breadcrumb-separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></li>
              <li aria-current="page"><h1>Invoice Details</h1></li>
            </ol>
          </nav>
          <div class="page-actions"><button class="btn btn-light" data-export-page><i class="bi bi-download"></i> Export</button><button class="btn btn-light" data-print-page><i class="bi bi-printer"></i> Print</button><button class="btn btn-primary" data-create-open data-create-type="invoice-details" data-create-label="Send Invoice"><i class="bi bi-send"></i> Send Invoice</button></div>
        </div>
        <div class="invoice-layout">
          <article class="invoice-document panel" data-print-area>
            <div class="invoice-top">
              <div class="invoice-brand">
                <span class="brand-mark">{{ strtoupper(substr($globalSettings->villa_name, 0, 1)) }}</span>
                <div><strong>{{ $globalSettings->villa_name }}</strong><small>Admin Suite</small></div>
              </div>
              <div class="invoice-title">
                <span class="deal-badge won">Paid</span>
                <h2>Invoice</h2>
                <p>#DS-1024</p>
              </div>
            </div>

            <div class="invoice-hero">
              <div>
                <span class="eyebrow">Amount Due</span>
                <strong>$3,420.00</strong>
                <p>Paid on May 24, 2026 via Visa ending 2048</p>
              </div>
              <div class="invoice-meta-card">
                <span>Issued</span><strong>May 22, 2026</strong>
                <span>Due Date</span><strong>May 30, 2026</strong>
                <span>Reference</span><strong>PO-72891</strong>
              </div>
            </div>

            <div class="invoice-party-grid">
              <section>
                <h3>Bill From</h3>
                <strong>{{ $globalSettings->villa_name }}</strong>
                <p>
                  @if ($globalSettings->address){{ $globalSettings->address }}<br>@endif
                  @if ($globalSettings->email){{ $globalSettings->email }}@endif
                </p>
              </section>
              <section>
                <h3>Bill To</h3>
                <strong>Nexa Analytics</strong>
                <p>22 Market Street<br>San Francisco, CA 94105<br>accounts@nexaanalytics.com</p>
              </section>
            </div>

            <div class="invoice-items">
              <table>
                <thead><tr><th>Service</th><th>Qty</th><th>Rate</th><th>Total</th></tr></thead>
                <tbody>
                  <tr><td><strong>Dashora Enterprise License</strong><span>Annual dashboard template license and updates</span></td><td>1</td><td>$1,800.00</td><td>$1,800.00</td></tr>
                  <tr><td><strong>Implementation Support</strong><span>Design system setup, layout review, and QA support</span></td><td>12h</td><td>$95.00</td><td>$1,140.00</td></tr>
                  <tr><td><strong>Custom Reporting Pack</strong><span>Invoice, customer, and analytics reporting views</span></td><td>1</td><td>$480.00</td><td>$480.00</td></tr>
                </tbody>
              </table>
            </div>

            <div class="invoice-bottom">
              <div class="invoice-note">
                <h3>Notes</h3>
                <p>Thank you for your business. This invoice is paid and ready for accounting records.</p>
              </div>
              <div class="invoice-totals">
                <div><span>Subtotal</span><strong>$3,420.00</strong></div>
                <div><span>Tax</span><strong>$0.00</strong></div>
                <div><span>Discount</span><strong>$0.00</strong></div>
                <div class="grand-total"><span>Total Paid</span><strong>$3,420.00</strong></div>
              </div>
            </div>
          </article>

          <aside class="invoice-side panel">
            <div class="invoice-side-block">
              <span class="work-icon"><i class="bi bi-receipt"></i></span>
              <h2>Payment Summary</h2>
              <p>Invoice has been settled and is ready to download, print, or share with the customer.</p>
            </div>
            <div class="invoice-status-list">
              <div><span>Payment status</span><strong class="text-success">Paid</strong></div>
              <div><span>Customer</span><strong>Nexa Analytics</strong></div>
              <div><span>Currency</span><strong>USD</strong></div>
              <div><span>Next action</span><strong>Archive</strong></div>
            </div>
            <div class="invoice-actions">
              <button class="btn btn-primary w-100" data-print-page><i class="bi bi-printer"></i> Print Invoice</button>
              <button class="btn btn-light w-100" data-export-page><i class="bi bi-download"></i> Download PDF</button>
            </div>
          </aside>
        </div>
@endsection
