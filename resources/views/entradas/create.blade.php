<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Entradas de Material') }}
        </h2>
    </x-slot>
    <div class="container mx-auto px-4 py-6">
        <form id="inventarioForm" method="POST" action="{{ route('entradas.store') }}"
            class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
            @csrf

            <div class="mb-4">
                <label for="fabricante" class="block text-gray-700 font-bold mb-2">Fabricante:</label>
                <select id="fabricante" name="fabricante" required class="w-full px-3 py-2 border rounded-lg">
                    <option value="" {{ old('fabricante') == '' ? 'selected' : '' }}>Seleccione un fabricante
                    </option>
                    <option value="MEGASA" {{ old('fabricante') == 'MEGASA' ? 'selected' : '' }}>MEGASA</option>
                    <option value="GETAFE" {{ old('fabricante') == 'GETAFE' ? 'selected' : '' }}>GETAFE</option>
                    <option value="NERVADUCTIL" {{ old('fabricante') == 'NERVADUCTIL' ? 'selected' : '' }}>NERVADUCTIL
                    </option>
                    <option value="SIDERURGICA SEVILLANA"
                        {{ old('fabricante') == 'SIDERURGICA SEVILLANA' ? 'selected' : '' }}>SIDERURGICA SEVILLANA
                    </option>
                </select>
            </div>

            <div class="mb-4">
                <label for="albaran" class="block text-gray-700 font-bold mb-2">Albarán:</label>
                <input type="text" id="albaran" name="albaran" value="{{ old('albaran') }}" required
                    pattern="[A-Za-z0-9]{5,15}" title="Debe contener entre 5 y 15 caracteres alfanuméricos"
                    class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="p-4 border rounded-lg bg-gray-100">
                <h3 class="font-bold text-lg mb-2">Paquete</h3>
                <div class="mb-2">
                    <label for="tipo" class="block text-gray-700">Tipo:</label>
                    <select id="tipo" name="tipo" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="ENCARRETADO" {{ old('tipo') == 'ENCARRETADO' ? 'selected' : '' }}>ENCARRETADO
                        </option>
                        <option value="BARRA" {{ old('tipo') == 'BARRA' ? 'selected' : '' }}>BARRA</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label for="diametro" class="block text-gray-700">Diámetro:</label>
                    <select id="diametro" name="diametro" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="" disabled {{ old('diametro') == '' ? 'selected' : '' }}>Seleccione un
                            diámetro</option>
                        @foreach ([5, 8, 10, 12, 16, 20, 25, 32] as $diametro)
                            <option value="{{ $diametro }}" {{ old('diametro') == $diametro ? 'selected' : '' }}>
                                {{ $diametro }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label for="longitud" class="block text-gray-700">Longitud:</label>
                    <select id="longitud" name="longitud" class="w-full px-3 py-2 border rounded-lg">
                        <option value="" disabled {{ old('longitud') == '' ? 'selected' : '' }}>Seleccione una
                            longitud</option>
                        @foreach ([6, 12, 14, 15, 16] as $longitud)
                            <option value="{{ $longitud }}" {{ old('longitud') == $longitud ? 'selected' : '' }}>
                                {{ $longitud }}</option>
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
                    <input type="number" id="peso" name="peso" value="{{ old('peso') }}" required
                        min="1" step="0.01" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="mb-2">
                    <label for="ubicacion" class="block text-gray-700">Ubicación:</label>
                    <input type="text" id="ubicacion" name="ubicacion" value="{{ old('ubicacion') }}" required
                        class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>

            <button type="submit"
                class="w-full bg-blue-500 text-white py-2 px-4 mt-4 rounded-lg hover:bg-blue-600">Registrar
                Entrada</button>
        </form>
    </div>
</x-app-layout>
