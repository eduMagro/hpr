<x-app-layout>
    <x-slot name="title">Detalles de Obra - {{ $obra->obra }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('clientes.show', $obra->cliente_id) }}" class="text-blue-600 hover:underline">
                {{ $obra->cliente->empresa }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Obra: ') }} {{ $obra->obra }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <!-- Información de la Obra -->
        <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-4">Información de la Obra</h3>
            <div class="grid grid-cols-2 gap-4 text-sm text-gray-700">
                <div><span class="font-semibold">Código:</span> {{ $obra->cod_obra }}</div>
                <div><span class="font-semibold">Ciudad:</span> {{ $obra->ciudad }}</div>
                <div><span class="font-semibold">Dirección:</span> {{ $obra->direccion }}</div>
                <div><span class="font-semibold">Presupuesto Estimado:</span>
                    {{ number_format($obra->presupuesto_estimado, 2, ',', '.') }} €</div>
                <div><span class="font-semibold">Estado:</span>
                    @php
                        switch ($obra->estado) {
                            case 'activa':
                                $color = 'text-green-500';
                                break;
                            case 'completada':
                                $color = 'text-blue-500';
                                break;
                            case 'inactiva':
                            default:
                                $color = 'text-red-500';
                                break;
                        }
                    @endphp

                    <span class="{{ $color }}">
                        {{ ucfirst($obra->estado) }}
                    </span>

                </div>
            </div>
        </div>

        <!-- LISTADO DE PLANILLAS -->
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Planillas de la Obra</h3>

            @if ($planillas->isEmpty())
                <p class="text-gray-500">No hay planillas registradas para esta obra.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full border border-gray-300 rounded-lg text-sm">
                        <thead class="bg-blue-500 text-white">
                            <tr class="text-left uppercase">
                                <th class="py-3 px-2 border text-center">ID</th>
                                <th class="px-2 py-3 text-center border">Fecha</th>
                                <th class="px-2 py-3 text-center border">Peso Total</th>
                                <th class="px-2 py-3 text-center border">Estado</th>
                                <th class="px-2 py-3 text-center border">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700">
                            @foreach ($planillas as $planilla)
                                <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer">
                                    <td class="px-2 py-3 text-center border">{{ $planilla->id }}</td>
                                    <td class="px-2 py-3 text-center border">{{ $planilla->fecha }}</td>
                                    <td class="px-2 py-3 text-center border">
                                        {{ number_format($planilla->peso_total, 2) }} kg</td>
                                    <td class="px-2 py-3 text-center border">
                                        <span
                                            class="{{ $planilla->estado == 'completada' ? 'text-green-500' : 'text-red-500' }}">
                                            {{ ucfirst($planilla->estado) }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-3 text-center border">
                                        <a href="{{ route('planillas.show', $planilla->id) }}" wire:navigate
                                            class="text-blue-500 hover:underline">Ver</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="mt-4">
                    {{ $planillas->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
