<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo ?? 'Informe FERRALLIN' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        .container {
            padding: 20px;
        }

        /* Header */
        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-top {
            display: table;
            width: 100%;
        }

        .logo-section {
            display: table-cell;
            width: 60%;
            vertical-align: middle;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
            letter-spacing: 1px;
        }

        .logo-subtitle {
            font-size: 10px;
            color: #6b7280;
            margin-top: 2px;
        }

        .info-section {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: middle;
            font-size: 10px;
            color: #6b7280;
        }

        .report-title {
            font-size: 18px;
            color: #1f2937;
            margin-top: 15px;
            font-weight: 600;
        }

        .report-meta {
            font-size: 10px;
            color: #6b7280;
            margin-top: 5px;
        }

        /* Content */
        .content {
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            padding-bottom: 8px;
            margin-bottom: 12px;
            border-bottom: 2px solid #e5e7eb;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }

        th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: 600;
            padding: 10px 8px;
            text-align: left;
            border-bottom: 2px solid #d1d5db;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        tr:hover {
            background-color: #f3f4f6;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Summary boxes */
        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .summary-item {
            display: table-cell;
            width: 25%;
            padding: 10px;
            text-align: center;
            vertical-align: top;
        }

        .summary-box {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #d1d5db;
        }

        .summary-value {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
        }

        .summary-label {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            margin-top: 5px;
        }

        /* Status badges */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }

        /* Highlights */
        .highlight-positive {
            color: #059669;
            font-weight: 600;
        }

        .highlight-negative {
            color: #dc2626;
            font-weight: 600;
        }

        .highlight-warning {
            color: #d97706;
            font-weight: 600;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
        }

        .footer-disclaimer {
            font-style: italic;
            margin-bottom: 5px;
        }

        /* Page break */
        .page-break {
            page-break-after: always;
        }

        /* Charts placeholder */
        .chart-placeholder {
            background: #f9fafb;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            color: #9ca3af;
            margin: 15px 0;
        }

        /* Recommendations */
        .recommendation {
            background: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 12px 15px;
            margin: 10px 0;
            border-radius: 0 8px 8px 0;
        }

        .recommendation-title {
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 5px;
        }

        .recommendation-text {
            color: #1f2937;
            font-size: 10px;
        }

        /* Alert boxes */
        .alert {
            padding: 12px 15px;
            margin: 10px 0;
            border-radius: 8px;
        }

        .alert-warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
        }

        .alert-danger {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
        }

        .alert-success {
            background: #d1fae5;
            border-left: 4px solid #10b981;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="logo-section">
                    <div class="logo">FERRALLIN</div>
                    <div class="logo-subtitle">Sistema Experto de Gestión - Hierros Paco Reyes</div>
                </div>
                <div class="info-section">
                    <div><strong>Generado:</strong> {{ $fecha_generacion ?? now()->format('d/m/Y H:i') }}</div>
                    <div><strong>Usuario:</strong> {{ $usuario ?? 'Sistema' }}</div>
                    <div><strong>Ref:</strong> INF-{{ str_pad($informe->id ?? 0, 6, '0', STR_PAD_LEFT) }}</div>
                </div>
            </div>
            <div class="report-title">{{ $titulo ?? 'Informe' }}</div>
            @if(isset($datos['fecha']))
                <div class="report-meta">Datos actualizados al {{ $datos['fecha'] }}</div>
            @endif
            @if(isset($datos['periodo']))
                <div class="report-meta">Periodo: {{ $datos['periodo'] }}</div>
            @endif
        </div>

        <!-- Content -->
        <div class="content">
            @yield('content')
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-disclaimer">
                Este informe ha sido generado automáticamente por FERRALLIN - Sistema Experto.
            </div>
            <div>
                Hierros Paco Reyes S.L. - {{ now()->format('Y') }} | Página 1
            </div>
        </div>
    </div>
</body>
</html>
