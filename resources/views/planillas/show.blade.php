<x-app-layout>
    @php
    $planilla = $planillaCalculada['planilla'];
@endphp
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('planillas.index') }}" class="text-blue-500">
                {{ __('Planillas') }}
            </a><span> / </span>Elementos de Planilla <strong class="text-black">{{ $planilla->codigo_limpio }}</strong>
        </h2>
    </x-slot>

    <div class="container mx-auto p-2">
        <h1 class="text-2xl font-bold mb-4 text-black">Progreso de la Planilla</h1>

        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-black">
                Planilla: {{ $planilla->codigo_limpio }}
                (Peso Total: {{ number_format($planilla->peso_total, 2) }} kg)
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2 text-black">
                <p><strong>Cliente:</strong> {{ $planilla->cliente }}</p>
                <p><strong>Obra:</strong> {{ $planilla->nom_obra }}</p>
                <p><strong>Sección:</strong> {{ $planilla->seccion }}</p>
                <p><strong>Descripción:</strong> {{ $planilla->descripcion }}</p>
            </div>

            <!-- Barra de progreso -->
            <div class="w-full bg-gray-200 rounded-full h-6 mt-4">
                <div class="bg-blue-600 text-xs font-medium text-white text-center p-1 leading-none rounded-full"
                    style="width: {{ $planillaCalculada['progreso'] }}%">
                    {{ number_format($planillaCalculada['progreso'], 2) }}%
                </div>
            </div>

            <!-- Sección: Paquetes Empaquetados -->
            @if ($planillaCalculada['paquetes']->isNotEmpty())
                <h3 class="text-md font-semibold mt-6 text-black">Paquetes Empaquetados</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                    @foreach ($planillaCalculada['paquetes'] as $paquete)
                        <div class="bg-white shadow-md rounded-lg p-2 border {{ $paquete->color }}">
                            <strong class="text-black">Paquete #{{ $paquete->id }}</strong> - 
                            Peso: {{ number_format($paquete->peso, 2) }} kg
                            @if (!is_null($paquete->ubicacion))
                                - Ubicación: {{ $paquete->ubicacion->nombre }}
                            @endif

                            <!-- Filtrar etiquetas que pertenecen a este paquete -->
                            @php
                                $etiquetasPaquete = $planillaCalculada['etiquetas']->where('paquete_id', $paquete->id);
                            @endphp

                            @if ($etiquetasPaquete->isNotEmpty())
                                <h4 class="text-md font-semibold mt-4 text-gray-800">Etiquetas dentro del Paquete</h4>
                                <ul class="list-disc list-inside ml-4 mt-2 mb-4">
                                    @foreach ($etiquetasPaquete as $etiqueta)
                                        <li class="p-2 rounded-lg {{ $etiqueta->color }} text-gray-800">
                                            <strong>Etiqueta #{{ $etiqueta->id }}</strong> - 
                                            Peso: {{ number_format($etiqueta->peso, 2) }} kg - 
                                            Estado: {{ $etiqueta->estado }}
                                            @if (!is_null($etiqueta->ubicacion))
                                                - Ubicación: {{ $etiqueta->ubicacion->nombre }}
                                            @endif

                                            <!-- Filtrar elementos dentro de esta etiqueta -->
                                            @if ($etiqueta->elementos->isNotEmpty())
                                                <ul class="list-disc list-inside ml-4 mt-2">
                                                    @foreach ($etiqueta->elementos as $elemento)
                                                        <li class="p-2 rounded-lg {{ $elemento->color }} text-gray-600">
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

                                                            <!-- Subpaquetes dentro del elemento -->
                                                            @if ($elemento->subpaquetes && $elemento->subpaquetes->isNotEmpty())
                                                                <ul class="list-disc list-inside ml-6 mt-2">
                                                                    @foreach ($elemento->subpaquetes as $subpaquete)
                                                                        <li class="p-2 rounded-lg bg-gray-100 text-gray-400">
                                                                            <strong>Subpaquete:</strong> {{ $subpaquete->nombre }} - 
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
                                                <p class="text-gray-600 text-sm mt-2">No hay elementos en esta etiqueta.</p>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-gray-500 text-sm mt-2">No hay etiquetas en este paquete.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 mt-4">No hay paquetes empaquetados.</p>
            @endif

            <hr style="border: 1px solid black; margin: 30px;">

            <!-- Sección: Etiquetas sin paquete -->
            @if ($planillaCalculada['etiquetasSinPaquete']->isNotEmpty())
                <h3 class="text-md font-semibold mt-8 text-gray-800">Etiquetas sin paquete</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                    @foreach ($planillaCalculada['etiquetasSinPaquete'] as $etiqueta)
                        <div class="bg-white shadow-md rounded-lg p-2 border {{ $etiqueta->color }} text-gray-800">
                            <strong>Etiqueta #{{ $etiqueta->id }}</strong> - Peso: {{ number_format($etiqueta->peso, 2) }} kg - Estado: {{ $etiqueta->estado }}
                            @if (!is_null($etiqueta->ubicacion))
                                - Ubicación: {{ $etiqueta->ubicacion->nombre }}
                            @endif

                            <!-- Elementos dentro de la etiqueta -->
                            @if ($etiqueta->elementos->isNotEmpty())
                                <ul class="list-disc list-inside ml-4 mt-2">
                                    @foreach ($etiqueta->elementos as $elemento)
                                        <li class="p-2 rounded-lg {{ $elemento->color }} text-gray-600">
                                            <strong>Elemento #{{ $elemento->id }}</strong> - 
                                            Peso: {{ number_format($elemento->peso, 2) }} kg
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-gray-600 text-sm mt-2">No hay elementos en esta etiqueta.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 mt-4">No hay etiquetas sin paquete.</p>
            @endif
        </div>
    </div>
</x-app-layout>
