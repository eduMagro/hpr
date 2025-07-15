@props([
    'nombreMeses',
    'stockData',
    'pedidosPorDiametro',
    'necesarioPorDiametro',
    'totalGeneral',
    'consumoOrigen' => [],
    'consumosPorMes' => [],
    'productoBaseInfo' => [],
    'stockPorProductoBase' => [],
    'kgPedidosPorProductoBase' => [],
    'resumenReposicion' => [],
    'recomendacionReposicion' => [],
])

<div class="space-y-12">

    @php
        // 🎨 Función para marcar celdas en rojo según condiciones de diámetro y tipo
        function rojo($diametro, $tipo, $longitud = null)
        {
            if ($tipo === 'barra' && $diametro == 10 && $longitud == 12) {
                return '';
            }
            if ($tipo === 'encarretado' && in_array($diametro, [25, 32])) {
                return 'bg-red-200'; // 🔴 marcar producto no disponible
            }
            if ($tipo === 'barra') {
                if (in_array($diametro, [8, 10])) {
                    return 'bg-red-200';
                }
                if ($diametro == 12 && in_array($longitud, [15, 16])) {
                    return 'bg-red-200';
                }
            }
            return '';
        }
    @endphp

    <h2 class="text-2xl font-bold text-gray-800 mt-4">📦 Estado actual de stock, pedidos y necesidades</h2>
    <div class="overflow-x-auto bg-white shadow-lg rounded-lg border border-gray-300">
        <table class="w-full text-sm text-center border-collapse">
            <thead>
                <tr class="bg-blue-900 text-white text-xs">
                    <th rowspan="2" class="border px-2 py-2">Ø mm<br>
                        <span class="text-blue-200 text-[10px]">Diámetro</span>
                    </th>
                    <th colspan="3" class="border px-2 py-2">Encarretado</th>
                    <th colspan="3" class="border px-2 py-2">Barras 12 m</th>
                    <th colspan="3" class="border px-2 py-2">Barras 14 m</th>
                    <th colspan="3" class="border px-2 py-2">Barras 15 m</th>
                    <th colspan="3" class="border px-2 py-2">Barras 16 m</th>
                    <th colspan="3" class="border px-2 py-2">Barras Total</th>
                    <th colspan="3" class="border px-2 py-2">Total</th>
                </tr>
                <tr class="bg-blue-800 text-white text-xs">
                    @for ($i = 0; $i < 7; $i++)
                        <th class="border px-2 py-1">Stock</th>
                        <th class="border px-2 py-1">Pedido</th>
                        <th class="border px-2 py-1">Necesario</th>
                    @endfor
                </tr>
            </thead>

            <tbody class="bg-white">
                @foreach ($stockData as $diametro => $stock)
                    @php
                        $pedido = $pedidosPorDiametro[$diametro] ?? [
                            'encarretado' => 0,
                            'barras' => collect([12 => 0, 14 => 0, 15 => 0, 16 => 0]),
                            'barras_total' => 0,
                            'total' => 0,
                        ];
                        $necesario = $necesarioPorDiametro[$diametro] ?? [
                            'encarretado' => 0,
                            'barras' => collect([12 => 0, 14 => 0, 15 => 0, 16 => 0]),
                            'barras_total' => 0,
                            'total' => 0,
                        ];
                    @endphp
                    <tr class="hover:bg-gray-100 transition">
                        <td class="border px-2 py-1 font-bold text-gray-800">{{ $diametro }}</td>

                        {{-- 🔵 Encarretado --}}
                        @php
                            $stockVal = $stock['encarretado'];
                            $pedidoVal = $pedido['encarretado'];
                            $necesarioVal = $necesario['encarretado'];
                            $claseRojo = rojo($diametro, 'encarretado');
                            $colorNecesario =
                                $necesarioVal > $stockVal
                                    ? 'text-red-600 font-semibold'
                                    : 'text-green-600 font-semibold';
                        @endphp
                        <td class="border px-2 py-1 {{ $claseRojo }}">
                            {{ !$claseRojo ? number_format($stockVal, 2, ',', '.') : '' }}</td>
                        <td class="border px-2 py-1 {{ $claseRojo }}">
                            {{ !$claseRojo ? number_format($pedidoVal, 2, ',', '.') : '' }}</td>
                        <td class="border px-2 py-1 {{ $claseRojo }}">
                            @if (!$claseRojo)
                                <div class="flex items-center justify-start gap-1">
                                    <input type="checkbox" name="seleccionados[]"
                                        value="encarretado-{{ $diametro }}">
                                    <input type="hidden" name="detalles[encarretado-{{ $diametro }}][tipo]"
                                        value="encarretado">
                                    <input type="hidden" name="detalles[encarretado-{{ $diametro }}][diametro]"
                                        value="{{ $diametro }}">
                                    <input type="hidden" name="detalles[encarretado-{{ $diametro }}][cantidad]"
                                        value="{{ round(max(0, $necesarioVal - $stockVal), 2) }}">
                                    <span
                                        class="{{ $colorNecesario }}">{{ number_format($necesarioVal, 2, ',', '.') }}</span>
                                </div>
                            @endif
                        </td>

                        {{-- 🔵 Barras por longitud --}}
                        @foreach ([12, 14, 15, 16] as $longitud)
                            @php
                                $stockVal = $stock['barras'][$longitud] ?? 0;
                                $pedidoVal = $pedido['barras'][$longitud] ?? 0;
                                $necesarioVal = $necesario['barras'][$longitud] ?? 0;
                                $claseRojo = rojo($diametro, 'barra', $longitud);
                                $colorNecesario =
                                    $necesarioVal > $stockVal
                                        ? 'text-red-600 font-semibold'
                                        : 'text-green-600 font-semibold';
                            @endphp
                            <td class="border px-2 py-1 {{ $claseRojo }}">
                                {{ !$claseRojo ? number_format($stockVal, 2, ',', '.') : '' }}</td>
                            <td class="border px-2 py-1 {{ $claseRojo }}">
                                {{ !$claseRojo ? number_format($pedidoVal, 2, ',', '.') : '' }}</td>
                            <td class="border px-2 py-1 {{ $claseRojo }}">
                                @if (!$claseRojo)
                                    <div class="flex items-center justify-start gap-1">
                                        <input type="checkbox" name="seleccionados[]"
                                            value="barra-{{ $diametro }}-{{ $longitud }}">
                                        <input type="hidden"
                                            name="detalles[barra-{{ $diametro }}-{{ $longitud }}][tipo]"
                                            value="barra">
                                        <input type="hidden"
                                            name="detalles[barra-{{ $diametro }}-{{ $longitud }}][diametro]"
                                            value="{{ $diametro }}">
                                        <input type="hidden"
                                            name="detalles[barra-{{ $diametro }}-{{ $longitud }}][longitud]"
                                            value="{{ $longitud }}">
                                        <input type="hidden"
                                            name="detalles[barra-{{ $diametro }}-{{ $longitud }}][cantidad]"
                                            value="{{ round($necesarioVal, 2) }}">
                                        <span
                                            class="{{ $colorNecesario }}">{{ number_format($necesarioVal, 2, ',', '.') }}</span>
                                    </div>
                                @endif
                            </td>
                        @endforeach

                        <td class="border px-2 py-1 font-semibold text-gray-800">
                            {{ number_format($stock['barras_total'], 2, ',', '.') }}</td>
                        <td class="border px-2 py-1 font-semibold text-gray-800">
                            {{ number_format($pedido['barras_total'], 2, ',', '.') }}</td>
                        <td class="border px-2 py-1 font-semibold text-gray-800">
                            {{ number_format($necesario['barras_total'], 2, ',', '.') }}</td>
                        <td class="border px-2 py-1 font-bold bg-gray-100">
                            {{ number_format($stock['total'], 2, ',', '.') }}</td>
                        <td class="border px-2 py-1 font-bold bg-gray-100">
                            {{ number_format($pedido['total'], 2, ',', '.') }}</td>
                        <td class="border px-2 py-1 font-bold bg-gray-100">
                            {{ number_format($necesario['total'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-end">

        <button type="button" onclick="mostrarConfirmacion()"
            class="bg-blue-600 text-white px-4 py-2 mr-4 rounded hover:bg-blue-700">
            Crear pedido con seleccionados
        </button>


        <span class="bg-gray-700 text-white rounded px-4 py-2 font-bold shadow">
            📌 Total general disponible: {{ number_format($totalGeneral, 2, ',', '.') }} kg
        </span>
    </div>



    {{-- 📊 CONSUMO HISTÓRICO --}}
    @if (!empty($resumenReposicion))
        <h2 class="text-2xl font-bold text-blue-900 mt-4">📊 Consumo histórico por producto base</h2>

        <div class="overflow-x-auto bg-white shadow-lg rounded-lg border border-blue-200">
            <table class="min-w-full text-sm text-center border-collapse">
                <thead class="bg-blue-900 text-white text-xs">
                    <tr>
                        <th class="px-4 py-2 border">
                            Tipo<br>
                            <span class="text-blue-200 text-[9px]">Barra o encarrete</span>
                        </th>
                        <th class="px-4 py-2 border">
                            Ø mm<br>
                            <span class="text-blue-200 text-[9px]">Diámetro</span>
                        </th>
                        <th class="px-4 py-2 border">
                            Longitud<br>
                            <span class="text-blue-200 text-[9px]">Metros (solo barras)</span>
                        </th>
                        <th class="px-4 py-2 border">
                            Total a origen<br>
                            <span class="text-blue-200 text-[9px]">Kg consumidos desde el inicio</span>
                        </th>
                        <th class="px-4 py-2 border">
                            Media mensual a Origen<br>
                            <span class="text-blue-200 text-[9px]">Promedio Kg/mes </span>
                        </th>

                        <th class="px-4 py-2 border">{{ $nombreMeses['haceDosMeses'] }}</th>
                        <th class="px-4 py-2 border">{{ $nombreMeses['mesAnterior'] }}</th>
                        <th class="px-4 py-2 border">{{ $nombreMeses['mesActual'] }}</th>
                        <th class="px-4 py-2 border">
                            Stock actual<br>
                            <span class="text-blue-200 text-[9px]">Disponible en almacén</span>
                        </th>
                        <th class="px-4 py-2 border">
                            Cobertura (días)<br>
                            <span class="text-blue-200 text-[10px]">Stock actual ÷ consumo diario(Mes anterior)</span>
                        </th>
                        <th class="px-4 py-2 border">
                            Stock seguridad<br>
                            <span class="text-blue-200 text-[10px]">Consumo diario(Mes anterior) × 15 días</span>
                        </th>
                        <th class="px-4 py-2 border">
                            Kg pedidos<br>
                            <span class="text-blue-200 text-[9px]">Pendiente de llegar</span>
                        </th>
                        <th class="px-4 py-2 border">
                            Diferencia<br>
                            <span class="text-blue-200 text-[9px]">(Stock + pedidos) - consumo mes anterior</span>
                        </th>
                    </tr>
                </thead>

                <tbody class="bg-white">
                    @foreach ($resumenReposicion as $id => $item)
                        <tr class="hover:bg-blue-50 transition">
                            <td class="px-4 py-2 border">{{ ucfirst($item['tipo']) }}</td>
                            <td class="px-4 py-2 border">{{ $item['diametro'] }}</td>
                            <td class="px-4 py-2 border">
                                {{ $item['tipo'] === 'barra' ? $item['longitud'] . ' m' : '—' }}
                            </td>

                            <td class="border px-2 py-1">
                                {{ number_format($consumoOrigen[$id]['total_origen'] ?? 0, 0, ',', '.') }} Kg</td>
                            <td class="border px-2 py-1">
                                {{ number_format($consumoOrigen[$id]['media_mensual'] ?? 0, 0, ',', '.') }} Kg/mes
                            </td>
                            <td class="border px-2 py-1">
                                {{ number_format($consumosPorMes['mes_hace_dos'][$id] ?? 0, 0, ',', '.') }} Kg
                            </td>
                            <td class="border px-2 py-1">
                                {{ number_format($consumosPorMes['mes_anterior'][$id] ?? 0, 0, ',', '.') }} Kg
                            </td>
                            <td class="border px-2 py-1">
                                {{ number_format($consumosPorMes['mes_actual'][$id] ?? 0, 0, ',', '.') }} Kg
                            </td>

                            <td class="px-4 py-2 border">{{ number_format($item['stock'], 0, ',', '.') }} kg</td>
                            <td class="px-4 py-2 border">
                                {{ ($item['consumo_ant'] ?? 0) > 0 ? round($item['stock'] / ($item['consumo_ant'] / 30), 0) : '∞' }}
                                días
                            </td>
                            <td class="px-4 py-2 border">
                                {{ number_format((($item['consumo_ant'] ?? 0) / 30) * 15, 0, ',', '.') }} kg
                            </td>
                            <td class="px-4 py-2 border">
                                {{ number_format($item['pedido'], 0, ',', '.') }} kg
                            </td>
                            <td
                                class="px-4 py-2 border {{ $item['stock'] + $item['pedido'] - ($item['consumo_ant'] ?? 0) < 0 ? 'text-red-600' : 'text-green-700' }}">
                                {{ number_format($item['stock'] + $item['pedido'] - ($item['consumo_ant'] ?? 0), 0, ',', '.') }}
                                kg
                            </td>
                        </tr>
                    @endforeach

                </tbody>

            </table>
        </div>
    @endif

    {{-- 📦 REPOSICIÓN SUGERIDA --}}
    @if (!empty($resumenReposicion))
        <h2 class="text-2xl font-bold text-blue-900 mt-4">📦 Reposición sugerida</h2>
        <div class="overflow-x-auto bg-white shadow-lg rounded-lg border border-blue-200">
            <table class="min-w-full text-sm text-center border-collapse">
                <thead class="bg-blue-900 text-white text-xs">
                    <tr>
                        <th class="px-4 py-2 border">Tipo<br><span class="text-blue-200 text-[9px]">Barra o
                                encarrete</span></th>
                        <th class="px-4 py-2 border">Diámetro<br><span class="text-blue-200 text-[9px]">Ø mm</span>
                        </th>
                        <th class="px-4 py-2 border">Longitud<br><span class="text-blue-200 text-[9px]">Metros (si
                                aplica)</span></th>

                        <th class="px-4 py-2 border">Consumo 14d<br><span class="text-blue-200 text-[9px]">Últimas 2
                                semanas</span></th>
                        <th class="px-4 py-2 border">Consumo 30d<br><span class="text-blue-200 text-[9px]">Último
                                mes</span></th>
                        <th class="px-4 py-2 border">Consumo 60d<br><span class="text-blue-200 text-[9px]">Últimos 2
                                meses</span></th>
                        <th class="px-4 py-2 border">Stock actual<br><span class="text-blue-200 text-[9px]">En
                                almacén</span></th>
                        <th class="px-4 py-2 border">Kg pedidos<br><span
                                class="text-blue-200 text-[9px]">Pendiente</span></th>
                        <th class="px-4 py-2 border">Reposición<br><span class="text-blue-200 text-[9px]">Cantidad a
                                pedir</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @foreach ($resumenReposicion as $item)
                        @if ($item['reposicion'] > 0)
                            <tr class="hover:bg-blue-50 transition">
                                <td class="px-4 py-2 border">{{ ucfirst($item['tipo']) }}</td>
                                <td class="px-4 py-2 border">{{ $item['diametro'] }}</td>
                                <td class="px-4 py-2 border">
                                    {{ $item['tipo'] === 'barra' ? $item['longitud'] . ' m' : '—' }}</td>
                                <td class="border px-2 py-1">
                                    {{ number_format($consumosPorMes['mes_hace_dos'][$id] ?? 0, 0, ',', '.') }} Kg
                                </td>
                                <td class="border px-2 py-1">
                                    {{ number_format($consumosPorMes['mes_anterior'][$id] ?? 0, 0, ',', '.') }} Kg
                                </td>
                                <td class="border px-2 py-1">
                                    {{ number_format($consumosPorMes['mes_actual'][$id] ?? 0, 0, ',', '.') }} Kg
                                </td>

                                <td class="px-4 py-2 border text-right text-blue-700 font-semibold">
                                    {{ number_format($item['stock'], 2, ',', '.') }} kg</td>
                                <td class="px-4 py-2 border text-right text-indigo-600 font-semibold">
                                    {{ number_format($item['pedido'], 2, ',', '.') }} kg</td>
                                <td class="px-4 py-2 border text-right text-red-600 font-bold">⚠
                                    {{ number_format($item['reposicion'], 2, ',', '.') }} kg</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif


    {{-- 📦 RECOMENDACION REPOSICIÓN --}}
    @if (!empty($recomendacionReposicion))
        <h2 class="text-2xl font-bold text-blue-900 mt-4">📦 Recomendación de Reposición</h2>
        <div class="overflow-x-auto bg-white shadow-lg rounded-lg border border-blue-200 mt-4">
            <table class="min-w-full text-sm text-center border-collapse">
                <thead class="bg-blue-900 text-white text-xs">
                    <tr>
                        <th class="px-4 py-2 border">Tipo</th>
                        <th class="px-4 py-2 border">Ø mm</th>
                        <th class="px-4 py-2 border">Longitud</th>
                        <th class="px-4 py-2 border">Tendencia consumo</th>
                        <th class="px-4 py-2 border">Stock objetivo (2 meses)</th>
                        <th class="px-4 py-2 border">Stock actual</th>
                        <th class="px-4 py-2 border">Pedidos</th>
                        <th class="px-4 py-2 border">A pedir</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @foreach ($recomendacionReposicion as $rec)
                        <tr class="hover:bg-blue-50 transition">
                            <td class="px-4 py-2 border">{{ ucfirst($rec['tipo']) }}</td>
                            <td class="px-4 py-2 border">{{ $rec['diametro'] }}</td>
                            <td class="px-4 py-2 border">{{ $rec['longitud'] ?? '—' }}</td>
                            <td class="px-4 py-2 border">{{ number_format($rec['tendencia'], 0, ',', '.') }} kg/mes
                            </td>
                            <td class="px-4 py-2 border">{{ number_format($rec['stock_objetivo'], 0, ',', '.') }} kg
                            </td>
                            <td class="px-4 py-2 border">{{ number_format($rec['stock_actual'], 0, ',', '.') }} kg
                            </td>
                            <td class="px-4 py-2 border">{{ number_format($rec['pedido'], 0, ',', '.') }} kg</td>
                            <td class="px-4 py-2 border font-bold text-red-600">
                                {{ number_format($rec['reponer'], 0, ',', '.') }} kg</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
