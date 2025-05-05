<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Recepción del Pedido {{ $pedido->codigo }}</h2>
    </x-slot>

    <div class="px-4 py-6">
        <form action="{{ route('pedidos.recepcion.guardar', $pedido->id) }}" method="POST"
            class="bg-white shadow rounded p-6 space-y-6 max-w-4xl mx-auto">
            @csrf
            <table class="w-full text-sm border-collapse border text-center">

                <tbody>
                    @foreach ($pedido->productos as $producto)
                        <div x-data="{ items: [{}] }" class="mb-6 border rounded p-4 shadow-sm bg-gray-50">
                            <h4 class="text-md font-semibold mb-2">
                                {{ ucfirst($producto->tipo) }} / {{ $producto->diametro }} mm —
                                {{ $producto->pivot->cantidad }} kg pedidos
                            </h4>

                            <template x-for="(item, index) in items" :key="index">
                                <div class="grid grid-cols-6 gap-3 mb-2">
                                    <input type="hidden" name="lineas[{{ $producto->id }}][producto_base_id][]"
                                        value="{{ $producto->id }}">
                                    <input type="text" name="lineas[{{ $producto->id }}][peso][]"
                                        placeholder="Peso recibido" class="border px-2 py-1 rounded w-full col-span-1"
                                        required>
                                    <input type="text" name="lineas[{{ $producto->id }}][n_colada][]"
                                        placeholder="Nº colada" class="border px-2 py-1 rounded w-full col-span-1">
                                    <input type="text" name="lineas[{{ $producto->id }}][n_paquete][]"
                                        placeholder="Nº paquete" class="border px-2 py-1 rounded w-full col-span-1">
                                    <input type="text" name="lineas[{{ $producto->id }}][ubicacion_texto][]"
                                        class="border px-2 py-1 rounded w-full col-span-2"
                                        placeholder="Escanea ubicación">

                                    <input type="text" name="lineas[{{ $producto->id }}][otros][]"
                                        class="border px-2 py-1 rounded w-full col-span-6"
                                        placeholder="Observaciones (opcional)">
                                </div>
                            </template>

                            <button type="button" class="text-sm text-blue-600 hover:underline mt-2"
                                @click="items.push({})">+ Añadir otro paquete</button>
                        </div>
                    @endforeach

                </tbody>
            </table>

            <div class="text-right">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Confirmar recepción
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
