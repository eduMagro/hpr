<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('productos.index') }}" class="text-blue-600">
                {{ __('Materiales') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Editar Material') }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <form method="POST" action="{{ route('productos.update', $producto->id) }}"
            class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
            @csrf
            @method('PUT')

            <!-- Código (no editable si es clave primaria o identificador) -->
            <div class="mb-4">
                <label for="codigo" class="block text-gray-700 font-bold mb-2">Código:</label>
                <input type="text" id="codigo" name="codigo" value="{{ old('codigo', $producto->codigo) }}"
                    class="w-full px-3 py-2 border rounded-lg" readonly>
            </div>

            <!-- Proveedor -->
            <div class="mb-4">
                <label for="fabricante_id" class="block text-gray-700 font-bold mb-2">Fabricante:</label>
                <select id="fabricante_id" name="fabricante_id" required class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Seleccione un fabricante</option>
                    @foreach ($fabricantes as $fabricante)
                        <option value="{{ $fabricante->id }}"
                            {{ old('fabricante_id', $producto->fabricante) == $fabricante->id ? 'selected' : '' }}>
                            {{ $fabricante->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Producto base -->
            <div class="mb-4">
                <label for="producto_base_id" class="block text-gray-700 font-bold mb-2">Producto base:</label>
                <select id="producto_base_id" name="producto_base_id" required
                    class="w-full px-3 py-2 border rounded-lg">
                    <option value="" disabled>Seleccione un producto base</option>
                    @foreach ($productosBase as $base)
                        <option value="{{ $base->id }}"
                            {{ old('producto_base_id', $producto->producto_base_id) == $base->id ? 'selected' : '' }}>
                            {{ strtoupper($base->tipo) }} |
                            Ø{{ $base->diametro }}{{ $base->longitud ? ' | ' . $base->longitud . ' m' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Colada -->
            <div class="mb-2">
                <label for="n_colada" class="block text-gray-700 font-bold mb-2">Colada:</label>
                <input type="text" id="n_colada" name="n_colada" value="{{ old('n_colada', $producto->n_colada) }}"
                    class="w-full px-3 py-2 border rounded-lg">
            </div>

            <!-- Paquete -->
            <div class="mb-2">
                <label for="n_paquete" class="block text-gray-700 font-bold mb-2">Paquete:</label>
                <input type="text" id="n_paquete" name="n_paquete"
                    value="{{ old('n_paquete', $producto->n_paquete) }}" class="w-full px-3 py-2 border rounded-lg">
            </div>

            <!-- Peso -->
            <div class="mb-2">
                <label for="peso_inicial" class="block text-gray-700 font-bold mb-2">Peso inicial (kg):</label>
                <input type="number" step="0.01" min="0" id="peso_inicial" name="peso_inicial"
                    value="{{ old('peso_inicial', $producto->peso_inicial) }}"
                    class="w-full px-3 py-2 border rounded-lg">
            </div>

            <!-- Ubicación -->
            <div class="mb-2">
                <label for="ubicacion_id" class="block text-gray-700 font-bold mb-2">Ubicación:</label>
                <input type="text" id="ubicacion_id" name="ubicacion_id"
                    value="{{ old('ubicacion_id', $producto->ubicacion_id) }}"
                    class="w-full px-3 py-2 border rounded-lg">
            </div>

            <!-- Otros -->
            <div class="mb-2">
                <label for="otros" class="block text-gray-700 font-bold mb-2">Otros:</label>
                <input type="text" id="otros" name="otros" value="{{ old('otros', $producto->otros) }}"
                    class="w-full px-3 py-2 border rounded-lg">
            </div>

            <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 mt-4 rounded-lg hover:bg-green-700">
                Guardar Cambios
            </button>
        </form>
    </div>
</x-app-layout>
