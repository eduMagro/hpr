@extends('pdfs.asistente.base-informe')

@section('content')
    @php
        $tipoInforme = $informe->tipo ?? 'generico';
    @endphp

    @if($tipoInforme === 'peso_obra')
        {{-- Kilos por Obra --}}

        {{-- Resumen --}}
        @if(!empty($resumen))
        <div class="section">
            <div class="section-title">Resumen de Producción por Obra</div>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">{{ number_format($resumen['total_kilos'] ?? 0, 0, ',', '.') }}</div>
                        <div class="summary-label">Kilos Totales</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">{{ $resumen['obras_con_produccion'] ?? 0 }}</div>
                        <div class="summary-label">Obras con Producción</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value" style="font-size: 12px;">{{ Str::limit($resumen['obra_mayor_produccion'] ?? 'N/A', 20) }}</div>
                        <div class="summary-label">Mayor Producción</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Tabla de kilos por obra --}}
        <div class="section">
            <div class="section-title">Producción por Obra</div>
            <table>
                <thead>
                    <tr>
                        <th>Obra</th>
                        <th>Cliente</th>
                        <th class="text-right">Kilos</th>
                        <th class="text-right">Planillas</th>
                        <th class="text-center">% del Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalKilos = $resumen['total_kilos'] ?? 1;
                    @endphp
                    @forelse($datos['kilos_por_obra'] ?? [] as $item)
                    <tr>
                        <td><strong>{{ Str::limit($item['obra'] ?? 'Sin nombre', 30) }}</strong></td>
                        <td>{{ Str::limit($item['cliente'] ?? '-', 25) }}</td>
                        <td class="text-right">{{ number_format($item['kilos'] ?? 0, 0, ',', '.') }} kg</td>
                        <td class="text-right">{{ $item['num_planillas'] ?? 0 }}</td>
                        <td class="text-center">{{ $totalKilos > 0 ? number_format((($item['kilos'] ?? 0) / $totalKilos) * 100, 1) : 0 }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center">No hay datos de producción por obra para el periodo</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background-color: #e5e7eb; font-weight: bold;">
                        <td colspan="2"><strong>TOTAL</strong></td>
                        <td class="text-right"><strong>{{ number_format($datos['total_kilos'] ?? 0, 0, ',', '.') }} kg</strong></td>
                        <td class="text-right">-</td>
                        <td class="text-center">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>

    @elseif($tipoInforme === 'planilleros')
        {{-- Producción por Planillero --}}

        {{-- Resumen --}}
        @if(!empty($resumen))
        <div class="section">
            <div class="section-title">Resumen de Producción por Operario</div>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">{{ number_format($resumen['total_kilos'] ?? 0, 0, ',', '.') }}</div>
                        <div class="summary-label">Kilos Totales</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">{{ $resumen['planilleros_activos'] ?? 0 }}</div>
                        <div class="summary-label">Operarios Activos</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">{{ number_format($resumen['promedio_por_planillero'] ?? 0, 0, ',', '.') }}</div>
                        <div class="summary-label">Promedio por Operario (kg)</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box" style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);">
                        <div class="summary-value" style="font-size: 14px;">{{ Str::limit($resumen['mejor_planillero'] ?? 'N/A', 15) }}</div>
                        <div class="summary-label">Mejor Rendimiento</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Tabla de producción por usuario --}}
        <div class="section">
            <div class="section-title">Ranking de Producción</div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Operario</th>
                        <th>Máquinas</th>
                        <th class="text-right">Kilos</th>
                        <th class="text-right">Elementos</th>
                        <th class="text-center">Rendimiento</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $mejorPlanillero = $resumen['mejor_planillero'] ?? '';
                        $promedioKilos = $resumen['promedio_por_planillero'] ?? 1;
                    @endphp
                    @forelse($datos['produccion_por_usuario'] ?? [] as $index => $item)
                    <tr>
                        <td>
                            @if($index === 0)
                                <span class="badge badge-success">1</span>
                            @elseif($index === 1)
                                <span class="badge badge-info">2</span>
                            @elseif($index === 2)
                                <span class="badge badge-warning">3</span>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </td>
                        <td>
                            <strong>{{ $item['nombre'] }}</strong>
                            @if($item['nombre'] === $mejorPlanillero)
                                ⭐
                            @endif
                        </td>
                        <td>{{ Str::limit($item['maquinas'] ?? '-', 30) }}</td>
                        <td class="text-right">{{ number_format($item['kilos'] ?? 0, 0, ',', '.') }} kg</td>
                        <td class="text-right">{{ $item['elementos'] ?? 0 }}</td>
                        <td class="text-center">
                            @php
                                $rendimiento = $promedioKilos > 0 ? (($item['kilos'] ?? 0) / $promedioKilos) * 100 : 100;
                            @endphp
                            @if($rendimiento >= 120)
                                <span class="badge badge-success">EXCELENTE</span>
                            @elseif($rendimiento >= 80)
                                <span class="badge badge-info">NORMAL</span>
                            @else
                                <span class="badge badge-warning">MEJORAR</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">No hay datos de producción por operario para el periodo</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background-color: #e5e7eb; font-weight: bold;">
                        <td colspan="3"><strong>TOTAL</strong></td>
                        <td class="text-right"><strong>{{ number_format($datos['total_kilos'] ?? 0, 0, ',', '.') }} kg</strong></td>
                        <td class="text-right">-</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    @elseif($tipoInforme === 'planillas_pendientes')
        {{-- Planillas Pendientes --}}

        {{-- Alertas --}}
        @if(($resumen['atrasadas'] ?? 0) > 0)
        <div class="alert alert-danger">
            <strong>Atención:</strong> Hay {{ $resumen['atrasadas'] }} planilla(s) atrasada(s) que requieren atención inmediata.
        </div>
        @endif

        @if(($resumen['urgentes'] ?? 0) > 0)
        <div class="alert alert-warning">
            <strong>Urgente:</strong> {{ $resumen['urgentes'] }} planilla(s) con fecha de entrega en los próximos 3 días.
        </div>
        @endif

        {{-- Resumen --}}
        @if(!empty($resumen))
        <div class="section">
            <div class="section-title">Resumen de Estado</div>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">{{ $resumen['total_pendientes'] ?? 0 }}</div>
                        <div class="summary-label">Planillas Pendientes</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box">
                        <div class="summary-value">{{ number_format($resumen['kilos_pendientes'] ?? 0, 0, ',', '.') }}</div>
                        <div class="summary-label">Kilos Pendientes</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box" style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);">
                        <div class="summary-value highlight-negative">{{ $resumen['atrasadas'] ?? 0 }}</div>
                        <div class="summary-label">Atrasadas</div>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-box" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);">
                        <div class="summary-value highlight-warning">{{ $resumen['urgentes'] ?? 0 }}</div>
                        <div class="summary-label">Urgentes (< 3 días)</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Resumen por estado --}}
        @if(!empty($datos['resumen_por_estado']))
        <div class="section">
            <div class="section-title">Distribución por Estado</div>
            <table>
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th class="text-right">Cantidad</th>
                        <th class="text-right">Kilos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($datos['resumen_por_estado'] as $estado => $info)
                    <tr>
                        <td>
                            @if($estado === 'pendiente')
                                <span class="badge badge-warning">{{ strtoupper($estado) }}</span>
                            @elseif($estado === 'fabricando')
                                <span class="badge badge-info">{{ strtoupper($estado) }}</span>
                            @else
                                <span class="badge badge-success">{{ strtoupper($estado) }}</span>
                            @endif
                        </td>
                        <td class="text-right">{{ $info['cantidad'] ?? 0 }}</td>
                        <td class="text-right">{{ number_format($info['kilos'] ?? 0, 0, ',', '.') }} kg</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Tabla de planillas --}}
        <div class="section">
            <div class="section-title">Detalle de Planillas Pendientes</div>
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Obra</th>
                        <th class="text-right">Peso</th>
                        <th class="text-center">Entrega</th>
                        <th class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($datos['planillas'] ?? [] as $planilla)
                    <tr>
                        <td><strong>{{ $planilla['codigo'] ?? 'N/A' }}</strong></td>
                        <td>{{ Str::limit($planilla['cliente'] ?? '-', 20) }}</td>
                        <td>{{ Str::limit($planilla['obra'] ?? '-', 20) }}</td>
                        <td class="text-right">{{ number_format($planilla['peso'] ?? 0, 0, ',', '.') }} kg</td>
                        <td class="text-center">
                            @if($planilla['urgencia'] === 'atrasada')
                                <span class="highlight-negative">{{ $planilla['fecha_entrega'] }}</span>
                            @elseif($planilla['urgencia'] === 'urgente')
                                <span class="highlight-warning">{{ $planilla['fecha_entrega'] }}</span>
                            @else
                                {{ $planilla['fecha_entrega'] }}
                            @endif
                        </td>
                        <td class="text-center">
                            @if($planilla['urgencia'] === 'atrasada')
                                <span class="badge badge-danger">ATRASADA</span>
                            @elseif($planilla['urgencia'] === 'urgente')
                                <span class="badge badge-warning">URGENTE</span>
                            @elseif($planilla['estado'] === 'fabricando')
                                <span class="badge badge-info">FABRICANDO</span>
                            @else
                                <span class="badge badge-warning">PENDIENTE</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">No hay planillas pendientes</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    @else
        {{-- Informe Genérico --}}

        {{-- Resumen si existe --}}
        @if(!empty($resumen))
        <div class="section">
            <div class="section-title">Resumen</div>
            <table>
                @foreach($resumen as $key => $value)
                <tr>
                    <td style="width: 40%;"><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}</strong></td>
                    <td>
                        @if(is_numeric($value) && $value > 1000)
                            {{ number_format($value, 0, ',', '.') }}
                        @elseif(is_bool($value))
                            {{ $value ? 'Sí' : 'No' }}
                        @else
                            {{ $value }}
                        @endif
                    </td>
                </tr>
                @endforeach
            </table>
        </div>
        @endif

        {{-- Datos completos --}}
        <div class="section">
            <div class="section-title">Datos del Informe</div>
            @foreach($datos as $seccion => $contenido)
                @if($seccion !== 'fecha' && $seccion !== 'periodo')
                    @if(is_array($contenido) && count($contenido) > 0)
                        @if(isset($contenido[0]) && is_array($contenido[0]))
                            {{-- Es un array de arrays, mostrar como tabla --}}
                            <h4 style="margin: 15px 0 10px 0; font-size: 12px;">{{ ucfirst(str_replace('_', ' ', $seccion)) }}</h4>
                            <table>
                                <thead>
                                    <tr>
                                        @foreach(array_keys($contenido[0]) as $header)
                                            <th>{{ ucfirst(str_replace('_', ' ', $header)) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contenido as $fila)
                                    <tr>
                                        @foreach($fila as $valor)
                                        <td>
                                            @if(is_numeric($valor) && $valor > 100)
                                                {{ number_format($valor, 0, ',', '.') }}
                                            @else
                                                {{ $valor }}
                                            @endif
                                        </td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            {{-- Es un array simple, mostrar como lista --}}
                            <h4 style="margin: 15px 0 10px 0; font-size: 12px;">{{ ucfirst(str_replace('_', ' ', $seccion)) }}</h4>
                            <ul style="margin-left: 20px;">
                                @foreach($contenido as $key => $valor)
                                    <li>
                                        @if(is_string($key))
                                            <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                        @endif
                                        @if(is_numeric($valor) && $valor > 100)
                                            {{ number_format($valor, 0, ',', '.') }}
                                        @else
                                            {{ $valor }}
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @elseif(!is_array($contenido))
                        <p><strong>{{ ucfirst(str_replace('_', ' ', $seccion)) }}:</strong> {{ $contenido }}</p>
                    @endif
                @endif
            @endforeach
        </div>
    @endif
@endsection
