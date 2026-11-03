@extends('pdf.layout')

@php
    $titles = [
        'income' => 'Income Report',
        'expense' => 'Expense Report',
        'profit_loss' => 'Profit & Loss Statement',
    ];
@endphp

@section('title', $titles[$reportType].' — '.$globalSettings->villa_name)

@section('content')

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
                <div class="doc-title">{{ $titles[$reportType] }}</div>
                <div class="muted text-right">Period: {{ $periodLabel }}</div>
                <div class="muted text-right">Generated: {{ now()->format('d M Y, H:i') }}</div>
            </td>
        </tr>
    </table>

    @if ($reportType !== 'expense')
        <div class="section-title">Income Transactions</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Customer</th>
                    <th>Room</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($incomeRows as $row)
                    <tr>
                        <td>{{ $row['date']->format('d M Y') }}</td>
                        <td>{{ $row['type'] }}</td>
                        <td>{{ $row['booking']->customer_name }}</td>
                        <td>{{ $row['booking']->room->name_or_number }}</td>
                        <td class="text-right">{{ $globalSettings->currency }} {{ number_format($row['amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted" style="text-align: center;">No income recorded for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if ($reportType !== 'income')
        <div class="section-title">Expense Transactions</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($expenseRows as $expense)
                    <tr>
                        <td>{{ $expense['date']->format('d M Y') }}</td>
                        <td>{{ $expense['category'] }}@if ($expense['booking'])<br><span class="muted">Booking #{{ $expense['booking']->id }} · {{ $expense['booking']->customer_name }}</span>@endif</td>
                        <td>{{ $expense['description'] ?: '—' }}</td>
                        <td class="text-right">{{ $globalSettings->currency }} {{ number_format($expense['amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted" style="text-align: center;">No expenses recorded for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <table class="totals-table">
        @if ($reportType !== 'expense')
            <tr>
                <td class="label">Total Revenue</td>
                <td class="value">{{ $globalSettings->currency }} {{ number_format($totalIncome, 2) }}</td>
            </tr>
        @endif
        @if ($reportType !== 'income')
            <tr>
                <td class="label">Total Expenses</td>
                <td class="value">{{ $globalSettings->currency }} {{ number_format($totalExpenses, 2) }}</td>
            </tr>
        @endif
        @if ($reportType === 'profit_loss')
            <tr class="grand">
                <td class="label">Net Profit</td>
                <td class="value" style="color: {{ $netProfit >= 0 ? '#2fa84f' : '#dc2626' }};">{{ $globalSettings->currency }} {{ number_format($netProfit, 2) }}</td>
            </tr>
        @endif
    </table>

    @if ($reportType === 'profit_loss' && $bookingProfits->isNotEmpty())
        <div class="section-title">Net Profit per Booking</div>
        <p class="muted" style="margin: 4px 0 0;">Revenue (room + extra charges) minus the direct costs recorded against each booking.</p>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Booking</th>
                    <th class="text-right">Revenue</th>
                    <th>Direct costs</th>
                    <th class="text-right">Net profit</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bookingProfits as $row)
                    <tr>
                        <td><strong>#{{ $row['booking']->id }} · {{ $row['booking']->customer_name }}</strong><br><span class="muted">{{ $row['booking']->room_label }} · {{ $row['booking']->check_in->format('d M') }} – {{ $row['booking']->check_out->format('d M Y') }}</span></td>
                        <td class="text-right">{{ number_format($row['revenue'], 2) }}<br><span class="muted">Room {{ number_format($row['room_charges'], 2) }}@if ($row['extras_total'] > 0) + extras {{ number_format($row['extras_total'], 2) }}@endif</span></td>
                        <td>
                            @forelse ($row['expenses'] as $expense)
                                {{ $expense['category'] }}@if ($expense['description']) <span class="muted">({{ $expense['description'] }})</span>@endif: {{ number_format($expense['amount'], 2) }}<br>
                            @empty
                                <span class="muted">—</span>
                            @endforelse
                            @if ($row['expenses']->count() > 1)<strong>Total: {{ number_format($row['costs'], 2) }}</strong>@endif
                        </td>
                        <td class="text-right"><strong style="color: {{ $row['net_profit'] >= 0 ? '#2fa84f' : '#dc2626' }};">{{ number_format($row['net_profit'], 2) }}</strong><br><span class="muted">{{ number_format($row['margin'], 1) }}%</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <table class="totals-table">
            <tr><td class="label">Booking Revenue</td><td class="value">{{ $globalSettings->currency }} {{ number_format($bookingProfitTotals['revenue'], 2) }}</td></tr>
            <tr><td class="label">Booking Direct Costs</td><td class="value">{{ $globalSettings->currency }} {{ number_format($bookingProfitTotals['costs'], 2) }}</td></tr>
            <tr class="grand"><td class="label">Booking Net Profit</td><td class="value" style="color: {{ $bookingProfitTotals['net_profit'] >= 0 ? '#2fa84f' : '#dc2626' }};">{{ $globalSettings->currency }} {{ number_format($bookingProfitTotals['net_profit'], 2) }}</td></tr>
        </table>
    @endif

    <div class="footer-note">
        Generated by {{ $globalSettings->villa_name }} — Villa Management System.
    </div>

@endsection
