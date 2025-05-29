<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('entradas.index') }}" class="text-blue-600">
                {{ __('Entradas') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Crear Entradas de Material') }}
        </h2>
    </x-slot>
    <div class="container mx-auto px-4 py-6">
        <form id="inventarioForm" method="POST" action="{{ route('entradas.store') }}"
            class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
            @csrf

            <div class="mb-4">
                <label for="codigo" class="block text-gray-700 font-bold mb-2">Código (escaneado):</label>
                <input type="text" id="codigo" name="codigo" value="{{ old('codigo') }}" required
                    placeholder="Escanee el código MP..." class="w-full px-3 py-2 border rounded-lg" autofocus>
            </div>

            <div class="mb-4">
                <label for="proveedor_id" class="block text-gray-700 font-bold mb-2">Proveedor:</label>
                <select id="proveedor_id" name="proveedor_id" required class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Seleccione un proveedor</option>
                    @foreach ($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}"
                            {{ old('proveedor_id') == $proveedor->id ? 'selected' : '' }}>
                            {{ $proveedor->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="albaran" class="block text-gray-700 font-bold mb-2">Albarán:</label>
                <input type="text" id="albaran" name="albaran" value="{{ old('albaran', 'Entrada manual') }}"
                    required pattern="[A-Za-z0-9 ]{5,30}" title="Debe contener entre 5 y 30 caracteres alfanuméricos"
                    class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="mb-4">
                <label for="producto_base_id" class="block text-gray-700 font-bold mb-2">Producto base:</label>
                <select id="producto_base_id" name="producto_base_id" required
                    class="w-full px-3 py-2 border rounded-lg">
                    <option value="" disabled>Seleccione un producto base</option>
                    @foreach ($productosBase as $producto)
                        <option value="{{ $producto->id }}"
                            {{ old('producto_base_id') == $producto->id ? 'selected' : '' }}>
                            {{ strtoupper($producto->tipo) }} |
                            Ø{{ $producto->diametro }}{{ $producto->longitud ? ' | ' . $producto->longitud . ' m' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-2">
                <label for="n_colada" class="block text-gray-700">Colada:</label>
                <input type="text" id="n_colada" name="n_colada" value="{{ old('n_colada') }}" required
                    class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-2">
                <label for="n_paquete" class="block text-gray-700">Paquete:</label>
                <input type="text" id="n_paquete" name="n_paquete" value="{{ old('n_paquete') }}" required
                    class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-2">
                <label for="peso" class="block text-gray-700">Peso (kg):</label>
                <input type="number" id="peso" name="peso" value="{{ old('peso') }}" required min="1"
                    step="0.01" class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-2">
                <label for="ubicacion" class="block text-gray-700">Ubicación:</label>
                <input type="text" id="ubicacion" name="ubicacion" value="{{ old('ubicacion') }}"
                    class="w-full px-3 py-2 border rounded-lg">
            </div>

            <button type="submit" class="w-full bg-blue-500 text-white py-2 px-4 mt-4 rounded-lg hover:bg-blue-600">
                Registrar Entrada
            </button>
        </form>
    </div>
</x-app-layout>
