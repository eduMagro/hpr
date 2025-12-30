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
    'configuracion_vista_stock' => [],
])

<div class="space-y-12">

    @php
        // 1) Defaults y luego los que vienen del service (los del service tienen prioridad)
        $configuracionVistaStock = array_merge(
            [
                'incluir_encarretado' => true,
                'longitudes_barras' => [12, 14, 15, 16],
                'es_nave_almacen' => false,
            ],
            $configuracion_vista_stock ?? [],
        );

        // 2) Variables que usa la vista
        $incluirEncarretado = $configuracionVistaStock['incluir_encarretado'] ?? true;
        $longitudesBarras = $configuracionVistaStock['longitudes_barras'] ?? [12, 14, 15, 16];
        $numBloques = ($incluirEncarretado ? 1 : 0) + count($longitudesBarras) + 2;

        $esNaveAlmacen = $configuracionVistaStock['es_nave_almacen'] ?? false;
        $rojo = function ($diametro, $tipo, $longitud = null) use ($esNaveAlmacen) {
            if ($esNaveAlmacen) {
                return '';
            }

            if ($tipo === 'barra' && $diametro == 10 && $longitud == 12) {
                return '';
            }
            if ($tipo === 'encarretado' && in_array($diametro, [25, 32])) {
                return 'bg-red-200';
            }
            if ($tipo === 'barra') {
                if (in_array($diametro, [6, 8, 10])) {
                    return 'bg-red-200';
                }
                if ($diametro == 12 && in_array($longitud, [15, 16])) {
                    return 'bg-red-200';
                }
            }
            return '';
        };
    @endphp


    <div class="overflow-x-auto bg-white shadow-lg border border-gray-300">
        <table class="w-full text-sm text-center border-collapse">
            <thead>
                <tr class="bg-blue-900 text-white text-xs">
                    <th rowspan="2" class="border px-2 py-2">
                        Ø mm<br><span class="text-blue-200 text-[10px]">Diámetro</span>
                    </th>

                    @if ($incluirEncarretado)
                        <th colspan="2" class="border px-2 py-2">Encarretado</th>
                    @endif

                    @foreach ($longitudesBarras as $L)
                        <th colspan="2" class="border px-2 py-2">Barras {{ $L }} m</th>
                    @endforeach

                    <th colspan="2" class="border px-2 py-2">Barras Total</th>
                    <th colspan="2" class="border px-2 py-2">Total</th>
                </tr>

                <tr class="bg-blue-800 text-white text-xs">
                    @for ($i = 0; $i < $numBloques; $i++)
                        <th class="border px-2 py-1">Stock</th>
                        <th class="border px-2 py-1">Pedido</th>
                    @endfor
                </tr>
            </thead>


            <tbody class="bg-white">
                @foreach ($stockData as $diametro => $stock)
                    @php
                        $pedido = $pedidosPorDiametro[$diametro] ?? [
                            'encarretado' => 0,
                            'barras' => collect($longitudesBarras)->mapWithKeys(fn($L) => [$L => 0])->all(),
                            'barras_total' => 0,
                            'total' => 0,
                        ];
                        $necesario = $necesarioPorDiametro[$diametro] ?? [
                            'encarretado' => 0,
                            'barras' => collect($longitudesBarras)->mapWithKeys(fn($L) => [$L => 0])->all(),
                            'barras_total' => 0,
                            'total' => 0,
                        ];
                    @endphp

                    <tr class="hover:bg-gray-100 transition">
                        <td class="border px-2 py-1 font-bold text-gray-800">{{ $diametro }}</td>

                        {{-- 🔵 Encarretado (solo si aplica) --}}
                        @if ($incluirEncarretado)
                            @php
                                $stockVal = $stock['encarretado'] ?? 0;
                                $pedidoVal = $pedido['encarretado'] ?? 0;
                                $necesarioVal = $necesario['encarretado'] ?? 0;
                                $claseRojo = $rojo($diametro, 'encarretado', null);
                            @endphp

                            <td class="border px-2 py-1 {{ $claseRojo }}">
                                {{ !$claseRojo ? number_format($stockVal, 0, ',', '.') : '' }}
                            </td>

                            <td class="border px-2 py-1 {{ $claseRojo }}">
                                @if (!$claseRojo)
                                    <div class="gap-2">
                                        <span>{{ number_format($pedidoVal, 0, ',', '.') }}</span>
                                        <input type="hidden"
                                            name="detalles[encarretado-{{ $diametro }}][producto_base_id]"
                                            value="{{ $productoBaseInfo['encarretado'][$diametro]['id'] ?? '' }}">


                                        <label class="inline-flex items-center gap-1">
                                            <input type="checkbox" name="seleccionados[]"
                                                value="encarretado-{{ $diametro }}"
                                                @change="toggleItem({ 
                                                    id: 'encarretado-{{ $diametro }}', 
                                                    tipo: 'encarretado', 
                                                    diametro: '{{ $diametro }}', 
                                                    cantidad: {{ $pedidoVal > 0 ? $pedidoVal : max(0, $necesarioVal - $stockVal) }},
                                                    base_id: '{{ $productoBaseInfo['encarretado'][$diametro]['id'] ?? '' }}'
                                                })"
                                                :checked="isInCart('encarretado-{{ $diametro }}')"
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <input type="hidden"
                                                name="detalles[encarretado-{{ $diametro }}][tipo]"
                                                value="encarretado">
                                            <input type="hidden"
                                                name="detalles[encarretado-{{ $diametro }}][diametro]"
                                                value="{{ $diametro }}">
                                            <input type="hidden"
                                                name="detalles[encarretado-{{ $diametro }}][cantidad]"
                                                value="{{ round(max(0, $necesarioVal - $stockVal), 2) }}">
                                        </label>
                                    </div>
                                @endif
                            </td>
                        @endif

                        {{-- 🔵 Barras por longitud (dinámico) --}}
                        @foreach ($longitudesBarras as $longitud)
                            @php
                                $stockVal = $stock['barras'][$longitud] ?? 0;
                                $pedidoVal = $pedido['barras'][$longitud] ?? 0;
                                $necesarioVal = $necesario['barras'][$longitud] ?? 0;
                                $claseRojo = $rojo($diametro, 'barra', $longitud);
                            @endphp

                            <td class="border px-2 py-1 {{ $claseRojo }}">
                                {{ !$claseRojo ? number_format($stockVal, 0, ',', '.') : '' }}
                            </td>

                            <td class="border px-2 py-1 {{ $claseRojo }}">
                                @if (!$claseRojo)
                                    <div class= "gap-2">
                                        <span>{{ number_format($pedidoVal, 0, ',', '.') }}</span>
                                        <input type="hidden"
                                            name="detalles[barra-{{ $diametro }}-{{ $longitud }}][producto_base_id]"
                                            value="{{ $productoBaseInfo['barras'][$diametro][$longitud]['id'] ?? '' }}">

                                        <label class="inline-flex items-center gap-1">
                                            <input type="checkbox" name="seleccionados[]"
                                                value="barra-{{ $diametro }}-{{ $longitud }}"
                                                @change="toggleItem({ 
                                                    id: 'barra-{{ $diametro }}-{{ $longitud }}', 
                                                    tipo: 'barra', 
                                                    diametro: '{{ $diametro }}', 
                                                    longitud: '{{ $longitud }}',
                                                    cantidad: {{ $pedidoVal > 0 ? $pedidoVal : $necesarioVal }},
                                                    base_id: '{{ $productoBaseInfo['barras'][$diametro][$longitud]['id'] ?? '' }}'
                                                })"
                                                :checked="isInCart('barra-{{ $diametro }}-{{ $longitud }}')"
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
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
                                                value="{{ round($necesarioVal, 0) }}">
                                        </label>
                                    </div>
                                @endif
                            </td>
                        @endforeach


                        <td class="border px-2 py-1 font-semibold text-gray-800">
                            {{ number_format($stock['barras_total'], 0, ',', '.') }}</td>
                        <td class="border px-2 py-1 font-semibold text-gray-800">
                            {{ number_format($pedido['barras_total'], 0, ',', '.') }}</td>
                        {{-- <td class="border px-2 py-1 font-semibold text-gray-800">
                            {{ number_format($necesario['barras_total'], 0, ',', '.') }}</td> --}}
                        <td class="border px-2 py-1 font-bold bg-gray-100">
                            {{ number_format($stock['total'], 0, ',', '.') }}</td>
                        <td class="border px-2 py-1 font-bold bg-gray-100">
                            {{ number_format($pedido['total'], 0, ',', '.') }}</td>
                        {{-- <td class="border px-2 py-1 font-bold bg-gray-100">
                            {{ number_format($necesario['total'], 0, ',', '.') }}</td> --}}
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Botones de acción ahora en el sidebar del padre --}}


    {{-- 📊 CONSUMO HISTÓRICO --}}
    @if (!empty($resumenReposicion))
        <div class="p-6 rounded-3xl flex flex-wrap items-center justify-between gap-4 mt-8 mb-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-50 rounded-2xl ring-1 ring-blue-100">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="w-6 h-6 text-blue-600">
                        <path d="M3 3v18h18" />
                        <path d="M18 17V9" />
                        <path d="M13 17V5" />
                        <path d="M8 17v-3" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-black text-gray-900 tracking-tight italic uppercase">Consumo Histórico</h2>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Desglose por producto
                        base</p>
                </div>
            </div>
            {{-- Espacio para posibles acciones futuras --}}
        </div>

        <div class="bg-white shadow-lg border border-blue-100">
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
    {{-- @if (!empty($resumenReposicion))
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
    @endif --}}

    {{-- 📦 RECOMENDACION REPOSICIÓN --}}
    {{-- 📦 RECOMENDACION REPOSICIÓN --}}
    @if (!empty($recomendacionReposicion))
        <div class="p-6 rounded-3xl  flex flex-wrap items-center justify-between gap-4 mt-12 mb-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-50 rounded-2xl ring-1 ring-blue-100">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="w-6 h-6 text-blue-600">
                        <path d="m7.5 4.27 9 5.15" />
                        <path
                            d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                        <path d="m3.3 7 8.7 5 8.7-5" />
                        <path d="M12 22V12" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-black text-gray-900 tracking-tight italic uppercase">Recomendación Sugerida
                    </h2>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Acciones de reposición
                        necesarias</p>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-8 items-start">
            <div class="flex-1 overflow-x-auto bg-white shadow-lg border border-blue-100 w-full">
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
                                <td class="px-4 py-2 border">{{ number_format($rec['tendencia'], 0, ',', '.') }}
                                    kg/mes
                                </td>
                                <td class="px-4 py-2 border">{{ number_format($rec['stock_objetivo'], 0, ',', '.') }}
                                    kg
                                </td>
                                <td class="px-4 py-2 border">{{ number_format($rec['stock_actual'], 0, ',', '.') }} kg
                                </td>
                                <td class="px-4 py-2 border">{{ number_format($rec['pedido'], 0, ',', '.') }} kg</td>
                                <td
                                    class="px-4 py-2 border font-bold {{ $rec['reponer'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ number_format($rec['reponer'], 0, ',', '.') }} kg
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
