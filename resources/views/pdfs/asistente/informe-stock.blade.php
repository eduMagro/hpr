@extends('pdfs.asistente.base-informe')

@section('content')
    @php
        $tipoInforme = $informe->tipo ?? 'stock_general';
    @endphp

    @if($tipoInforme === 'stock_general')
        {{-- Stock General --}}

        {{-- Resumen --}}
        @if(!empty($resumen))
        <div class="section">
            <div class="section-title">Resumen Ejecutivo</div>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">{{ number_format($resumen['total_kg'] ?? 0, 0, ',', '.') }}</div>
                        <div class="summary-label">Kilos Totales</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">{{ $resumen['diametros_con_stock'] ?? 0 }}</div>
                        <div class="summary-label">Diámetros con Stock</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">Ø{{ $resumen['diametro_mayor_stock'] ?? '-' }}mm</div>
                        <div class="summary-label">Mayor Stock</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Tabla de Stock --}}
        <div class="section">
            <div class="section-title">Stock por Diámetro</div>
            <table>
                <thead>
                    <tr>
                        <th>Diámetro</th>
                        <th class="text-right">Encarretado (kg)</th>
                        <th class="text-right">Barras (kg)</th>
                        <th class="text-right">Total (kg)</th>
                        <th class="text-center">% del Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalGeneral = collect($datos['stock_por_diametro'] ?? [])->sum('total');
                    @endphp
                    @forelse($datos['stock_por_diametro'] ?? [] as $item)
                        @if($item['total'] > 0)
                        <tr>
                            <td><strong>Ø{{ $item['diametro'] }}mm</strong></td>
                            <td class="text-right">{{ number_format($item['encarretado'], 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($item['barras'], 0, ',', '.') }}</td>
                            <td class="text-right"><strong>{{ number_format($item['total'], 0, ',', '.') }}</strong></td>
                            <td class="text-center">
                                @if($totalGeneral > 0)
                                    {{ number_format(($item['total'] / $totalGeneral) * 100, 1) }}%
                                @else
                                    0%
                                @endif
                            </td>
                        </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No hay datos de stock disponibles</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background-color: #e5e7eb; font-weight: bold;">
                        <td><strong>TOTAL</strong></td>
                        <td class="text-right">{{ number_format(collect($datos['stock_por_diametro'] ?? [])->sum('encarretado'), 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format(collect($datos['stock_por_diametro'] ?? [])->sum('barras'), 0, ',', '.') }}</td>
                        <td class="text-right"><strong>{{ number_format($datos['total_general'] ?? 0, 0, ',', '.') }}</strong></td>
                        <td class="text-center">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>

    @elseif($tipoInforme === 'stock_critico')
        {{-- Stock Crítico --}}

        {{-- Alerta si hay productos críticos --}}
        @if(($resumen['total_criticos'] ?? 0) > 0)
            <div class="alert alert-warning">
                <strong>Atención:</strong> Se han detectado {{ $resumen['total_criticos'] }} producto(s) por debajo del stock mínimo.
                @if(($resumen['urgencia_alta'] ?? 0) > 0)
                    <strong class="highlight-negative">{{ $resumen['urgencia_alta'] }} con urgencia alta.</strong>
                @endif
            </div>
        @else
            <div class="alert alert-success">
                <strong>Excelente:</strong> Todos los productos están por encima del stock mínimo requerido.
            </div>
        @endif

        {{-- Resumen --}}
        @if(!empty($resumen))
        <div class="section">
            <div class="section-title">Resumen de Alertas</div>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-box" style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);">
                        <div class="summary-value highlight-negative">{{ $resumen['urgencia_alta'] ?? 0 }}</div>
                        <div class="summary-label">Urgencia Alta</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);">
                        <div class="summary-value highlight-warning">{{ $resumen['urgencia_media'] ?? 0 }}</div>
                        <div class="summary-label">Urgencia Media</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">{{ $resumen['total_criticos'] ?? 0 }}</div>
                        <div class="summary-label">Total Críticos</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Tabla de productos críticos --}}
        @if(!empty($datos['productos_criticos']))
        <div class="section">
            <div class="section-title">Productos bajo Mínimo</div>
            <table>
                <thead>
                    <tr>
                        <th>Diámetro</th>
                        <th class="text-right">Stock Actual</th>
                        <th class="text-right">Mínimo</th>
                        <th class="text-right">Reponer</th>
                        <th class="text-center">Cobertura</th>
                        <th class="text-center">Urgencia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($datos['productos_criticos'] as $item)
                    <tr>
                        <td><strong>Ø{{ $item['diametro'] }}mm</strong></td>
                        <td class="text-right">{{ number_format($item['stock_actual'], 0, ',', '.') }} kg</td>
                        <td class="text-right">{{ number_format($item['minimo'], 0, ',', '.') }} kg</td>
                        <td class="text-right"><strong>{{ number_format($item['reponer'], 0, ',', '.') }} kg</strong></td>
                        <td class="text-center">{{ $item['porcentaje'] }}%</td>
                        <td class="text-center">
                            @if($item['urgencia'] === 'alta')
                                <span class="badge badge-danger">ALTA</span>
                            @elseif($item['urgencia'] === 'media')
                                <span class="badge badge-warning">MEDIA</span>
                            @else
                                <span class="badge badge-info">BAJA</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Recomendaciones --}}
        @if(!empty($datos['recomendaciones']))
        <div class="section">
            <div class="section-title">Recomendaciones</div>
            @foreach($datos['recomendaciones'] as $rec)
            <div class="recommendation">
                <div class="recommendation-title">
                    @if($rec['prioridad'] === 'alta')
                        🔴
                    @elseif($rec['prioridad'] === 'media')
                        🟡
                    @else
                        🟢
                    @endif
                    Ø{{ $rec['diametro'] }}mm - Prioridad {{ ucfirst($rec['prioridad']) }}
                </div>
                <div class="recommendation-text">
                    {{ $rec['mensaje'] }}
                    @if(($rec['consumo_estimado_mensual'] ?? 0) > 0)
                        <br><em>Consumo estimado mensual: {{ number_format($rec['consumo_estimado_mensual'], 0, ',', '.') }} kg</em>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    @endif
@endsection
