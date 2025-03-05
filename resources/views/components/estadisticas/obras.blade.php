<div class="bg-white shadow-lg rounded-lg mb-6">
    <div class="bg-green-600 text-white text-center p-4 rounded-t-lg">
        <h3 class="text-lg font-semibold">Peso Entregado a Cada Obra</h3>
    </div>
    <div class="p-4">
        <table class="w-full border border-gray-300 rounded-lg">
            <thead class="bg-green-500 text-white">
                <tr>
                    <th class="px-4 py-3 border text-center">Obra</th>
                    <th class="px-4 py-3 border text-center">Peso Entregado (kg)</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                @foreach ($pesoEntregadoPorObra as $entrega)
                    <tr>
                        <td class="px-4 py-3 text-center border">{{ $entrega['nom_obra'] }}</td>
                        <td class="px-4 py-3 text-center border">{{ number_format($entrega['peso_entregado'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
