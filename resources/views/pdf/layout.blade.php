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
