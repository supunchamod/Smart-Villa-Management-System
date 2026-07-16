<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $stage === 'final' ? 'Final Invoice' : 'Invoice' }} — {{ $booking->customer_name }}</title>
<style>
    @page {
        margin: 30px 40px 40px 40px;
    }

    * {
        box-sizing: border-box;
    }

    body {
        font-family: Helvetica, Arial, sans-serif;
        color: #1f2937;
        font-size: 13px;
        line-height: 1.5;
    }

    .watermark {
        position: fixed;
        top: 300px;
        left: 60px;
        font-size: 92px;
        font-weight: bold;
        color: #5278ff;
        opacity: 0.10;
        transform: rotate(-28deg);
    }

    table {
        border-collapse: collapse;
        width: 100%;
    }

    .header-table td {
        vertical-align: top;
        padding: 0;
    }

    .villa-logo {
        height: 54px;
        margin-bottom: 8px;
    }

    .villa-name {
        font-size: 20px;
        font-weight: bold;
        margin: 0 0 4px 0;
        color: #111827;
    }

    .muted {
        color: #6b7280;
        font-size: 12px;
    }

    .invoice-title {
        font-size: 24px;
        font-weight: bold;
        margin: 0 0 6px 0;
        text-align: right;
        color: #111827;
    }

    .text-right {
        text-align: right;
    }

    .badge-final {
        display: inline-block;
        margin-top: 10px;
        padding: 8px 18px;
        border: 3px solid #2fa84f;
        border-radius: 6px;
        color: #2fa84f;
        font-weight: bold;
        font-size: 18px;
        transform: rotate(-4deg);
    }

    .badge-confirmed {
        display: inline-block;
        margin-top: 10px;
        padding: 6px 16px;
        border: 2px solid #5278ff;
        border-radius: 999px;
        color: #5278ff;
        font-weight: bold;
        font-size: 12px;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .section-label {
        color: #6b7280;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 4px;
    }

    .parties-table {
        margin-top: 26px;
    }

    .parties-table td {
        vertical-align: top;
        width: 50%;
        padding: 0;
    }

    .items-table {
        margin-top: 26px;
    }

    .items-table th {
        background: #f3f4f6;
        text-align: left;
        padding: 10px 12px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #374151;
        border-bottom: 1px solid #e5e7eb;
    }

    .items-table td {
        padding: 12px;
        border-bottom: 1px solid #e5e7eb;
    }

    .totals-table {
        width: 260px;
        margin-left: auto;
        margin-top: 18px;
    }

    .totals-table td {
        padding: 6px 0;
    }

    .totals-table .label {
        text-align: right;
        color: #6b7280;
        padding-right: 16px;
    }

    .totals-table .value {
        text-align: right;
        font-weight: bold;
        width: 120px;
    }

    .totals-table .grand td {
        border-top: 2px solid #111827;
        padding-top: 10px;
        font-size: 16px;
    }

    .totals-table .grand .value {
        color: {{ $stage === 'final' ? '#2fa84f' : '#111827' }};
    }

    .footer-note {
        margin-top: 50px;
        text-align: center;
        color: #9ca3af;
        font-size: 11px;
    }
</style>
</head>
<body>

    @if ($stage === 'confirmation')
        <div class="watermark">CONFIRMED</div>
    @endif

    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                @if ($villa->logo && file_exists(public_path('storage/'.$villa->logo)))
                    <img class="villa-logo" src="{{ public_path('storage/'.$villa->logo) }}">
                @endif
                <div class="villa-name">{{ $villa->name }}</div>
                <div class="muted">
                    @if ($villa->address){{ $villa->address }}<br>@endif
                    @if ($villa->phone_number){{ $villa->phone_number }}<br>@endif
                    @if ($villa->email){{ $villa->email }}@endif
                </div>
            </td>
            <td style="width: 45%;">
                <div class="invoice-title">{{ $stage === 'final' ? 'FINAL INVOICE' : 'INVOICE' }}</div>
                <div class="muted text-right">Invoice #: INV-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}-{{ $stage === 'final' ? 'F' : 'C' }}</div>
                <div class="muted text-right">Date: {{ now()->format('d M Y') }}</div>
                <div class="text-right">
                    @if ($stage === 'final')
                        <span class="badge-final">FULLY PAID</span>
                    @else
                        <span class="badge-confirmed">Confirmed</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="parties-table">
        <tr>
            <td>
                <div class="section-label">Billed To</div>
                <div style="font-weight: bold;">{{ $booking->customer_name }}</div>
                @if ($booking->customer_email)<div class="muted">{{ $booking->customer_email }}</div>@endif
                @if ($booking->customer_phone)<div class="muted">{{ $booking->customer_phone }}</div>@endif
            </td>
            <td>
                <div class="section-label">Stay Details</div>
                <div>Room: {{ $booking->room->name_or_number }} ({{ $booking->room->type }})</div>
                <div>Check-in: {{ $booking->check_in->format('d M Y') }}</div>
                <div>Check-out: {{ $booking->check_out->format('d M Y') }}</div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Nights</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $booking->room->name_or_number }} — {{ $booking->room->type }}</td>
                <td class="text-right">{{ max(1, $booking->check_in->diffInDays($booking->check_out)) }}</td>
                <td class="text-right">{{ $villa->currency }} {{ number_format($booking->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="label">Total Amount</td>
            <td class="value">{{ $villa->currency }} {{ number_format($booking->total_amount, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Advance Payment (Paid)</td>
            <td class="value">{{ $villa->currency }} {{ number_format($booking->advance_payment, 2) }}</td>
        </tr>
        <tr class="grand">
            <td class="label">{{ $stage === 'final' ? 'Balance Due' : 'Remaining Balance Due' }}</td>
            <td class="value">{{ $villa->currency }} {{ number_format($booking->total_amount - $booking->advance_payment, 2) }}</td>
        </tr>
    </table>

    <div class="footer-note">
        Thank you for choosing {{ $villa->name }}.
    </div>

</body>
</html>
