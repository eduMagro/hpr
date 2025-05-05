<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Confirmar pedido</h2>
    </x-slot>

    <div class="px-4 py-6 flex justify-center">
        <form action="{{ route('pedidos.store') }}" method="POST"
            class="w-full max-w-4xl bg-white shadow rounded p-6 space-y-6">
            @csrf

            <div>
                <label class="block font-medium mb-1">Proveedor:</label>
                <select name="proveedor_id" class="w-full border rounded px-3 py-2" required>
                    <option value="">Selecciona un proveedor</option>
                    @foreach ($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-medium mb-1">Fecha estimada de llegada:</label>
                <input type="date" name="fecha_estimada" class="w-full border rounded px-3 py-2" required>
            </div>

            <h3 class="font-semibold text-gray-700 mb-2">Productos a pedir:</h3>

            <div class="overflow-x-auto">
                <table class="min-w-[600px] border-collapse border text-center text-sm w-full">
                    <thead class="bg-blue-500 text-white">
                        <tr>
                            <th class="border px-2 py-1">Tipo</th>
                            <th class="border px-2 py-1">Diámetro</th>
                            <th class="border px-2 py-1">Peso a pedir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($filas as $fila)
                            <tr class="bg-gray-100">
                                <td class="border px-2 py-1">{{ ucfirst($fila['tipo']) }}</td>
                                <td class="border px-2 py-1">{{ $fila['diametro'] }} mm</td>
                                <td class="border px-2 py-1">
                                    <input type="number" step="1000" min="0"
                                        name="detalles[{{ $fila['clave'] }}][cantidad]" value="{{ $fila['cantidad'] }}"
                                        class="w-full text-center border border-gray-300 rounded px-2 py-1">
                                </td>

                            </tr>
                            <input type="hidden" name="seleccionados[]" value="{{ $fila['clave'] }}">
                            <input type="hidden" name="detalles[{{ $fila['clave'] }}][tipo]"
                                value="{{ $fila['tipo'] }}">
                            <input type="hidden" name="detalles[{{ $fila['clave'] }}][diametro]"
                                value="{{ $fila['diametro'] }}">
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="text-right">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Confirmar y crear pedido
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
