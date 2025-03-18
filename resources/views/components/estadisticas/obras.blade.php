<div class="bg-white shadow-lg rounded-lg mb-6">
    <div class="bg-green-600 text-white text-center p-2 rounded-t-lg">
        <h3 class="text-lg font-semibold">Peso Entregado a Cada Obra</h3>
    </div>
    <div class="p-4">
        <table class="w-full border border-gray-300 rounded-lg">
            <thead class="bg-green-500 text-white">
                <tr>
                    <th class="px-1 py-1 border text-center">Obra</th>
                    <th class="px-1 py-1 border text-center">Peso Entregado (kg)</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                @foreach ($pesoPorObra as $nomObra => $pesoTotal)
                    <tr class="border-b hover:bg-gray-100">
                        <td class="px-2 py-2 text-center">{{ $nomObra }}</td>
                        <td class="px-2 py-2 text-center">{{ number_format($pesoTotal, 2, ',', '.') }} kg</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
