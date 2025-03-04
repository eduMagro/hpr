<x-app-layout>
    <x-slot name="title">Salidas - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Listado de Salidas') }}
        </h2>
    </x-slot>

    <div class="container mx-auto p-6">
        <!-- Botón para crear nueva salida -->
        <div class="mb-4">
            <a href="{{ route('salidas.create') }}"
                class="bg-green-600 text-white py-2 px-4 rounded-lg shadow-lg hover:bg-green-700 transition duration-300 ease-in-out">
                Crear Nueva Salida
            </a>
        </div>

        <div class="bg-white shadow-lg rounded-lg p-6 mt-6">
            <h3 class="font-semibold text-xl text-gray-800 mb-4">Todas las Salidas</h3>
            <table class="min-w-full table-auto border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-left text-sm font-medium text-gray-700">
                        <th class="py-2 px-4 border-b">Salida</th>
                        <th class="py-2 px-4 border-b">Fecha</th>
                        <th class="py-2 px-4 border-b">Empresa</th>
                        <th class="py-2 px-4 border-b">Camión</th>
                        <th class="py-2 px-4 border-b">Estado</th>
                        <th class="py-2 px-4 border-b">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($salidas as $salida)
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border-b">{{ $salida->codigo_salida }}</td>
                            <td class="py-2 px-4 border-b">{{ $salida->created_at->format('d/m/Y H:i') }}</td>
                            <td class="py-2 px-4 border-b">{{ $salida->empresaTransporte->nombre }}</td>
                            <td class="py-2 px-4 border-b">{{ $salida->camion->modelo }} -
                                {{ $salida->camion->matricula }}</td>
                            <td class="py-2 px-4 border-b">
                                {{ ucfirst($salida->estado) }}
                            </td>
                            <td class="py-2 px-4 border-b">
                                <a href="{{ route('salidas.show', $salida->id) }}"
                                    class="text-blue-600 hover:text-blue-800">Ver</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
