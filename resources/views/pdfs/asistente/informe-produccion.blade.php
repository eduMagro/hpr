@extends('pdfs.asistente.base-informe')

@section('content')
    @php
        $tipoInforme = $informe->tipo ?? 'produccion_diaria';
    @endphp

    @if($tipoInforme === 'produccion_diaria')
        {{-- Producción Diaria --}}

        {{-- Resumen --}}
        @if(!empty($resumen))
        <div class="section">
            <div class="section-title">Resumen del Día</div>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">{{ number_format($resumen['total_kilos'] ?? 0, 0, ',', '.') }}</div>
                        <div class="summary-label">Kilos Fabricados</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">{{ $resumen['total_elementos'] ?? 0 }}</div>
                        <div class="summary-label">Elementos</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">{{ $resumen['maquinas_activas'] ?? 0 }}</div>
                        <div class="summary-label">Máquinas Activas</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box" style="{{ ($datos['comparativa_ayer']['variacion_porcentaje'] ?? 0) >= 0 ? 'background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);' : 'background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);' }}">
                        <div class="summary-value {{ ($datos['comparativa_ayer']['variacion_porcentaje'] ?? 0) >= 0 ? 'highlight-positive' : 'highlight-negative' }}">
                            {{ ($datos['comparativa_ayer']['variacion_porcentaje'] ?? 0) >= 0 ? '+' : '' }}{{ $datos['comparativa_ayer']['variacion_porcentaje'] ?? 0 }}%
                        </div>
                        <div class="summary-label">vs Ayer</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Producción por máquina --}}
        <div class="section">
            <div class="section-title">Producción por Máquina</div>
            <table>
                <thead>
                    <tr>
                        <th>Máquina</th>
                        <th class="text-right">Kilos</th>
                        <th class="text-right">Elementos</th>
                        <th class="text-center">% del Total</th>
                        <th class="text-center">Rendimiento</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalKilos = $resumen['total_kilos'] ?? 1;
                        $mejorMaquina = $resumen['mejor_maquina'] ?? '';
                    @endphp
                    @forelse($datos['produccion_por_maquina'] ?? [] as $item)
                    <tr>
                        <td>
                            <strong>{{ $item['maquina'] }}</strong>
                            @if($item['maquina'] === $mejorMaquina)
                                <span class="badge badge-success">TOP</span>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($item['kilos'], 0, ',', '.') }} kg</td>
                        <td class="text-right">{{ $item['elementos'] }}</td>
                        <td class="text-center">
                            {{ $totalKilos > 0 ? number_format(($item['kilos'] / $totalKilos) * 100, 1) : 0 }}%
                        </td>
                        <td class="text-center">
                            @php
                                $porcentaje = $totalKilos > 0 ? ($item['kilos'] / $totalKilos) * 100 : 0;
                            @endphp
                            @if($porcentaje >= 30)
                                <span class="badge badge-success">ALTO</span>
                            @elseif($porcentaje >= 15)
                                <span class="badge badge-info">NORMAL</span>
                            @else
                                <span class="badge badge-warning">BAJO</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center">No hay datos de producción para el día</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background-color: #e5e7eb; font-weight: bold;">
                        <td><strong>TOTAL</strong></td>
                        <td class="text-right"><strong>{{ number_format($datos['total_kilos'] ?? 0, 0, ',', '.') }} kg</strong></td>
                        <td class="text-right"><strong>{{ $datos['total_elementos'] ?? 0 }}</strong></td>
                        <td class="text-center">100%</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Comparativa --}}
        @if(!empty($datos['comparativa_ayer']))
        <div class="section">
            <div class="section-title">Comparativa con el Día Anterior</div>
            <table>
                <tr>
                    <th>Métrica</th>
                    <th class="text-right">Hoy</th>
                    <th class="text-right">Ayer</th>
                    <th class="text-center">Diferencia</th>
                </tr>
                <tr>
                    <td>Kilos Fabricados</td>
                    <td class="text-right">{{ number_format($datos['total_kilos'] ?? 0, 0, ',', '.') }} kg</td>
                    <td class="text-right">{{ number_format($datos['comparativa_ayer']['kilos_ayer'] ?? 0, 0, ',', '.') }} kg</td>
                    <td class="text-center {{ ($datos['comparativa_ayer']['variacion_porcentaje'] ?? 0) >= 0 ? 'highlight-positive' : 'highlight-negative' }}">
                        {{ ($datos['comparativa_ayer']['variacion_porcentaje'] ?? 0) >= 0 ? '+' : '' }}{{ $datos['comparativa_ayer']['variacion_porcentaje'] ?? 0 }}%
                    </td>
                </tr>
            </table>
        </div>
        @endif

    @elseif($tipoInforme === 'produccion_semanal')
        {{-- Producción Semanal --}}

        {{-- Resumen --}}
        @if(!empty($resumen))
        <div class="section">
            <div class="section-title">Resumen de la Semana</div>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">{{ number_format($resumen['total_kilos'] ?? 0, 0, ',', '.') }}</div>
                        <div class="summary-label">Kilos Totales</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">{{ $resumen['total_elementos'] ?? 0 }}</div>
                        <div class="summary-label">Elementos</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">{{ number_format($resumen['promedio_diario'] ?? 0, 0, ',', '.') }}</div>
                        <div class="summary-label">Promedio Diario (kg)</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box" style="{{ ($datos['comparativa_semana_anterior']['variacion_porcentaje'] ?? 0) >= 0 ? 'background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);' : 'background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);' }}">
                        <div class="summary-value {{ ($datos['comparativa_semana_anterior']['variacion_porcentaje'] ?? 0) >= 0 ? 'highlight-positive' : 'highlight-negative' }}">
                            {{ ($datos['comparativa_semana_anterior']['variacion_porcentaje'] ?? 0) >= 0 ? '+' : '' }}{{ $datos['comparativa_semana_anterior']['variacion_porcentaje'] ?? 0 }}%
                        </div>
                        <div class="summary-label">vs Semana Anterior</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Producción por día --}}
        <div class="section">
            <div class="section-title">Producción por Día</div>
            <table>
                <thead>
                    <tr>
                        <th>Día</th>
                        <th class="text-right">Kilos</th>
                        <th class="text-right">Elementos</th>
                        <th class="text-center">% del Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalKilosSemana = $resumen['total_kilos'] ?? 1;
                        $mejorDia = $resumen['mejor_dia'] ?? '';
                    @endphp
                    @forelse($datos['produccion_por_dia'] ?? [] as $item)
                    <tr>
                        <td>
                            <strong>{{ $item['fecha'] }}</strong>
                            @if($item['fecha'] === $mejorDia)
                                <span class="badge badge-success">MEJOR DÍA</span>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($item['kilos'], 0, ',', '.') }} kg</td>
                        <td class="text-right">{{ $item['elementos'] }}</td>
                        <td class="text-center">{{ $totalKilosSemana > 0 ? number_format(($item['kilos'] / $totalKilosSemana) * 100, 1) : 0 }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center">No hay datos de producción para la semana</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background-color: #e5e7eb; font-weight: bold;">
                        <td><strong>TOTAL SEMANA</strong></td>
                        <td class="text-right"><strong>{{ number_format($datos['total_kilos'] ?? 0, 0, ',', '.') }} kg</strong></td>
                        <td class="text-right"><strong>{{ $datos['total_elementos'] ?? 0 }}</strong></td>
                        <td class="text-center">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Producción por máquina --}}
        @if(!empty($datos['produccion_por_maquina']))
        <div class="section">
            <div class="section-title">Producción por Máquina</div>
            <table>
                <thead>
                    <tr>
                        <th>Máquina</th>
                        <th class="text-right">Kilos</th>
                        <th class="text-right">Elementos</th>
                        <th class="text-center">% del Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($datos['produccion_por_maquina'] as $item)
                    <tr>
                        <td><strong>{{ $item['maquina'] }}</strong></td>
                        <td class="text-right">{{ number_format($item['kilos'], 0, ',', '.') }} kg</td>
                        <td class="text-right">{{ $item['elementos'] }}</td>
                        <td class="text-center">{{ $totalKilosSemana > 0 ? number_format(($item['kilos'] / $totalKilosSemana) * 100, 1) : 0 }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    @elseif($tipoInforme === 'consumo_maquinas')
        {{-- Consumo por Máquinas --}}

        {{-- Resumen --}}
        @if(!empty($resumen))
        <div class="section">
            <div class="section-title">Resumen de Consumo</div>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">{{ number_format($resumen['total_consumido'] ?? 0, 0, ',', '.') }}</div>
                        <div class="summary-label">Kilos Consumidos</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">{{ $resumen['maquinas_con_consumo'] ?? 0 }}</div>
                        <div class="summary-label">Máquinas Activas</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">{{ $resumen['maquina_mayor_consumo'] ?? 'N/A' }}</div>
                        <div class="summary-label">Mayor Consumo</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">Ø{{ $resumen['diametro_mas_usado'] ?? '-' }}mm</div>
                        <div class="summary-label">Diámetro + Usado</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Consumo por máquina --}}
        <div class="section">
            <div class="section-title">Consumo por Máquina</div>
            <table>
                <thead>
                    <tr>
                        <th>Máquina</th>
                        <th>Tipo</th>
                        <th class="text-right">Kilos Consumidos</th>
                        <th class="text-right">Movimientos</th>
                        <th class="text-center">% del Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalConsumo = $resumen['total_consumido'] ?? 1;
                    @endphp
                    @forelse($datos['consumo_por_maquina'] ?? [] as $item)
                    <tr>
                        <td><strong>{{ $item['maquina'] }}</strong></td>
                        <td>{{ $item['tipo'] ?? '-' }}</td>
                        <td class="text-right">{{ number_format($item['kilos_consumidos'], 0, ',', '.') }} kg</td>
                        <td class="text-right">{{ $item['num_movimientos'] }}</td>
                        <td class="text-center">{{ $totalConsumo > 0 ? number_format(($item['kilos_consumidos'] / $totalConsumo) * 100, 1) : 0 }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center">No hay datos de consumo para el periodo</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Consumo por diámetro --}}
        @if(!empty($datos['consumo_por_diametro']))
        <div class="section">
            <div class="section-title">Consumo por Diámetro</div>
            <table>
                <thead>
                    <tr>
                        <th>Diámetro</th>
                        <th class="text-right">Kilos</th>
                        <th class="text-center">% del Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($datos['consumo_por_diametro'] as $item)
                    <tr>
                        <td><strong>Ø{{ $item['diametro'] }}mm</strong></td>
                        <td class="text-right">{{ number_format($item['kilos'], 0, ',', '.') }} kg</td>
                        <td class="text-center">{{ $totalConsumo > 0 ? number_format(($item['kilos'] / $totalConsumo) * 100, 1) : 0 }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    @endif
@endsection
