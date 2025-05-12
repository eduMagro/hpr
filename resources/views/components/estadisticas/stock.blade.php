@php
    // Agrupamos $stockBarras ahora con base en producto_base (hay que asegurarse de que esté cargado)
    $longitudesUnicas = $stockBarras->pluck('productoBase.longitud')->unique()->sort();

    // Agrupar por diámetro (a través de productoBase) para combinar encarretado y barras
    $stockEncarretadoMap = $stockEncarretado->keyBy('diametro');

    // Agrupar las barras por diámetro (productoBase->diametro)
    $stockBarrasGroup = $stockBarras->groupBy(fn($item) => $item->productoBase->diametro);

    // Obtener todos los diámetros presentes, tanto en encarretado como en barras
    $diametros = $stockEncarretado
        ->pluck('diametro')
        ->merge($stockBarras->map(fn($p) => $p->productoBase->diametro))
        ->unique()
        ->sort();
@endphp

<div class="p-4">
    <div class="overflow-x-auto">
        <table class="w-full border border-gray-300 rounded-lg text-xs">
            <thead class="bg-blue-500 text-white">
                <tr>
                    <th class="px-4 py-3 border text-center">Diámetro (mm)</th>
                    <th class="px-4 py-3 border text-center">Encarretado (kg)</th>
                    @foreach ($longitudesUnicas as $longitud)
                        <th class="px-4 py-3 border text-center">Barras {{ number_format($longitud, 2) }} m (kg)</th>
                    @endforeach
                    <th class="px-4 py-3 border text-center">Barras Total (kg)</th>
                    <th class="px-4 py-3 border text-center">Total (kg)</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                @forelse($diametros as $diam)
                    @php
                        $encarretado = $stockEncarretadoMap->get($diam);
                        $stockEncarretadoVal = $encarretado ? $encarretado->stock : 0;

                        $barrasCollection = $stockBarrasGroup->get($diam) ?? collect();
                        $barrasTotal = 0;
                    @endphp
                    <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">
                        <td class="px-4 py-3 text-center border">{{ number_format($diam, 2) }}</td>
                        <td class="px-4 py-3 text-center border">{{ number_format($stockEncarretadoVal, 2) }}</td>

                        @foreach ($longitudesUnicas as $longitud)
                            @php
                                $stockThisLength = $barrasCollection
                                    ->filter(fn($item) => $item->productoBase->longitud == $longitud)
                                    ->sum('stock');

                                $barrasTotal += $stockThisLength;
                            @endphp
                            <td class="px-4 py-3 text-center border">{{ number_format($stockThisLength, 2) }}</td>
                        @endforeach

                        <td class="px-4 py-3 text-center border">{{ number_format($barrasTotal, 2) }}</td>
                        <td class="px-4 py-3 text-center border">
                            {{ number_format($stockEncarretadoVal + $barrasTotal, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 3 + count($longitudesUnicas) }}" class="text-red-600 px-4 py-3 text-center">
                            No hay datos disponibles
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
