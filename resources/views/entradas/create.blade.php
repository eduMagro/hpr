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
                    value="{{ old('albaran') }}" required class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="mb-4">
                <label for="peso_total" class="block text-gray-700 font-bold mb-2">Peso Total (kg):</label>
                <input type="number" id="peso_total" name="peso_total" value="{{ old('peso_total') }}" required
                    min="1" step="0.01" title="Debe ser un número mayor a 1 con hasta dos decimales"
                    value="{{ old('peso_total') }}" required class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="mb-4">
                <label for="cantidad_paquetes" class="block text-gray-700 font-bold mb-2">Número de Paquetes:</label>
                <input type="number" id="cantidad_paquetes" name="cantidad_paquetes"
                    value="{{ old('cantidad_paquetes') }}" min="1" max="30" step="1"
                    value="{{ old('cantidad_paquetes') }}" min="1" required
                    title="Debe ser un número entero mayor a 0" value="{{ old('cantidad_paquetes') }}" min="1"
                    required class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div id="paquetesContainer" class="space-y-4"></div>

            <button type="submit"
                class="w-full bg-blue-500 text-white py-2 px-4 mt-4 rounded-lg hover:bg-blue-600">Registrar
                Entrada</button>
        </form>

        <script>
            document.getElementById('cantidad_paquetes').addEventListener('input', function() {
                let cantidad = parseInt(this.value);
                let container = document.getElementById('paquetesContainer');
                let existingData = {};

                document.querySelectorAll('#paquetesContainer div').forEach((div, index) => {
                    existingData[index + 1] = {
                        nombre: div.querySelector(`[name="paquetes[${index + 1}][nombre]"]`)?.value || '',
                        tipo: div.querySelector(`[name="paquetes[${index + 1}][tipo]"]`)?.value || '',
                        diametro: div.querySelector(`[name="paquetes[${index + 1}][diametro]"]`)?.value ||
                            '',
                        longitud: div.querySelector(`[name="paquetes[${index + 1}][longitud]"]`)?.value ||
                            '',
                        n_colada: div.querySelector(`[name="paquetes[${index + 1}][n_colada]"]`)?.value ||
                            '',
                        n_paquete: div.querySelector(`[name="paquetes[${index + 1}][n_paquete]"]`)?.value ||
                            '',
                        peso: div.querySelector(`[name="paquetes[${index + 1}][peso]"]`)?.value || '',
                        ubicacion: div.querySelector(`[name="paquetes[${index + 1}][ubicacion]"]`)?.value ||
                            '',
                        otros: div.querySelector(`[name="paquetes[${index + 1}][otros]"]`)?.value || ''
                    };
                });

                container.innerHTML = '';

                if (cantidad > 0) {
                    for (let i = 1; i <= cantidad; i++) {
                        let div = document.createElement('div');
                        div.classList.add("p-4", "border", "rounded-lg", "bg-gray-100", "focus-within:bg-blue-200");
                        div.innerHTML = `
                    <h3 class="font-bold text-lg mb-2">Paquete ${i}</h3>
                    
                    <div class="mb-2">
                        <label for="tipo_${i}" class="block text-gray-700">Tipo:</label>
                        <select id="tipo_${i}" name="paquetes[${i}][tipo]" required class="w-full px-3 py-2 border rounded-lg">
                            <option value="ENCARRETADO">ENCARRETADO</option>
                            <option value="BARRA">BARRA</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="diametro_${i}" class="block text-gray-700">Diámetro:</label>
                        <select id="diametro_${i}" name="paquetes[${i}][diametro]" required class="w-full px-3 py-2 border rounded-lg">
                            <option value="" disabled selected>Seleccione un diámetro</option>
                     
                            <option value="5">5</option>
                            <option value="8">8</option>
                            <option value="10">10</option>
                            <option value="12">12</option>
                            <option value="16">16</option>
                            <option value="20">20</option>
                            <option value="25">25</option>
                            <option value="32">32</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="longitud_${i}" class="block text-gray-700">Longitud:</label>
                        <select id="longitud_${i}" name="paquetes[${i}][longitud]" class="w-full px-3 py-2 border rounded-lg">
                            <option value="6">6</option>
                            <option value="12">12</option>
                            <option value="14">14</option>
                            <option value="15">15</option>
                            <option value="16">16</option>
                        </select>
                    </div>
                    
                    <div class="mb-2">
                        <label for="n_colada_${i}" class="block text-gray-700">Colada:</label>
                        <input type="text" id="n_colada_${i}" name="paquetes[${i}][n_colada]" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div class="mb-2">
                        <label for="n_paquete_${i}" class="block text-gray-700">Paquete:</label>
                        <input type="text" id="n_paquete_${i}" name="paquetes[${i}][n_paquete]" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div class="mb-2">
                        <label for="peso_${i}" class="block text-gray-700">Peso (kg):</label>
                        <input type="number" id="peso_${i}" name="paquetes[${i}][peso]" required min="1" step="0.01" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div class="mb-2">
                        <label for="ubicacion_${i}" class="block text-gray-700">Ubicación:</label>
                        <input type="text" id="ubicacion_${i}" name="paquetes[${i}][ubicacion]" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    </div>
                `;
                        container.appendChild(div);
                    }
                }
            });
        </script>
</x-app-layout>
