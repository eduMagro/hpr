<x-app-layout>
    <x-slot name="title">Detalles de Cliente - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Cliente: ') }} {{ $cliente->empresa }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <!-- Botón para regresar -->
        <div class="mb-4">
            <a href="{{ route('clientes.index') }}" class="btn btn-secondary">⬅ Volver a Clientes</a>
        </div>

        <!-- Información del Cliente -->
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Información del Cliente</h3>
            <table class="w-full border border-gray-300 rounded-lg">
                <tbody class="text-gray-700 text-sm">
                    <tr class="border-b">
                        <td class="px-4 py-2 font-semibold">ID:</td>
                        <td class="px-4 py-2">{{ $cliente->id }}</td>
                    </tr>
                    <tr class="border-b">
                        <td class="px-4 py-2 font-semibold">Empresa:</td>
                        <td class="px-4 py-2">{{ $cliente->empresa }}</td>
                    </tr>
                    <tr class="border-b">
                        <td class="px-4 py-2 font-semibold">Teléfono:</td>
                        <td class="px-4 py-2">{{ $cliente->contacto1_telefono }}</td>
                    </tr>
                    <tr class="border-b">
                        <td class="px-4 py-2 font-semibold">Email:</td>
                        <td class="px-4 py-2">{{ $cliente->contacto1_email }}</td>
                    </tr>
                    <tr class="border-b">
                        <td class="px-4 py-2 font-semibold">Dirección:</td>
                        <td class="px-4 py-2">{{ $cliente->direccion }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-semibold">Estado:</td>
                        <td class="px-4 py-2">{{ $cliente->activo ? 'Activo' : 'Inactivo' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- OBRAS ASOCIADAS -->
        <div class="bg-white shadow-lg rounded-lg p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Obras del Cliente</h3>

            @if ($cliente->obras->isEmpty())
                <p class="text-gray-500">No hay obras registradas para este cliente.</p>
            @else
                <div class="w-full max-w-full overflow-x-auto">
                    <table class="w-full border border-gray-300 rounded-lg">
                        <thead class="bg-blue-500 text-white">
                            <tr class="text-left text-sm uppercase">
                                <th class="py-3 px-2 border text-center">ID</th>
                                <th class="px-2 py-3 text-center border">Nombre</th>
                                <th class="px-2 py-3 text-center border">Dirección</th>
                                <th class="px-2 py-3 text-center border">Ciudad</th>
                                <th class="px-2 py-3 text-center border">Fecha Inicio</th>
                                <th class="px-2 py-3 text-center border">Estado</th>
                                <th class="px-2 py-3 text-center border">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm">
                            @foreach ($cliente->obras as $obra)
                                <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer">
                                    <td class="px-2 py-3 text-center border">{{ $obra->id }}</td>
                                    <td class="px-2 py-3 text-center border">{{ $obra->nombre }}</td>
                                    <td class="px-2 py-3 text-center border">{{ $obra->direccion }}</td>
                                    <td class="px-2 py-3 text-center border">{{ $obra->ciudad }}</td>
                                    <td class="px-2 py-3 text-center border">{{ $obra->fecha_inicio }}</td>
                                    <td class="px-2 py-3 text-center border">
                                        <span
                                            class="{{ $obra->estado === 'En proceso' ? 'text-orange-500' : ($obra->estado === 'Finalizado' ? 'text-green-500' : 'text-gray-500') }}">
                                            {{ $obra->estado }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-3 text-center border">
                                        <a href="{{ route('obras.show', $obra->id) }}"
                                            class="text-blue-500 hover:underline">Ver</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
