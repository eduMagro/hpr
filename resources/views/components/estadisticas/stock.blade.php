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

                            @php
                                $claveConsumo = "encarretado-$diametro-0";
                                $consumoMensual = $consumoMensualPromedio[$claveConsumo] ?? 0;
                                $stockDeseado = round($consumoMensual * 1.5, 1);
                            @endphp

                            <div class="text-xs text-gray-500 mt-1 leading-3">
                                <div>⏳ {{ number_format($consumoMensual, 1, ',', '.') }} kg/mes</div>
                                <div>🎯 {{ number_format($stockDeseado, 1, ',', '.') }} deseado</div>
                            </div>
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

                                @php
                                    $claveConsumo = "barra-$diametro-$longitud";
                                    $consumoMensual = $consumoMensualPromedio[$claveConsumo] ?? 0;
                                    $stockDeseado = round($consumoMensual * 1.5, 1);
                                @endphp

                                <div class="text-xs text-gray-500 mt-1 leading-3">
                                    <div>⏳ {{ number_format($consumoMensual, 1, ',', '.') }} kg/mes</div>
                                    <div>🎯 {{ number_format($stockDeseado, 1, ',', '.') }} deseado</div>
                                </div>
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
                                        value="encarretado-{{ $diametro }}">
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
</div>
