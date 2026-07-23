@extends('pdf.layout')

@section('title', ($stage === 'final' ? 'Final Invoice' : 'Invoice').' — '.$booking->customer_name)

@section('content')

    @if ($stage === 'confirmation')
        <div class="watermark">CONFIRMED</div>
    @endif

    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                @if ($globalSettings->villa_logo && file_exists(public_path('storage/'.$globalSettings->villa_logo)))
                    <img class="villa-logo" src="{{ public_path('storage/'.$globalSettings->villa_logo) }}">
                @endif
                <div class="villa-name">{{ $globalSettings->villa_name }}</div>
                <div class="muted">
                    @if ($globalSettings->address){{ $globalSettings->address }}<br>@endif
                    @if ($globalSettings->phone_number){{ $globalSettings->phone_number }}<br>@endif
                    @if ($globalSettings->email){{ $globalSettings->email }}@endif
                </div>
            </td>
            <td style="width: 45%;">
                <div class="doc-title">{{ $stage === 'final' ? 'FINAL INVOICE' : 'INVOICE' }}</div>
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
                <div>Cabana: {{ $booking->room->name_or_number }} ({{ $booking->room->type }})</div>
                <div>Check-in: {{ $booking->check_in->format('d M Y') }}</div>
                <div>Check-out: {{ $booking->check_out->format('d M Y') }}</div>
                @if ($booking->board_type_label)
                    <div>Board Type: {{ $booking->board_type_label }}</div>
                @endif
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
                <td class="text-right">{{ $globalSettings->currency }} {{ number_format($booking->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="label">Total Amount</td>
            <td class="value">{{ $globalSettings->currency }} {{ number_format($booking->total_amount, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Advance Payment (Paid)</td>
            <td class="value">{{ $globalSettings->currency }} {{ number_format($booking->advance_payment, 2) }}</td>
        </tr>
        @if ($stage === 'final')
            <tr>
                <td class="label">Final Settlement (Paid)</td>
                <td class="value">{{ $globalSettings->currency }} {{ number_format($booking->final_settlement_amount, 2) }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td class="label">{{ $stage === 'final' ? 'Balance Due' : 'Remaining Balance Due' }}</td>
            <td class="value" style="color: {{ $stage === 'final' ? '#2fa84f' : '#111827' }};">{{ $globalSettings->currency }} {{ number_format($booking->remaining_balance, 2) }}</td>
        </tr>
    </table>

    @php
        $paymentStatusColors = [
            'Fully Paid' => '#2fa84f',
            'Partially Paid' => '#d97706',
            'Unpaid' => '#dc2626',
        ];
        $paymentStatusColor = $paymentStatusColors[$booking->payment_status_label] ?? '#6b7280';
    @endphp
    <div class="text-right" style="margin-top: 8px;">
        <span class="payment-status-pill" style="border-color: {{ $paymentStatusColor }}; color: {{ $paymentStatusColor }};">{{ $booking->payment_status_label }}</span>
    </div>

    <div class="footer-note">
        Thank you for choosing {{ $globalSettings->villa_name }}.
    </div>

@endsection
