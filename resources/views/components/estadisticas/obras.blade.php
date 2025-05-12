<div class="bg-white shadow rounded mb-4 text-sm">
    <div class="bg-green-600 text-white text-center py-1 rounded-t">
        <h3 class="font-semibold text-base">Peso Entregado a Cada Obra</h3>
    </div>
    <div class="px-3 py-2">
        <table class="w-full border border-gray-300 text-xs">
            <thead class="bg-green-500 text-white">
                <tr>
                    <th class="px-2 py-1 border text-center">Obra</th>
                    <th class="px-2 py-1 border text-center">Peso Entregado (kg)</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                @forelse ($pesoPorObra as $nomObra => $pesoTotal)
                    <tr class="border-b hover:bg-gray-100">
                        <td class="px-2 py-1 text-center">{{ $nomObra }}</td>
                        <td class="px-2 py-1 text-center">{{ number_format($pesoTotal, 2, ',', '.') }} kg</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-red-600 px-2 py-1 text-center">
                            No hay datos disponibles
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
