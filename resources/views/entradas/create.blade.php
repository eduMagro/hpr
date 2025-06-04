<x-app-layout>
    @if (auth()->user()->rol !== 'operario')
        <x-slot name="header">
            <h2 class="text-lg font-semibold text-gray-800">
                <a href="{{ route('entradas.index') }}" class="text-blue-600">
                    {{ __('Entradas') }}
                </a>
                <span class="mx-2">/</span>
                {{ __('Crear Entradas de Material') }}
            </h2>
        </x-slot>
    @endif

    <div class="container mx-auto px-0 py-4 sm:px-4 sm:py-6" x-data="{ paquetes: '1', peso: '', ubicacion: '' }">
        <form id="inventarioForm" method="POST" action="{{ route('entradas.store') }}" class="max-w-lg mx-auto">
            @csrf

            {{-- Selector de cantidad de paquetes --}}
            <div class="mb-4">
                <label for="cantidad_paquetes" class="block text-gray-700 font-bold mb-2">
                    ¿Cuántos paquetes quieres registrar?
                </label>
                <select id="cantidad_paquetes" name="cantidad_paquetes" class="w-full px-3 py-2 border rounded-lg"
                    x-model="paquetes">
                    <option value="1">1 paquete</option>
                    <option value="2">2 paquetes</option>
                </select>
            </div>

            {{-- Primer paquete --}}
            <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200 mb-6">
                <h3 class="text-blue-700 font-semibold text-base mb-2">🧱 Primer paquete</h3>

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
                        required pattern="[A-Za-z0-9 ]{5,30}"
                        title="Debe contener entre 5 y 30 caracteres alfanuméricos"
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
            </div>

            {{-- Segundo paquete --}}
            <div x-show="paquetes === '2'" class="bg-white p-6 rounded-lg shadow-md border border-gray-200 mt-6">
                <h3 class="text-blue-700 font-semibold text-base mb-2">🧱 Segundo paquete</h3>
                <input type="text" id="codigo_2" name="codigo_2" :required="paquetes === '2'"
                    x-show="paquetes === '2'" value="{{ old('codigo_2') }}"
                    placeholder="Escanee el segundo código MP..." class="w-full px-3 py-2 border rounded-lg">


                <div class="mb-2">
                    <label for="n_colada_2" class="block text-gray-700">Nº Colada:</label>
                    <input type="text" id="n_colada_2" name="n_colada_2" value="{{ old('n_colada_2') }}"
                        class="w-full px-3 py-2 border rounded-lg">
                </div>

                <div class="mb-2">
                    <label for="n_paquete_2" class="block text-gray-700">Nº Paquete:</label>
                    <input type="text" id="n_paquete_2" name="n_paquete_2" value="{{ old('n_paquete_2') }}"
                        class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>

            {{-- Peso y ubicación --}}
            <div class="mt-6 bg-white p-6 rounded-lg shadow-md border border-gray-200">
                <div class="mb-2">
                    <label for="peso" class="block text-gray-700">Peso (kg):</label>
                    <input type="number" id="peso" name="peso" value="{{ old('peso') }}" required
                        min="1" step="0.01" class="w-full px-3 py-2 border rounded-lg" x-model="peso">
                    <p class="text-sm text-gray-500 mt-1" x-show="paquetes === '2'">
                        Introduce el <strong>peso total de ambos paquetes</strong>. Se dividirá automáticamente.
                    </p>
                </div>

                <select id="ubicacion" name="ubicacion" class="w-full px-3 py-2 mt-2 border rounded-lg"
                    x-model="ubicacion" required>
                    <option value="">Seleccione una ubicación</option>
                    @foreach ($ubicaciones as $ubicacion)
                        <option value="{{ $ubicacion->id }}"
                            {{ old('ubicacion') == $ubicacion->id ? 'selected' : '' }}>
                            {{ $ubicacion->nombre_sin_prefijo }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="w-full bg-blue-500 text-white py-2 px-4 mt-4 rounded-lg hover:bg-blue-600">
                Registrar Entrada
            </button>
        </form>
    </div>
</x-app-layout>
