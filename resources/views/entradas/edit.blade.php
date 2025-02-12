<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Entrada de Material') }}
        </h2>
    </x-slot>

    <!-- Mostrar mensajes de error y éxito -->
    @if ($errors->any())
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Errores encontrados',
                    html: '<ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>',
                    confirmButtonColor: '#d33'
                });
            });
        </script>
    @endif
    @if (session('error'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '{{ session('error') }}',
                    confirmButtonColor: '#d33'
                });
            });
        </script>
    @endif

    @if (session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'success',
                    text: '{{ session('success') }}',
                    confirmButtonColor: '#28a745'
                });
            });
        </script>
    @endif


    <div class="container mx-auto px-4 py-6">
        <form method="POST" action="{{ route('entradas.update', $entrada->id) }}"
            class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="fabricante" class="block text-gray-700 font-bold mb-2">Fabricante:</label>
                <select id="fabricante" name="fabricante" required class="w-full px-3 py-2 border rounded-lg">
                    <option value="" {{ old('fabricante', $entrada->fabricante) == '' ? 'selected' : '' }}>
                        Seleccione un fabricante</option>
                    <option value="MEGASA" {{ old('fabricante', $entrada->fabricante) == 'MEGASA' ? 'selected' : '' }}>
                        MEGASA</option>
                    <option value="GETAFE" {{ old('fabricante', $entrada->fabricante) == 'GETAFE' ? 'selected' : '' }}>
                        GETAFE</option>
                    <option value="NERVADUCTIL"
                        {{ old('fabricante', $entrada->fabricante) == 'NERVADUCTIL' ? 'selected' : '' }}>NERVADUCTIL
                    </option>
                    <option value="SIDERURGICA SEVILLANA"
                        {{ old('fabricante', $entrada->fabricante) == 'SIDERURGICA SEVILLANA' ? 'selected' : '' }}>
                        SIDERURGICA SEVILLANA</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="albaran" class="block text-gray-700 font-bold mb-2">Albarán:</label>
                <input type="text" id="albaran" name="albaran" value="{{ old('albaran', $entrada->albaran) }}"
                    required class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="mb-4">
                <label for="peso_total" class="block text-gray-700 font-bold mb-2">Peso Total (kg):</label>
                <input type="number" id="peso_total" name="peso_total"
                    value="{{ old('peso_total', $entrada->peso_total) }}" required min="1" step="0.01"
                    class="w-full px-3 py-2 border rounded-lg">
            </div>

            <button type="submit"
                class="w-full bg-blue-500 text-white py-2 px-4 mt-4 rounded-lg hover:bg-blue-600">Actualizar
                Entrada</button>
        </form>
    </div>
</x-app-layout>
