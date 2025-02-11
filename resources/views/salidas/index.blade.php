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
                    Planilla: {{ $planilla->codigo_limpio }}
                    (Peso Total: {{ number_format($planilla->peso_total, 2) }} kg)
                </h2>
                <p>Cliente: {{ $planilla->cliente }}</p>
                <p>Obra: {{ $planilla->nom_obra }}</p>
                <p>Sección: {{ $planilla->seccion }}</p>
                <p>Descripción: {{ $planilla->descripcion }}</p>

                <!-- Barra de progreso -->
                <div class="w-full bg-gray-200 rounded-full h-6 mt-2">
                    <div class="bg-blue-600 text-xs font-medium text-white text-center p-1 leading-none rounded-full"
                        style="width: {{ $data['progreso'] }}%">
                        {{ number_format($data['progreso'], 2) }}%
                    </div>
                </div>

                <!-- Sección: Paquetes Empaquetados -->
                <div class="mt-6">
                    <h3 class="text-md font-semibold">Paquetes Empaquetados:</h3>
                    @if ($data['paquetes']->isNotEmpty())
                        <ul class="list-disc list-inside text-gray-700">
                            @foreach ($data['paquetes'] as $paquete)
                                <li class="mt-2 {{ $paquete->color }} p-2 rounded-lg">
                                    <strong>Paquete #{{ $paquete->id }}</strong> -
                                    Peso: {{ number_format($paquete->peso, 2) }} kg
                                    @if (!is_null($paquete->ubicacion))
                                        - Ubicación: {{ $paquete->ubicacion->nombre }}
                                    @endif

                                    @php
                                        // Se filtran las etiquetas asociadas a este paquete.
                                        $etiquetasPaquete = $data['etiquetas']->where('paquete_id', $paquete->id);
                                    @endphp

                                    @if ($etiquetasPaquete->isNotEmpty())
                                        <ul class="list-disc list-inside ml-6">
                                            @foreach ($etiquetasPaquete as $etiqueta)
                                                <li class="mt-2 {{ $etiqueta->color }} p-2 rounded-lg">
                                                    <strong>Etiqueta #{{ $etiqueta->id }}</strong> -
                                                    Peso: {{ number_format($etiqueta->peso, 2) }} kg
                                                    @if (!is_null($etiqueta->ubicacion))
                                                        - Ubicación: {{ $etiqueta->ubicacion->nombre }}
                                                    @endif

                                                    <!-- Elementos de la etiqueta -->
                                                    @if ($etiqueta->elementos->isNotEmpty())
                                                        <ul class="list-disc list-inside ml-6">
                                                            @foreach ($etiqueta->elementos as $elemento)
                                                                <li class="mt-2 {{ $elemento->color }} p-1 rounded-lg">
                                                                    <strong>Elemento #{{ $elemento->id }}</strong> -
                                                                    Peso: {{ number_format($elemento->peso, 2) }} kg
                                                                    @if (!is_null($elemento->maquina))
                                                                        - Máquina: {{ $elemento->maquina->nombre }}
                                                                    @else
                                                                        - Máquina: Sin máquina
                                                                    @endif
                                                                    @if (!is_null($elemento->ubicacion))
                                                                        - Ubicación: {{ $elemento->ubicacion->nombre }}
                                                                    @endif

                                                                    <!-- Subpaquetes del elemento (si existen) -->
                                                                    @if ($elemento->subpaquetes && $elemento->subpaquetes->isNotEmpty())
                                                                        <ul class="list-disc list-inside ml-6">
                                                                            @foreach ($elemento->subpaquetes as $subpaquete)
                                                                                <li class="mt-1">
                                                                                    <strong>Subpaquete:</strong>
                                                                                    {{ $subpaquete->nombre }} -
                                                                                    Peso:
                                                                                    {{ number_format($subpaquete->peso, 2) }}
                                                                                    kg
                                                                                    @if (!empty($subpaquete->dimensiones))
                                                                                        - Dimensiones:
                                                                                        {{ $subpaquete->dimensiones }}
                                                                                    @endif
                                                                                    @if (!empty($subpaquete->cantidad))
                                                                                        - Cantidad:
                                                                                        {{ $subpaquete->cantidad }}
                                                                                    @endif
                                                                                    @if (!empty($subpaquete->descripcion))
                                                                                        - Descripción:
                                                                                        {{ $subpaquete->descripcion }}
                                                                                    @endif
                                                                                </li>
                                                                            @endforeach
                                                                        </ul>
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <p class="text-gray-500 text-sm ml-6">No hay elementos en esta
                                                            etiqueta.</p>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="text-gray-500 text-sm ml-6">No hay etiquetas en este paquete.</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-500">No hay paquetes empaquetados.</p>
                    @endif
                </div>

                <!-- Sección: Etiquetas sin paquete (sin empaquetar) -->
                @if ($data['etiquetasSinPaquete']->isNotEmpty())
                    <div class="mt-6">
                        <h3 class="text-md font-semibold">Etiquetas sin paquete:</h3>
                        <ul class="list-disc list-inside text-gray-700">
                            @foreach ($data['etiquetasSinPaquete'] as $etiqueta)
                                <li class="mt-2 {{ $etiqueta->color }} p-2 rounded-lg">
                                    <strong>Etiqueta #{{ $etiqueta->id }}</strong> -
                                    Peso: {{ number_format($etiqueta->peso, 2) }} kg
                                    @if (!is_null($etiqueta->ubicacion))
                                        - Ubicación: {{ $etiqueta->ubicacion->nombre }}
                                    @endif

                                    <!-- Elementos dentro de la etiqueta sin paquete -->
                                    @if ($etiqueta->elementos->isNotEmpty())
                                        <ul class="list-disc list-inside text-gray-500 ml-6">
                                            @foreach ($etiqueta->elementos as $elemento)
                                                <li class="{{ $elemento->color }} p-1 rounded-lg">
                                                    <strong>Elemento #{{ $elemento->id }}</strong> -
                                                    Peso: {{ number_format($elemento->peso, 2) }} kg
                                                    @if (!is_null($elemento->maquina))
                                                        - Máquina: {{ $elemento->maquina->nombre }}
                                                    @else
                                                        - Máquina: Sin máquina
                                                    @endif
                                                    @if (!is_null($elemento->ubicacion))
                                                        - Ubicación: {{ $elemento->ubicacion->nombre }}
                                                    @endif

                                                    <!-- Subpaquetes del elemento -->
                                                    @if ($elemento->subpaquetes && $elemento->subpaquetes->isNotEmpty())
                                                        <ul class="list-disc list-inside ml-6">
                                                            @foreach ($elemento->subpaquetes as $subpaquete)
                                                                <li class="mt-1">
                                                                    <strong>Subpaquete:</strong>
                                                                    {{ $subpaquete->nombre }} -
                                                                    Peso: {{ number_format($subpaquete->peso, 2) }} kg
                                                                    @if (!empty($subpaquete->dimensiones))
                                                                        - Dimensiones: {{ $subpaquete->dimensiones }}
                                                                    @endif
                                                                    @if (!empty($subpaquete->cantidad))
                                                                        - Cantidad: {{ $subpaquete->cantidad }}
                                                                    @endif
                                                                    @if (!empty($subpaquete->descripcion))
                                                                        - Descripción: {{ $subpaquete->descripcion }}
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                        </ul>
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
                    </div>
                @endif

            </div>
        @empty
            <p class="text-gray-500">No hay planillas disponibles.</p>
        @endforelse
    </div>
</x-app-layout>
