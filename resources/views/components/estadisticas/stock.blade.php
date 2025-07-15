<div class="space-y-12">

    @php
        // 🎨 Función para marcar celdas en rojo según condiciones de diámetro y tipo
        function rojo($diametro, $tipo, $longitud = null)
        {
            if ($tipo === 'barra' && $diametro == 10 && $longitud == 12) {
                return '';
            }
            if ($tipo === 'encarretado' && in_array($diametro, [25, 32])) {
                return 'bg-red-100';
            }
            if ($tipo === 'barra') {
                if (in_array($diametro, [8, 10])) {
                    return 'bg-red-100';
                }
                if ($diametro == 12 && in_array($longitud, [15, 16])) {
                    return 'bg-red-100';
                }
            }
            return '';
        }
    @endphp

    {{-- 📦 TABLA PRINCIPAL --}}
    <h2 class="text-2xl font-bold text-blue-900 mt-4">📦 Estado actual de stock, pedidos y necesidades</h2>
    <p class="text-sm text-blue-700 mb-4">
        Stock disponible, pedidos pendientes y material necesario para cubrir producción.
    </p>

    <div class="overflow-x-auto bg-white shadow-lg rounded-lg border border-blue-200">
        <table class="w-full text-sm text-center border-collapse">
            <thead>
                <tr class="bg-blue-900 text-white text-xs">
                    <th rowspan="2" class="border px-2 py-2">Ø mm<br><span
                            class="text-blue-200 text-[10px]">Diámetro</span></th>
                    <th colspan="3" class="border px-2 py-2">Encarretado<br><span
                            class="text-blue-200 text-[10px]">Bobinas</span></th>
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
                    <tr class="hover:bg-blue-50 transition">
                        <td class="border px-2 py-1 font-bold text-blue-900">{{ $diametro }}</td>

                        @php
                            $claseRojo = rojo($diametro, 'encarretado');
                            $stockVal = $stock['encarretado'];
                            $pedidoVal = $pedido['encarretado'];
                            $necesarioVal = $necesario['encarretado'];
                            $colorTexto =
                                $necesarioVal > $stockVal
                                    ? 'text-red-600 font-semibold'
                                    : 'text-blue-700 font-semibold';
                        @endphp
                        <td class="border px-2 py-1 {{ $claseRojo }}">
                            {{ !$claseRojo ? number_format($stockVal, 2, ',', '.') : '' }}</td>
                        <td class="border px-2 py-1 {{ $claseRojo }}">
                            {{ !$claseRojo ? number_format($pedidoVal, 2, ',', '.') : '' }}</td>
                        <td class="border px-2 py-1 {{ $claseRojo }}">
                            @if (!$claseRojo)
                                <span class="{{ $colorTexto }}">{{ number_format($necesarioVal, 2, ',', '.') }}</span>
                            @endif
                        </td>

                        @foreach ([12, 14, 15, 16] as $longitud)
                            @php
                                $claseRojo = rojo($diametro, 'barra', $longitud);
                                $stockVal = $stock['barras'][$longitud] ?? 0;
                                $pedidoVal = $pedido['barras'][$longitud] ?? 0;
                                $necesarioVal = $necesario['barras'][$longitud] ?? 0;
                                $colorTexto =
                                    $necesarioVal > $stockVal
                                        ? 'text-red-600 font-semibold'
                                        : 'text-blue-700 font-semibold';
                            @endphp
                            <td class="border px-2 py-1 {{ $claseRojo }}">
                                {{ !$claseRojo ? number_format($stockVal, 2, ',', '.') : '' }}</td>
                            <td class="border px-2 py-1 {{ $claseRojo }}">
                                {{ !$claseRojo ? number_format($pedidoVal, 2, ',', '.') : '' }}</td>
                            <td class="border px-2 py-1 {{ $claseRojo }}">
                                @if (!$claseRojo)
                                    <span
                                        class="{{ $colorTexto }}">{{ number_format($necesarioVal, 2, ',', '.') }}</span>
                                @endif
                            </td>
                        @endforeach

                        <td class="border px-2 py-1 font-semibold text-blue-900">
                            {{ number_format($stock['barras_total'], 2, ',', '.') }}</td>
                        <td class="border px-2 py-1 font-semibold text-blue-900">
                            {{ number_format($pedido['barras_total'], 2, ',', '.') }}</td>
                        <td class="border px-2 py-1 font-semibold text-blue-900">
                            {{ number_format($necesario['barras_total'], 2, ',', '.') }}</td>
                        <td class="border px-2 py-1 font-bold bg-blue-50">
                            {{ number_format($stock['total'], 2, ',', '.') }}</td>
                        <td class="border px-2 py-1 font-bold bg-blue-50">
                            {{ number_format($pedido['total'], 2, ',', '.') }}</td>
                        <td class="border px-2 py-1 font-bold bg-blue-50">
                            {{ number_format($necesario['total'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-end">
        <span class="bg-blue-900 text-white rounded px-4 py-2 font-bold shadow">
            📌 Total general disponible: {{ number_format($totalGeneral, 2, ',', '.') }} kg
        </span>
    </div>

    {{-- 📊 CONSUMO HISTÓRICO --}}
    @if (!empty($consumoPorProductoBase))
        <h2 class="text-2xl font-bold text-blue-900 mt-4">📊 Consumo histórico por producto base</h2>
        <p class="text-sm text-blue-700 mb-4">Incluye consumos reales, stock actual y pedidos pendientes.</p>
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
                            Últimas 2 semanas<br>
                            <span class="text-blue-200 text-[9px]">Consumo en 14 días</span>
                        </th>
                        <th class="px-4 py-2 border">
                            Último mes<br>
                            <span class="text-blue-200 text-[9px]">Consumo en 30 días</span>
                        </th>
                        <th class="px-4 py-2 border">
                            Stock actual<br>
                            <span class="text-blue-200 text-[9px]">Disponible en almacén</span>
                        </th>
                        <th class="px-4 py-2 border">
                            Cobertura (días)<br>
                            <span class="text-blue-200 text-[10px]">Stock actual ÷ consumo diario</span>
                        </th>
                        <th class="px-4 py-2 border">
                            Stock seguridad<br>
                            <span class="text-blue-200 text-[10px]">
                                Consumo diario promedio × días colchón (ej. 5)
                            </span>
                        </th>
                        <th class="px-4 py-2 border">
                            Kg pedidos<br>
                            <span class="text-blue-200 text-[9px]">Pendiente de llegar</span>
                        </th>
                        <th class="px-4 py-2 border">
                            Diferencia<br>
                            <span class="text-blue-200 text-[9px]">Stock - consumo</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white">
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
                            if (!$info) {
                                continue;
                            }

                            $stock = $stockPorProductoBase[$productoBaseId] ?? 0;
                            $consumoMes = $consumoPorProductoBase['ultimo_mes'][$productoBaseId] ?? 0;
                            $consumoDiario = $consumoMes / 30;
                            $diasCobertura = $consumoDiario > 0 ? round($stock / $consumoDiario, 1) : '∞';

                            // define tu stock de seguridad (ejemplo: 5 días de consumo)
                            $stockSeguridad = round($consumoDiario * 5, 2);

                            $kgPedidos = $kgPedidosPorProductoBase[$productoBaseId] ?? 0;
                            $diferencia = $stock - $consumoMes;

                            $alertaCobertura =
                                is_numeric($diasCobertura) && $diasCobertura < 5 ? 'text-red-600 font-bold' : '';
                            $alertaStockSeguridad = $stock < $stockSeguridad ? 'bg-red-100' : '';
                        @endphp
                        <tr class="hover:bg-blue-50 transition">
                            <td class="px-4 py-2 border">{{ ucfirst($info['tipo']) }}</td>
                            <td class="px-4 py-2 border">{{ $info['diametro'] }}</td>
                            <td class="px-4 py-2 border">
                                {{ $info['tipo'] === 'barra' ? $info['longitud'] . ' m' : '—' }}
                            </td>
                            <td class="px-4 py-2 border">
                                {{ number_format($consumoPorProductoBase['ultimas_2_semanas'][$productoBaseId] ?? 0, 2, ',', '.') }}
                                kg</td>
                            <td class="px-4 py-2 border">{{ number_format($consumoMes, 2, ',', '.') }} kg</td>
                            <td class="px-4 py-2 border font-semibold">{{ number_format($stock, 2, ',', '.') }} kg</td>
                            <td class="px-4 py-2 border {{ $alertaCobertura }}">{{ $diasCobertura }} días</td>
                            <td class="px-4 py-2 border {{ $alertaStockSeguridad }}">
                                {{ number_format($stockSeguridad, 2, ',', '.') }} kg</td>
                            <td class="px-4 py-2 border">{{ number_format($kgPedidos, 2, ',', '.') }} kg</td>
                            <td class="px-4 py-2 border {{ $diferencia < 0 ? 'text-red-600' : 'text-green-700' }}">
                                {{ number_format($diferencia, 2, ',', '.') }} kg</td>
                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>
    @endif

    {{-- 📦 REPOSICIÓN SUGERIDA --}}
    @if (!empty($resumenReposicion))
        <h2 class="text-2xl font-bold text-blue-900 mt-4">📦 Reposición sugerida</h2>
        <p class="text-sm text-blue-700 mb-4">Basada en el consumo de 30 días, el stock actual y pedidos en curso.</p>
        <div class="overflow-x-auto bg-white shadow-lg rounded-lg border border-blue-200">
            <table class="min-w-full text-sm text-center border-collapse">
                <thead class="bg-blue-900 text-white text-xs">
                    <tr>
                        <th class="px-4 py-2 border">Tipo<br><span class="text-blue-200 text-[9px]">Barra o
                                encarrete</span></th>
                        <th class="px-4 py-2 border">Diámetro<br><span class="text-blue-200 text-[9px]">Ø mm</span></th>
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
                                <td class="px-4 py-2 border text-right">
                                    {{ number_format($item['consumo_14d'], 2, ',', '.') }} kg</td>
                                <td class="px-4 py-2 border text-right">
                                    {{ number_format($item['consumo_30d'], 2, ',', '.') }} kg</td>
                                <td class="px-4 py-2 border text-right">
                                    {{ number_format($item['consumo_60d'], 2, ',', '.') }} kg</td>
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

    {{-- 📐 COMPARATIVA OPCIONAL --}}
    @if (!empty($comparativa))
        <h2 class="text-2xl font-bold text-blue-900 mt-4">📐 Comparativa rápida</h2>
        <p class="text-sm text-blue-700 mb-4">Disponible + pedido - necesario.</p>
        <div class="overflow-x-auto bg-white shadow-lg rounded-lg border border-blue-200">
            <table class="min-w-full text-sm text-center border-collapse">
                <thead class="bg-blue-900 text-white text-xs">
                    <tr>
                        <th class="px-4 py-2 border">Tipo</th>
                        <th class="px-4 py-2 border">Ø mm</th>
                        <th class="px-4 py-2 border">Pendiente</th>
                        <th class="px-4 py-2 border">Pedido</th>
                        <th class="px-4 py-2 border">Disponible</th>
                        <th class="px-4 py-2 border">Diferencia</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @foreach ($comparativa as $item)
                        <tr class="hover:bg-blue-50 transition">
                            <td class="px-4 py-2 border">{{ ucfirst($item['tipo']) }}</td>
                            <td class="px-4 py-2 border">{{ $item['diametro'] }}</td>
                            <td class="px-4 py-2 border">{{ number_format($item['pendiente'], 2, ',', '.') }} kg</td>
                            <td class="px-4 py-2 border">{{ number_format($item['pedido'], 2, ',', '.') }} kg</td>
                            <td class="px-4 py-2 border">{{ number_format($item['disponible'], 2, ',', '.') }} kg</td>
                            <td
                                class="px-4 py-2 border font-bold {{ $item['diferencia'] < 0 ? 'text-red-600' : 'text-green-700' }}">
                                {{ number_format($item['diferencia'], 2, ',', '.') }} kg
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
