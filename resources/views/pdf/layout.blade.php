<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
            background: #fff;
            line-height: 1.5;
        }

        .page-header {
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .page-header .app-name {
            font-size: 10px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }

        .page-header h1 {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }

        .page-header .subtitle {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }

        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #e5e7eb;
            padding: 6px 20px;
            font-size: 9px;
            color: #9ca3af;
            display: table;
            width: 100%;
        }

        .page-footer .footer-left {
            display: table-cell;
            text-align: left;
        }

        .page-footer .footer-right {
            display: table-cell;
            text-align: right;
        }

        .content {
            padding: 0 0 40px 0;
        }

        .section {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px 14px;
            margin-bottom: 12px;
            background: #fff;
        }

        .section-title {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b7280;
            margin-bottom: 6px;
        }

        .section-text {
            font-size: 12px;
            color: #374151;
            line-height: 1.6;
        }

        .section-green {
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        .section-green .section-title {
            color: #15803d;
        }

        .section-blue {
            border-color: #bfdbfe;
            background: #eff6ff;
        }

        .section-blue .section-title {
            color: #1d4ed8;
        }

        .section-orange {
            border-color: #fed7aa;
            background: #fff7ed;
        }

        .section-orange .section-title {
            color: #c2410c;
        }

        .section-amber {
            border-color: #fde68a;
            background: #fffbeb;
        }

        .section-amber .section-title {
            color: #b45309;
        }

        .section-purple {
            border-color: #ddd6fe;
            background: #faf5ff;
        }

        .section-purple .section-title {
            color: #7c3aed;
        }

        .score-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .score-green {
            background: #dcfce7;
            color: #15803d;
        }

        .score-yellow {
            background: #fef9c3;
            color: #854d0e;
        }

        .score-red {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
        }

        .badge-amber {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-indigo {
            background: #e0e7ff;
            color: #3730a3;
        }

        .badge-purple {
            background: #ede9fe;
            color: #6d28d9;
        }

        .badge-green {
            background: #dcfce7;
            color: #166534;
        }

        .badge-gray {
            background: #f3f4f6;
            color: #374151;
        }

        .model-label {
            font-size: 10px;
            color: #9ca3af;
            margin-left: 8px;
        }

        ul.bullet-list {
            list-style: none;
            padding: 0;
        }

        ul.bullet-list li {
            padding: 3px 0 3px 14px;
            position: relative;
            font-size: 12px;
            color: #374151;
            line-height: 1.5;
        }

        ul.bullet-list li::before {
            content: '›';
            position: absolute;
            left: 0;
            color: #6b7280;
            font-weight: 700;
        }

        .two-col {
            display: table;
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-bottom: 12px;
        }

        .two-col .col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .meta-row {
            font-size: 10px;
            color: #9ca3af;
            margin-top: 6px;
        }

        table.source-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        table.source-table th {
            background: #f9fafb;
            padding: 4px 6px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }

        table.source-table td {
            padding: 4px 6px;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
        }

        table.source-table tr:last-child td {
            border-bottom: none;
        }

        .source-section-title {
            font-size: 10px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 8px 0 4px 0;
        }

        .checklist-item {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 10px;
            margin: 2px;
        }

        .checklist-pass {
            background: #f0fdf4;
            color: #15803d;
        }

        .checklist-fail {
            background: #fef2f2;
            color: #b91c1c;
        }

        .crawler-row {
            display: table;
            width: 100%;
            margin-bottom: 3px;
        }

        .crawler-name {
            display: table-cell;
            font-size: 10px;
            color: #374151;
        }

        .crawler-status {
            display: table-cell;
            text-align: right;
            font-size: 10px;
            font-weight: 600;
        }

        .crawler-allowed {
            color: #15803d;
        }

        .crawler-blocked {
            color: #b91c1c;
        }

        .crawler-partial {
            color: #d97706;
        }

        .tags {
            line-height: 2;
        }

        .tags .badge {
            margin-right: 3px;
        }
    </style>
</head>

<body>
    <div class="page-footer">
        <div class="footer-left">{{ config('app.name') }}</div>
        <div class="footer-right">Generated {{ now()->format('d M Y') }}</div>
    </div>

    <div class="page-header">
        <div class="app-name">{{ config('app.name') }} · Analysis Report</div>
        <h1>{{ $title }}</h1>
        @if (!empty($subtitle))
            <div class="subtitle">{{ $subtitle }}</div>
        @endif
    </div>

    <div class="content">
        @yield('content')
    </div>
</body>

</html>
