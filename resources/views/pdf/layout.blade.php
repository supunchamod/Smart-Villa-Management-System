<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>@yield('title')</title>
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

    .doc-title {
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

    .section-title {
        font-size: 14px;
        font-weight: bold;
        color: #111827;
        margin: 26px 0 0 0;
    }

    .items-table {
        margin-top: 14px;
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
        width: 280px;
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
        width: 140px;
    }

    .totals-table .grand td {
        border-top: 2px solid #111827;
        padding-top: 10px;
        font-size: 16px;
    }

    .payment-status-pill {
        display: inline-block;
        padding: 5px 14px;
        border: 2px solid;
        border-radius: 999px;
        font-weight: bold;
        font-size: 11px;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .incl-box { margin-top: 18px; padding: 12px 14px; border: 1px solid #e5e7eb; border-radius: 6px; background: #fafafa; }
    .trip-table { margin-top: 6px; }
    .trip-table th { text-align: left; padding: 4px 6px; font-size: 10.5px; letter-spacing: .05em; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
    .trip-table td { padding: 5px 6px; font-size: 12.5px; color: #374151; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
    .trip-table th.text-right, .trip-table td.text-right { text-align: right; }
    .incl-table { margin-top: 4px; }
    .incl-table td { width: 50%; padding: 2px 0; font-size: 12.5px; color: #374151; }
    .section-gap { margin-top: 14px; }
    .items-table td.sub { color: #6b7280; font-size: 11px; padding-top: 0; border-bottom: none; }
    .items-table tr.extra-row td { padding-top: 9px; padding-bottom: 9px; }
    .items-table tr.group-row td { background: #fafafa; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; padding: 7px 12px; }
    .totals-table .grand.settled td { border-top-color: #2fa84f; }
    .totals-table .extra-line .label { font-size: 12px; }
    .totals-table .extra-line .value { font-weight: normal; font-size: 12px; }
    .totals-table .subtotal-line td { border-top: 1px solid #d1d5db; padding-top: 8px; }
    .method-note { margin-top: 6px; text-align: right; color: #6b7280; font-size: 12px; }

    @isset($web)
    /* On-screen bill: a centred, screenshot-friendly card. */
    body { background: #eef1f6; margin: 0; padding: 24px 12px; }
    .bill-sheet { max-width: 780px; margin: 0 auto; background: #fff; padding: 36px 40px; border-radius: 10px; box-shadow: 0 6px 30px rgba(15,23,42,.10); position: relative; overflow: hidden; }
    .bill-toolbar { max-width: 780px; margin: 0 auto 14px; display: flex; flex-wrap: wrap; gap: 8px; }
    .bill-toolbar a, .bill-toolbar button { font: inherit; font-weight: bold; font-size: 13px; padding: 9px 16px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; color: #111827; text-decoration: none; cursor: pointer; }
    .bill-toolbar .primary { background: #5278ff; border-color: #5278ff; color: #fff; }
    .bill-toolbar .wa { background: #25d366; border-color: #25d366; color: #fff; }
    .watermark { position: absolute; top: 320px; left: 80px; }
    @media print { body { background: #fff; padding: 0; } .bill-sheet { box-shadow: none; padding: 0; } .bill-toolbar { display: none; } }
    @endisset

    .footer-note {
        margin-top: 50px;
        text-align: center;
        color: #9ca3af;
        font-size: 11px;
    }
</style>
</head>
<body>
@yield('content')
</body>
</html>
