<x-app-layout>
    <x-slot name="title">Obras - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Lista de Obras') }}
        </h2>
    </x-slot>
    <div class="container px-6 py-4">

        <h2 class="text-xl font-semibold text-gray-800 mb-4">Listado de Clientes y sus Obras</h2>

        <a href="{{ route('clientes.create') }}" class="btn btn-secondary mb-4">Nuevo Cliente</a>

        <table class="table table-striped shadow-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">Empresa</th>
                    <th class="px-4 py-3">Código Cliente</th>
                    <th class="px-4 py-3">Obras</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clientes as $cliente)
                    <tr>
                        <td class="px-4 py-3">{{ $cliente->empresa }}</td>
                        <td class="px-4 py-3">{{ $cliente->codigo }}</td>
                        <td class="px-4 py-3">
                            @if ($cliente->obras->count() > 0)
                                <ul class="list-disc list-inside">
                                    @foreach ($cliente->obras as $obra)
                                        <li>{{ $obra->nombre_obra }} ({{ $obra->cod_obra }})</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="text-gray-500">Sin obras registradas</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center py-4 text-gray-500">No hay clientes registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>
</x-app-layout>
