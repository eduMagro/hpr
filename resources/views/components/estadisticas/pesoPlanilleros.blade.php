<!-- Reporte de Pesos por Usuario (Planillero) -->
<div class="bg-white shadow-lg rounded-lg mb-6">
    <div class="bg-blue-600 text-white text-center p-4 rounded-t-lg">
        <h3 class="text-lg font-semibold">Reporte de Pesos por Usuario (Planillero)</h3>
    </div>

    <div class="p-4">
        <h4 class="text-center bg-blue-100 text-blue-900 font-semibold py-2 rounded-md">
            Peso Total Importado por Usuario
        </h4>
        <div class="overflow-x-auto">
            <table class="w-full border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr>
                        <th class="px-4 py-3 border text-center">Usuario ID</th>
                        <th class="px-4 py-3 border text-center">Peso Total Importado (kg)</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    @forelse ($pesoPorUsuario as $usuario)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">
                            <td class="px-4 py-3 text-center border">{{ $usuario->users_id }}</td>
                            <td class="px-4 py-3 text-center border">{{ number_format($usuario->peso_importado, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-red-600 px-4 py-3 text-center">
                                No hay datos disponibles
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-center text-gray-600 bg-gray-100 py-2 rounded-b-lg">
        Generado el {{ now()->format('d/m/Y H:i') }}
    </div>
</div>
