@props([
    'stockData' => [],
    'pedidosPorDiametro' => [],
    'necesarioPorDiametro' => [],
    'totalGeneral' => 0,
    'consumoMensualPromedio' => [],
])

@php
    function rojo($diametro, $tipo, $longitud = null)
    {
        if ($tipo === 'barra' && $diametro == 10 && $longitud == 12) {
            return '';
        }
        if ($tipo === 'encarretado' && in_array($diametro, [25, 32])) {
            return 'bg-red-200';
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

<div class="overflow-x-auto rounded-lg">
    <table
        class="w-full text-sm border-collapse text-center mt-6 rounded-lg shadow border border-gray-300 overflow-hidden">
        <thead>
            <tr class="bg-gray-800 text-white">
                <th rowspan="2" class="border px-2 py-1">Ø mm</th>
                <th colspan="3" class="border px-2 py-1">Encarretado</th>
                <th colspan="3" class="border px-2 py-1">Barras 12 m</th>
                <th colspan="3" class="border px-2 py-1">Barras 14 m</th>
                <th colspan="3" class="border px-2 py-1">Barras 15 m</th>
                <th colspan="3" class="border px-2 py-1">Barras 16 m</th>
                <th colspan="3" class="border px-2 py-1">Barras Total</th>
                <th colspan="3" class="border px-2 py-1">Total</th>
            </tr>
            <tr class="bg-gray-700 text-white">
                @for ($i = 0; $i < 7; $i++)
                    <th class="border px-2 py-1">Stock</th>
                    <th class="border px-2 py-1">Pedido</th>
                    <th class="border px-2 py-1">Necesario</th>
                @endfor
            </tr>
        </thead>
        <tbody>
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
                <tr class="bg-white">
                    <td class="border px-2 py-1 font-bold">{{ $diametro }}</td>

                    {{-- Encarretado --}}
                    @php
                        $claseRojo = rojo($diametro, 'encarretado');
                        $stockVal = $stock['encarretado'];
                        $pedidoVal = $pedido['encarretado'];
                        $necesarioVal = $necesario['encarretado'];
                        $colorTexto = $necesarioVal > $stockVal ? 'text-red-600' : 'text-green-600';
                    @endphp

                    <td class="border px-2 py-1 {{ $claseRojo }}">
                        @if (!$claseRojo)
                            {{ number_format($stockVal, 2, ',', '.') }}
                        @endif
                    </td>

                    <td class="border px-2 py-1 {{ $claseRojo }}">
                        @if (!$claseRojo)
                            {{ number_format($pedidoVal, 2, ',', '.') }}
                        @endif
                    </td>
                    <td class="border px-2 py-1 {{ $claseRojo }}">
                        @if (!$claseRojo)
                            <div class="flex items-center justify-start gap-1">
                                <input type="checkbox" name="seleccionados[]" value="encarretado-{{ $diametro }}">
                                <input type="hidden" name="detalles[encarretado-{{ $diametro }}][tipo]"
                                    value="encarretado">
                                <input type="hidden" name="detalles[encarretado-{{ $diametro }}][diametro]"
                                    value="{{ $diametro }}">
                                <input type="hidden" name="detalles[encarretado-{{ $diametro }}][cantidad]"
                                    value="{{ round(max(0, $necesarioVal - $stockVal), 2) }}">
                                <span
                                    class="{{ $colorTexto }}">{{ number_format($necesarioVal, 2, ',', '.') }}</span>
                            </div>
                        @endif
                    </td>


                    {{-- Barras por longitud --}}
                    @foreach ([12, 14, 15, 16] as $longitud)
                        @php
                            $claseRojo = rojo($diametro, 'barra', $longitud);
                            $stockVal = $stock['barras'][$longitud] ?? 0;
                            $pedidoVal = $pedido['barras'][$longitud] ?? 0;
                            $necesarioVal = $necesario['barras'][$longitud] ?? 0;
                            $colorTexto = $necesarioVal > $stockVal ? 'text-red-600' : 'text-green-600';
                        @endphp

                        <td class="border px-2 py-1 {{ $claseRojo }}">
                            @if (!$claseRojo)
                                {{ number_format($stockVal, 2, ',', '.') }}
                            @endif
                        </td>

                        <td class="border px-2 py-1 {{ $claseRojo }}">
                            @if (!$claseRojo)
                                {{ number_format($pedidoVal, 2, ',', '.') }}
                            @endif
                        </td>
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
                                        class="{{ $colorTexto }}">{{ number_format($necesarioVal, 2, ',', '.') }}</span>
                                </div>
                            @endif
                        </td>
                    @endforeach

                    <td class="border px-2 py-1 font-semibold">
                        {{ number_format($stock['barras_total'], 2, ',', '.') }}
                    </td>
                    <td class="border px-2 py-1 font-semibold">
                        {{ number_format($pedido['barras_total'], 2, ',', '.') }}
                    </td>
                    <td class="border px-2 py-1 font-semibold">
                        {{ number_format($necesario['barras_total'], 2, ',', '.') }}
                    </td>

                    <td class="border px-2 py-1 font-bold">
                        {{ number_format($stock['total'], 2, ',', '.') }}
                    </td>
                    <td class="border px-2 py-1 font-bold">
                        {{ number_format($pedido['total'], 2, ',', '.') }}
                    </td>
                    <td class="border px-2 py-1 font-bold">
                        {{ number_format($necesario['total'], 2, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4 flex justify-end">
        <span class="bg-gray-100 border border-gray-300 rounded px-4 py-2 font-bold">
            Total general disponible (encarretado + barras): {{ number_format($totalGeneral, 2, ',', '.') }} kg
        </span>
    </div>

    @if (!empty($consumoPorProductoBase))
        <div class="bg-white shadow-lg rounded-lg p-6 mt-6 border border-gray-200">
            <h2 class="text-xl font-bold text-gray-800 mb-4">📊 Consumo por Producto Base</h2>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-center border border-gray-300 rounded table-auto">
                    <thead class="bg-gray-100 font-semibold text-gray-700">
                        <tr>
                            <th class="px-4 py-3 border">Tipo</th>
                            <th class="px-4 py-3 border">Diámetro</th>
                            <th class="px-4 py-3 border">Longitud</th>
                            <th class="px-4 py-3 border">Últimas 2 semanas</th>
                            <th class="px-4 py-3 border">Último mes</th>
                            <th class="px-4 py-3 border">Últimos 2 meses</th>
                            <th class="px-4 py-3 border">Stock actual</th>
                            <th class="px-4 py-3 border">Kg pedidos (pendiente)</th>
                            <th class="px-4 py-3 border">Diferencia</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @php
                            $ids = collect($consumoPorProductoBase['ultimas_2_semanas'])
                                ->keys()
                                ->merge($consumoPorProductoBase['ultimo_mes']->keys())
                                ->merge($consumoPorProductoBase['ultimos_2_meses']->keys())
                                ->unique()
                                ->sort();
                        @endphp

                        @foreach ($ids as $productoBaseId)
                            @php
                                $info = $productoBaseInfo[$productoBaseId] ?? null;
                                $stock = $stockPorProductoBase[$productoBaseId] ?? 0;
                                $consumoMes = $consumoPorProductoBase['ultimo_mes'][$productoBaseId] ?? 0;
                                $diferencia = $stock - $consumoMes;
                            @endphp
                            @if ($info)
                                <tr class="hover:bg-blue-50 transition-colors">
                                    <td class="px-4 py-2 border">{{ ucfirst($info['tipo']) }}</td>
                                    <td class="px-4 py-2 border">{{ $info['diametro'] }}</td>
                                    <td class="px-4 py-2 border">
                                        {{ $info['tipo'] === 'barra' ? $info['longitud'] . ' m' : '—' }}
                                    </td>
                                    <td class="px-4 py-2 border text-right">
                                        {{ number_format($consumoPorProductoBase['ultimas_2_semanas'][$productoBaseId] ?? 0, 2) }}
                                        kg
                                    </td>
                                    <td class="px-4 py-2 border text-right">
                                        {{ number_format($consumoMes, 2) }} kg
                                    </td>
                                    <td class="px-4 py-2 border text-right">
                                        {{ number_format($consumoPorProductoBase['ultimos_2_meses'][$productoBaseId] ?? 0, 2) }}
                                        kg
                                    </td>
                                    <td class="px-4 py-2 border text-right font-semibold text-blue-700">
                                        {{ number_format($stock, 2) }} kg
                                    </td>
                                    @php
                                        $kgPedidos = $kgPedidosPorProductoBase[$productoBaseId] ?? 0;
                                    @endphp

                                    <td class="px-4 py-2 border text-right">
                                        {{ number_format($kgPedidos, 2) }} kg
                                    </td>

                                    <td
                                        class="px-4 py-2 border text-right font-semibold {{ $diferencia < 0 ? 'text-red-600' : 'text-green-700' }}">
                                        {{ number_format($diferencia, 2) }} kg
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    @if (!empty($resumenReposicion))
        <div class="bg-white shadow-lg rounded-lg p-6 mt-10 border border-gray-200">
            <h2 class="text-xl font-bold text-gray-800 mb-4">📦 Resumen de Reposición Sugerida</h2>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-center border border-gray-300 table-auto">
                    <thead class="bg-gray-100 text-gray-700 font-semibold">
                        <tr>
                            <th class="px-4 py-3 border">Tipo</th>
                            <th class="px-4 py-3 border">Diámetro</th>
                            <th class="px-4 py-3 border">Longitud</th>
                            <th class="px-4 py-3 border">Consumo 14d</th>
                            <th class="px-4 py-3 border">Consumo 30d</th>
                            <th class="px-4 py-3 border">Consumo 60d</th>
                            <th class="px-4 py-3 border">Stock actual</th>
                            <th class="px-4 py-3 border">Kg pedidos</th>
                            <th class="px-4 py-3 border">Reposición sugerida</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($resumenReposicion as $item)
                            @if ($item['reposicion'] > 0)
                                <tr class="hover:bg-yellow-50">
                                    <td class="px-4 py-2 border">{{ ucfirst($item['tipo']) }}</td>
                                    <td class="px-4 py-2 border">{{ $item['diametro'] }}</td>
                                    <td class="px-4 py-2 border">
                                        {{ $item['tipo'] === 'barra' ? $item['longitud'] . ' m' : '—' }}
                                    </td>
                                    <td class="px-4 py-2 border text-right">
                                        {{ number_format($item['consumo_14d'], 2) }} kg</td>
                                    <td class="px-4 py-2 border text-right">
                                        {{ number_format($item['consumo_30d'], 2) }} kg</td>
                                    <td class="px-4 py-2 border text-right">
                                        {{ number_format($item['consumo_60d'], 2) }} kg</td>
                                    <td class="px-4 py-2 border text-right text-blue-700 font-semibold">
                                        {{ number_format($item['stock'], 2) }} kg</td>
                                    <td class="px-4 py-2 border text-right text-indigo-600 font-semibold">
                                        {{ number_format($item['pedido'], 2) }} kg</td>
                                    <td class="px-4 py-2 border text-right text-red-600 font-bold">
                                        ⚠ {{ number_format($item['reposicion'], 2) }} kg
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif



</div>
