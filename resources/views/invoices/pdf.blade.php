@extends('pdf.layout')

@section('title', ($stage === 'final' ? 'Final Invoice' : 'Invoice').' — '.$booking->customer_name)

@section('content')

    @isset($web)
        <div class="bill-toolbar">
            <a class="primary" href="{{ route($stage === 'final' ? 'bookings.invoice.final' : 'bookings.invoice.confirmation', [$booking, 'download' => 1]) }}">Download PDF</a>
            @if ($whatsAppUrl)
                <a class="wa" href="{{ $whatsAppUrl }}" target="_blank" rel="noopener">Share via WhatsApp</a>
            @endif
            <button type="button" onclick="window.print()">Print</button>
            <a href="{{ route('bookings.show', $booking) }}">Back to booking</a>
        </div>
        <div class="bill-sheet">
    @endisset

    @if ($stage === 'confirmation')
        <div class="watermark">CONFIRMED</div>
    @endif

    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                @if ($globalSettings->villa_logo && file_exists(public_path($globalSettings->villa_logo)))
                    <img class="villa-logo" src="{{ public_path($globalSettings->villa_logo) }}">
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
                <div class="section-label">{{ $booking->booking_type === 'safari_only' ? 'Safari Details' : 'Stay Details' }}</div>
                @if ($booking->room)
                    <div>Cabana: {{ $booking->room->name_or_number }} ({{ $booking->room->type }})</div>
                @endif
                @if ($booking->booking_type !== 'accommodation_only' && $booking->safari_tour_name)
                    <div>Safari: {{ $booking->safari_tour_name }}</div>
                    @if ($booking->safari_date)
                        <div>Safari Date: {{ $booking->safari_date->format('d M Y') }}</div>
                    @endif
                    @if ($booking->safari_vehicle_details)
                        <div>Vehicle: {{ $booking->safari_vehicle_details }}</div>
                    @endif
                @endif
                @if ($booking->booking_type !== 'safari_only')
                    <div>Check-in: {{ $booking->check_in->format('d M Y') }}</div>
                    <div>Check-out: {{ $booking->check_out->format('d M Y') }}</div>
                @endif
                @if ($booking->board_type_label)
                    <div>Board Type: {{ $booking->board_type_label }}</div>
                @endif
            </td>
        </tr>
    </table>

    @php
        $currency = $globalSettings->currency;
        $money = fn ($amount) => $currency.' '.number_format((float) $amount, 2);
        $nights = $booking->nights;
        $ratePerNight = $nights > 0 ? (float) $booking->total_amount / $nights : (float) $booking->total_amount;
        $isStay = $booking->booking_type !== 'safari_only';
        $methodLabels = ['cash' => 'Cash', 'card' => 'Card', 'bank_transfer' => 'Bank Transfer'];
    @endphp

    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Rate</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr class="group-row"><td colspan="4">{{ $isStay ? 'Room Charges' : 'Safari Charges' }}</td></tr>
            <tr>
                <td>
                    {{ $booking->room ? $booking->room->name_or_number.' — '.$booking->room->type : $booking->safari_tour_name }}
                </td>
                <td class="text-right">{{ $isStay ? $nights.' '.\Illuminate\Support\Str::plural('night', $nights) : '1' }}</td>
                <td class="text-right">{{ $money($ratePerNight) }}</td>
                <td class="text-right">{{ $money($booking->total_amount) }}</td>
            </tr>
            @if ($booking->extras->isNotEmpty())
                <tr class="group-row"><td colspan="4">Extra Charges</td></tr>
                @foreach ($booking->extras as $extra)
                    <tr class="extra-row">
                        <td>{{ $extra->item_name }}</td>
                        <td class="text-right">{{ $extra->quantity }}</td>
                        <td class="text-right">{{ $money($extra->unit_price) }}</td>
                        <td class="text-right">{{ $money($extra->line_total) }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="label">{{ $isStay ? 'Room Charges' : 'Safari Charges' }}</td>
            <td class="value">{{ $money($booking->total_amount) }}</td>
        </tr>
        {{-- Every extra charge is listed by name with its own amount; there is no single lumped "Extra charges" line. --}}
        @foreach ($booking->extras as $extra)
            <tr class="extra-line">
                <td class="label">{{ $extra->item_name }}@if ($extra->quantity > 1) × {{ $extra->quantity }}@endif</td>
                <td class="value">{{ $money($extra->line_total) }}</td>
            </tr>
        @endforeach
        <tr class="subtotal-line">
            <td class="label">Subtotal</td>
            <td class="value">{{ $money($booking->grand_total) }}</td>
        </tr>
        <tr>
            <td class="label">Less: Advance Paid</td>
            <td class="value">− {{ $money($booking->advance_payment) }}</td>
        </tr>
        @if ($stage === 'final')
            <tr>
                <td class="label">Balance Settled at Checkout</td>
                <td class="value">{{ $money($booking->final_settlement_amount) }}</td>
            </tr>
        @endif
        <tr class="grand {{ $stage === 'final' ? 'settled' : '' }}">
            <td class="label">Balance Due</td>
            <td class="value" style="color: {{ $stage === 'final' ? '#2fa84f' : '#111827' }};">{{ $money($booking->remaining_balance) }}</td>
        </tr>
    </table>

    @if ($stage === 'final' && $booking->payment_method)
        <div class="method-note">Balance paid by {{ $methodLabels[$booking->payment_method] ?? ucfirst(str_replace('_', ' ', $booking->payment_method)) }}@if ($booking->checked_out_at) on {{ $booking->checked_out_at->format('d M Y') }}@endif</div>
    @endif

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

    @isset($web)
        </div>
    @endisset

@endsection
