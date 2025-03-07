<x-app-layout>
    <x-slot name="title">Detalles de Cliente - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
            <a href="{{ route('clientes.index') }}" class="text-blue-600 hover:underline">
                {{ __('Clientes') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Cliente ') }} {{ $cliente->empresa }}
        </h2>
    </x-slot>

    <div x-data="{ modalObra: false }" class="container mx-auto px-4 py-6">

        <!-- Información del Cliente -->
        <div class="bg-white shadow-lg rounded-lg p-4 mb-6">
            <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-4">Información del Cliente</h3>
            <div class="grid grid-cols-2 gap-4 text-sm text-gray-700">
                <div><span class="font-semibold">Empresa:</span> {{ $cliente->empresa }}</div>
                <div><span class="font-semibold">Teléfono:</span> {{ $cliente->contacto1_telefono }}</div>
                <div><span class="font-semibold">Email:</span> {{ $cliente->contacto1_email }}</div>
                <div><span class="font-semibold">Dirección:</span> {{ $cliente->direccion }}</div>
                <div><span class="font-semibold">Estado:</span>
                    <span class="{{ $cliente->activo ? 'text-green-500' : 'text-red-500' }}">
                        {{ $cliente->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Sección de Obras -->
        <div class="bg-white shadow-lg rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-700">Obras del Cliente</h3>
                <button @click="modalObra = true" class="btn btn-primary">➕ Nueva Obra</button>
            </div>

            @if ($cliente->obras->isEmpty())
                <p class="text-gray-500">No hay obras registradas para este cliente.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full border border-gray-300 rounded-lg text-sm">
                        <thead class="bg-blue-500 text-white">
                            <tr class="text-left uppercase">
                                <th class="py-3 px-2 border text-center">ID</th>
                                <th class="px-2 py-3 text-center border">Nombre</th>
                                <th class="px-2 py-3 text-center border">Código</th>
                                <th class="px-2 py-3 text-center border">Ciudad</th>
                                <th class="px-2 py-3 text-center border">Dirección</th>
                                <th class="px-2 py-3 text-center border">Latitud</th>
                                <th class="px-2 py-3 text-center border">Longitud</th>
                                <th class="px-2 py-3 text-center border">Radio</th>
                                <th class="px-2 py-3 text-center border">Fecha Inicio</th>
                                <th class="px-2 py-3 text-center border">Peso Entregado</th>
                                <th class="px-2 py-3 text-center border">Estado</th>
                                <th class="px-2 py-3 text-center border">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700">
                            @foreach ($cliente->obras as $obra)
                                <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer">
                                    <td class="px-2 py-3 text-center border">{{ $obra->id }}</td>
                                    <td class="px-2 py-3 text-center border">{{ $obra->obra }}</td>
                                    <td class="px-2 py-3 text-center border">{{ $obra->cod_obra }}</td>
                                    <td class="px-2 py-3 text-center border">{{ $obra->ciudad }}</td>
                                    <td class="px-2 py-3 text-center border">{{ $obra->direccion }}</td>
                                    <td class="px-2 py-3 text-center border">{{ $obra->latitud }}</td>
                                    <td class="px-2 py-3 text-center border">{{ $obra->longitud }}</td>
                                    <td class="px-2 py-3 text-center border">{{ $obra->distancia }}</td>
                                    <td class="px-2 py-3 text-center border">{{ $obra->fecha_inicio }}</td>
                                    <td class="px-2 py-3 text-center border">
                                        {{ number_format($obra->peso_entregado, 2) }} kg
                                    </td>
                                    <td class="px-2 py-3 text-center border">
                                        <span class="{{ $obra->completada ? 'text-green-500' : 'text-red-500' }}">
                                            {{ $obra->completada ? 'Completada' : 'Pendiente' }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-3 text-center border">
                                        <a href="{{ route('obras.show', $obra->id) }}"
                                            class="text-blue-500 hover:underline">Ver</a>
                                        <x-boton-eliminar :action="route('obras.destroy', $obra->id)" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- MODAL PARA CREAR OBRA -->
        <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50" x-show="modalObra"
            x-transition.opacity>
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto transform transition-all scale-95"
                x-show="modalObra" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-90">

                <h3 class="text-2xl font-bold mb-6 text-center text-gray-800">Crear Nueva Obra</h3>

                <form action="{{ route('obras.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">

                    <!-- Formulario en dos columnas -->
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-700 font-semibold">Nombre de la Obra</label>
                            <input type="text" name="obra" class="form-input w-full p-2 border rounded-lg"
                                required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold">Código</label>
                            <input type="text" name="cod_obra" class="form-input w-full p-2 border rounded-lg"
                                required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold">Ciudad</label>
                            <input type="text" name="ciudad" class="form-input w-full p-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold">Dirección</label>
                            <input type="text" name="direccion" class="form-input w-full p-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold">Latitud</label>
                            <input type="text" name="latitud" class="form-input w-full p-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold">Longitud</label>
                            <input type="text" name="longitud" class="form-input w-full p-2 border rounded-lg">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-gray-700 font-semibold">Radio</label>
                            <input type="text" name="radio" class="form-input w-full p-2 border rounded-lg">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button"
                            class="btn bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold py-2 px-4 rounded-lg"
                            @click="modalObra = false">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="btn bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
