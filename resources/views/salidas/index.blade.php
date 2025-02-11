<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Salidas') }}
        </h2>
    </x-slot>

    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">Progreso de Planillas</h1>

        @forelse ($planillasCalculadas as $data)
            @php
                $planilla = $data['planilla'];
            @endphp

            <div class="bg-white shadow-md rounded-lg p-4 mb-6">
                <h2 class="text-lg font-semibold text-gray-800">
                    Planilla: {{ $planilla->codigo_limpio }} (Peso Total: {{ number_format($planilla->peso_total, 2) }}
                    kg)
                </h2>
                <p>Cliente: {{ $planilla->cliente }}</p>
                <p>Obra: {{ $planilla->nom_obra }}</p>
                <p>Sección: {{ $planilla->seccion }}</p>
                <p>Descripción: {{ $planilla->descripcion }}</p>

                <!-- Barra de progreso corregida -->
                <div class="w-full bg-gray-200 rounded-full h-6 mt-2">
                    <div class="bg-blue-600 text-xs font-medium text-white text-center p-1 leading-none rounded-full"
                        style="width: {{ $data['progreso'] }}%">
                        {{ number_format($data['progreso'], 2) }}%
                    </div>
                </div>

                <!-- Listado de etiquetas sin paquete -->
                @if ($data['etiquetasSinPaquete']->isNotEmpty())
                    <h3 class="mt-6 text-md font-semibold">Etiquetas sin paquete:</h3>
                    <ul class="list-disc list-inside text-gray-700">
                        @foreach ($data['etiquetasSinPaquete'] as $etiqueta)
                            <li class="mt-2 {{ $etiqueta->color }} p-2 rounded-lg">
                                <strong>Etiqueta #{{ $etiqueta->id }}</strong> - Peso:
                                {{ number_format($etiqueta->peso, 2) }} kg
                                @if (!is_null($etiqueta->ubicacion))
                                    - Ubicación: {{ $etiqueta->ubicacion->nombre }}
                                @endif

                                <!-- Elementos dentro de esta etiqueta -->
                                @if ($etiqueta->elementos->isNotEmpty())
                                    <ul class="list-disc list-inside text-gray-500 ml-6">
                                        @foreach ($etiqueta->elementos as $elemento)
                                            <li class="{{ $elemento->color }} p-1 rounded-lg">
                                                <strong>Elemento #{{ $elemento->id }}</strong> -
                                                Peso: {{ number_format($elemento->peso, 2) }} kg -
                                                Máquina: {{ $elemento->maquina->nombre ?? 'Sin máquina' }}
                                                @if (!is_null($elemento->ubicacion))
                                                    - Ubicación: {{ $elemento->ubicacion->nombre }}
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-gray-500 text-sm ml-6">No hay elementos en esta etiqueta.</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @empty
            <p class="text-gray-500">No hay planillas disponibles.</p>
        @endforelse
    </div>
</x-app-layout>
