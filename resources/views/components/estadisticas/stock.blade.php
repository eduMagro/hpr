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
        $configuracionVistaStock = array_merge(
            [
                'incluir_encarretado' => true,
                'longitudes_barras' => [12, 14, 15, 16],
                'es_nave_almacen' => false,
            ],
            $configuracion_vista_stock ?? [],
        );

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
                return 'bg-rose-50';
            }
            if ($tipo === 'barra') {
                if (in_array($diametro, [6, 8, 10])) {
                    return 'bg-rose-50';
                }
                if ($diametro == 12 && in_array($longitud, [15, 16])) {
                    return 'bg-rose-50';
                }
            }
            return '';
        };
    @endphp

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-center border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th rowspan="2"
                            class="px-3 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest border-r border-slate-200">
                            Diámetro
                        </th>

                        @if ($incluirEncarretado)
                            <th colspan="2"
                                class="px-3 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest border-r border-slate-200 bg-slate-100/30">
                                Encarretado</th>
                        @endif

                        @foreach ($longitudesBarras as $L)
                            <th colspan="2"
                                class="px-3 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest border-r border-slate-200">
                                Barras {{ $L }}m</th>
                        @endforeach

                        <th colspan="2"
                            class="px-3 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest border-r border-slate-200 bg-slate-50">
                            Barras Total</th>
                        <th colspan="2"
                            class="px-3 py-3 text-[10px] font-bold uppercase tracking-widest bg-slate-800 text-white">
                            Total General</th>
                    </tr>

                    <tr
                        class="bg-slate-50/50 text-[9px] font-bold text-slate-400 uppercase tracking-tighter border-b border-slate-200 text-center">
                        @for ($i = 0; $i < $numBloques - 1; $i++)
                            <th class="px-2 py-2 border-r border-slate-200">Stock</th>
                            <th class="px-2 py-2 border-r border-slate-200">Pedido</th>
                        @endfor
                        <th class="px-2 py-2 bg-slate-700 text-slate-300">Stock</th>
                        <th class="px-2 py-2 bg-slate-700 text-slate-300">Pedido</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
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
                                'barras' => [],
                                'barras_total' => 0,
                                'total' => 0,
                            ];
                        @endphp

                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td
                                class="px-3 py-2.5 font-extrabold text-slate-700 border-r border-slate-200 bg-slate-50/30">
                                Ø{{ $diametro }}</td>

                            @if ($incluirEncarretado)
                                @php
                                    $stockVal = $stock['encarretado'] ?? 0;
                                    $pedidoVal = $pedido['encarretado'] ?? 0;
                                    $claseRojo = $rojo($diametro, 'encarretado', null);
                                @endphp
                                <td
                                    class="px-2 py-2 border-r border-slate-100 {{ $claseRojo }} font-medium text-slate-600">
                                    {{ $claseRojo ? '—' : number_format($stockVal, 0, ',', '.') }}
                                </td>
                                <td class="px-2 py-2 border-r border-slate-200 {{ $claseRojo }}">
                                    @if (!$claseRojo)
                                        @php
                                            $cantidadReponer = round(
                                                max(0, ($necesario['encarretado'] ?? 0) - $stockVal),
                                                0,
                                            );
                                            // Fallback: si la sugerencia es 0 pero hay un pedido en curso, usamos ese valor como base sugerida
                                            if ($cantidadReponer <= 0 && $pedidoVal > 0) {
                                                $cantidadReponer = $pedidoVal;
                                            }
                                        @endphp
                                        <div class="flex items-center justify-center gap-2">
                                            <span
                                                class="font-bold text-blue-600">{{ number_format($pedidoVal, 0, ',', '.') }}</span>

                                            <button type="button"
                                                @click="addToCart({
                                                    id: 'encarretado-{{ $diametro }}',
                                                    tipo: 'encarretado',
                                                    diametro: '{{ $diametro }}',
                                                    nombre: 'Encarretado Ø{{ $diametro }}',
                                                    cantidad: {{ $cantidadReponer }},
                                                    producto_base_id: '{{ $productoBaseInfo['encarretado'][$diametro]['id'] ?? '' }}'
                                                })"
                                                :class="cart.find(i => i.id === 'encarretado-{{ $diametro }}') ?
                                                    'bg-blue-600 text-white shadow-blue-200' :
                                                    'bg-slate-100 text-slate-400 hover:bg-blue-50 hover:text-blue-600'"
                                                class="p-1.5 rounded-lg transition-all transform active:scale-90"
                                                title="Añadir al pedido">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2.5" d="M12 4v16m8-8H4" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            @endif

                            @foreach ($longitudesBarras as $longitud)
                                @php
                                    $stockVal = $stock['barras'][$longitud] ?? 0;
                                    $pedidoVal = $pedido['barras'][$longitud] ?? 0;
                                    $claseRojo = $rojo($diametro, 'barra', $longitud);
                                @endphp
                                <td
                                    class="px-2 py-2 border-r border-slate-100 {{ $claseRojo }} font-medium text-slate-600">
                                    {{ $claseRojo ? '—' : number_format($stockVal, 0, ',', '.') }}
                                </td>
                                <td class="px-2 py-2 border-r border-slate-200 {{ $claseRojo }}">
                                    @if (!$claseRojo)
                                        @php
                                            $cantidadReponer = round($necesario['barras'][$longitud] ?? 0, 0);
                                            if ($cantidadReponer <= 0 && $pedidoVal > 0) {
                                                $cantidadReponer = $pedidoVal;
                                            }
                                        @endphp
                                        <div class="flex items-center justify-center gap-2">
                                            <span
                                                class="font-bold text-blue-600">{{ number_format($pedidoVal, 0, ',', '.') }}</span>

                                            <button type="button"
                                                @click="addToCart({
                                                    id: 'barra-{{ $diametro }}-{{ $longitud }}',
                                                    tipo: 'barra',
                                                    diametro: '{{ $diametro }}',
                                                    longitud: '{{ $longitud }}',
                                                    nombre: 'Barra Ø{{ $diametro }} / {{ $longitud }}m',
                                                    cantidad: {{ $cantidadReponer }},
                                                    producto_base_id: '{{ $productoBaseInfo['barras'][$diametro][$longitud]['id'] ?? '' }}'
                                                })"
                                                :class="cart.find(i => i
                                                        .id === 'barra-{{ $diametro }}-{{ $longitud }}') ?
                                                    'bg-blue-600 text-white shadow-blue-200' :
                                                    'bg-slate-100 text-slate-400 hover:bg-blue-50 hover:text-blue-600'"
                                                class="p-1.5 rounded-lg transition-all transform active:scale-90"
                                                title="Añadir al pedido">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2.5" d="M12 4v16m8-8H4" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            @endforeach

                            <td class="px-2 py-2 font-bold text-slate-700 border-r border-slate-100 bg-slate-50/50">
                                {{ number_format($stock['barras_total'], 0, ',', '.') }}</td>
                            <td class="px-2 py-2 font-bold text-blue-700 border-r border-slate-200 bg-slate-50/50">
                                {{ number_format($pedido['barras_total'], 0, ',', '.') }}</td>

                            <td class="px-2 py-2 font-black text-slate-800 border-r border-slate-100 bg-slate-800/5">
                                {{ number_format($stock['total'], 0, ',', '.') }}</td>
                            <td class="px-2 py-2 font-black text-blue-800 bg-slate-800/5">
                                {{ number_format($pedido['total'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div
        class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-8 bg-slate-900 rounded-[2.5rem] shadow-2xl shadow-blue-900/10 border border-slate-800 relative overflow-hidden">
        <div class="absolute -top-24 -left-24 w-64 h-64 bg-blue-600/10 rounded-full blur-[80px]"></div>

        <div class="flex items-center gap-5 relative z-10">
            <div class="p-4 bg-slate-800 rounded-2xl border border-slate-700">
                <span class="text-3xl">🏗️</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Stock Disponible en
                    Planta</p>
                <div class="flex items-baseline gap-2">
                    <p class="text-3xl font-black text-white tracking-tight">
                        {{ number_format($totalGeneral, 2, ',', '.') }}</p>
                    <span class="text-sm font-bold text-slate-500">kg</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4 relative z-10" x-show="cart.length > 0">
            <p class="text-xs font-bold text-slate-400 italic">Analiza necesidades y configura tu pedido en el panel
                lateral →</p>
        </div>
    </div>

    {{-- Consumo Histórico --}}
    @if (!empty($resumenReposicion))
        <div>
            <div class="flex items-center gap-3 mb-6">
                <span class="p-2 bg-indigo-100 text-indigo-600 rounded-xl">📈</span>
                <h2 class="text-xl font-bold text-slate-800 tracking-tight">Análisis de Consumo Histórico</h2>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-center border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                                <th class="px-4 py-4 border-r border-slate-200">Producto</th>
                                <th class="px-4 py-4 border-r border-slate-200">Total Origen</th>
                                <th class="px-4 py-4 border-r border-slate-200">Media Mensual</th>
                                <th class="px-4 py-4 border-r border-slate-200">{{ $nombreMeses['haceDosMeses'] }}</th>
                                <th class="px-4 py-4 border-r border-slate-200">{{ $nombreMeses['mesAnterior'] }}</th>
                                <th class="px-4 py-4 border-r border-slate-200">{{ $nombreMeses['mesActual'] }}</th>
                                <th class="px-4 py-4 border-r border-slate-200 bg-slate-100/50">Stock Actual</th>
                                <th class="px-4 py-4 border-r border-slate-200">Cobertura</th>
                                <th class="px-4 py-4">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 italic font-medium text-slate-500">
                            @foreach ($resumenReposicion as $id => $item)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-4 py-3 border-r border-slate-100 bg-slate-50/30">
                                        <div class="flex flex-col items-center">
                                            <span
                                                class="text-sm font-extrabold text-slate-800">{{ ucfirst($item['tipo']) }}
                                                Ø{{ $item['diametro'] }}</span>
                                            @if ($item['longitud'])
                                                <span
                                                    class="text-[10px] font-bold text-slate-400 capitalize">{{ $item['longitud'] }}m</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 border-r border-slate-100">
                                        {{ number_format($consumoOrigen[$id]['total_origen'] ?? 0, 0, ',', '.') }}
                                        <span class="text-[9px]">kg</span>
                                    </td>
                                    <td class="px-4 py-3 border-r border-slate-100">
                                        {{ number_format($consumoOrigen[$id]['media_mensual'] ?? 0, 0, ',', '.') }}
                                        <span class="text-[9px]">kg/mes</span>
                                    </td>
                                    <td class="px-4 py-3 border-r border-slate-100">
                                        {{ number_format($consumosPorMes['mes_hace_dos'][$id] ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 border-r border-slate-100">
                                        {{ number_format($consumosPorMes['mes_anterior'][$id] ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 border-r border-slate-100 font-bold text-slate-700">
                                        {{ number_format($consumosPorMes['mes_actual'][$id] ?? 0, 0, ',', '.') }}</td>
                                    <td
                                        class="px-4 py-3 border-r border-slate-100 font-black text-slate-800 bg-slate-100/30">
                                        {{ number_format($item['stock'], 0, ',', '.') }} <span
                                            class="text-[9px]">kg</span></td>
                                    <td class="px-4 py-3 border-r border-slate-100">
                                        <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-[10px] font-bold">
                                            {{ ($item['consumo_ant'] ?? 0) > 0 ? round($item['stock'] / ($item['consumo_ant'] / 30), 0) : '∞' }}
                                            días
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @php $dif = $item['stock'] + $item['pedido'] - ($item['consumo_ant'] ?? 0); @endphp
                                        <span
                                            class="text-sm font-bold {{ $dif < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                            {{ number_format($dif, 0, ',', '.') }} <span class="text-[9px]">kg</span>
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Recomendación de Reposición --}}
    @if (!empty($recomendacionReposicion))
        <div class="grid lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <div class="flex items-center gap-3 mb-6">
                    <span class="p-2 bg-amber-100 text-amber-600 rounded-xl">📦</span>
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Reposición Recomendada</h2>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                    <table class="w-full text-sm text-center border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                <th class="px-4 py-4 text-left pl-8">Producto</th>
                                <th class="px-4 py-4">Tendencia</th>
                                <th class="px-4 py-4">Stock Actual</th>
                                <th class="px-4 py-4">A Pedir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($recomendacionReposicion as $rec)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-4 py-4 text-left pl-8">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="w-2 h-2 rounded-full {{ $rec['reponer'] > 0 ? 'bg-amber-400' : 'bg-emerald-400' }}"></span>
                                            <span
                                                class="text-sm font-extrabold text-slate-800">{{ ucfirst($rec['tipo']) }}
                                                Ø{{ $rec['diametro'] }}
                                                {{ $rec['longitud'] ? $rec['longitud'] . 'm' : '' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 font-bold text-slate-500">
                                        {{ number_format($rec['tendencia'], 0, ',', '.') }} <span
                                            class="text-[9px]">kg/mes</span></td>
                                    <td class="px-4 py-4 font-bold text-slate-500">
                                        {{ number_format($rec['stock_actual'], 0, ',', '.') }} <span
                                            class="text-[9px]">kg</span></td>
                                    <td class="px-4 py-4">
                                        <span
                                            class="px-3 py-1 rounded-xl font-black {{ $rec['reponer'] > 0 ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-emerald-50 text-emerald-600 border border-emerald-100' }}">
                                            {{ number_format($rec['reponer'], 0, ',', '.') }} kg
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-col justify-end">
                <div class="bg-blue-600 rounded-3xl p-8 text-white shadow-xl shadow-blue-200">
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                        <span>💡</span> Inteligencia de Stock
                    </h3>
                    <div class="space-y-4 text-blue-100 text-sm leading-relaxed">
                        <p>Calculamos la reposición analizando los consumos de los últimos 3 meses con un
                            <strong>promedio ponderado</strong> (50% mes actual, 30% anterior, 20% hace dos).
                        </p>
                        <hr class="border-blue-500">
                        <p>El <strong>Stock Objetivo</strong> se define como 2 meses de consumo proyectado para
                            garantizar la operatividad sin roturas.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
