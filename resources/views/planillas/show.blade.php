<x-app-layout>
    @php
        $planilla = $planillaCalculada['planilla'];
    @endphp
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('planillas.index') }}" class="text-blue-500">
                {{ __('Planillas') }}
            </a>
            <span> / </span>Elementos de Planilla <strong class="text-black">{{ $planilla->codigo_limpio }}</strong>
        </h2>
    </x-slot>

    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4 text-black">Progreso de la Planilla</h1>

        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-black">
                Planilla: {{ $planilla->codigo_limpio }} (Peso Total: {{ number_format($planilla->peso_total, 2) }} kg)
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2 text-black">
                <p><strong>Cliente:</strong> {{ $planilla->cliente->empresa }}</p>
                <p><strong>Obra:</strong> {{ $planilla->obra->obra }}</p>
                <p><strong>Sección:</strong> {{ $planilla->seccion }}</p>
                <p><strong>Descripción:</strong> {{ $planilla->descripcion }}</p>
            </div>

            <div class="w-full bg-gray-200 rounded-full h-6 mt-4">
                <div class="bg-blue-600 text-xs font-medium text-white text-center p-1 leading-none rounded-full"
                    style="width: {{ $planillaCalculada['progreso'] }}%">
                    {{ number_format($planillaCalculada['progreso'], 2) }}%
                </div>
            </div>
        </div>

        @if ($planillaCalculada['paquetes']->isNotEmpty())
            <h3 class="text-md font-semibold mt-6 text-black">Elementos Empaquetados</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                @foreach ($planillaCalculada['paquetes'] as $paquete)
                    <div class="bg-white shadow-md rounded-lg p-4 border {{ $paquete->color }}">
                        <strong class="text-black">Paquete #{{ $paquete->id }}</strong> -
                        Peso: {{ number_format($paquete->peso, 2) }} kg
                        @if (!is_null($paquete->ubicacion))
                            - Ubicación: {{ $paquete->ubicacion->nombre }}
                        @endif

                        @if ($paquete->elementos->isNotEmpty())
                            <h4 class="text-md font-semibold mt-4 text-gray-800">Elementos dentro del Paquete</h4>
                            <ul class="list-disc list-inside ml-4 mt-2">
                                @foreach ($paquete->elementos as $elemento)
                                    <li class="p-2 rounded-lg {{ $elemento->color }} text-gray-600">
                                        <strong>ET#</strong>{{ $elemento->etiquetaRelacion->id ?? null }} -
                                        <strong>Elemento #{{ $elemento->id }}</strong> -
                                        Peso: {{ number_format($elemento->peso, 2) }} kg
                                        @if (!is_null($elemento->maquina))
                                            - Máquina: {{ $elemento->diametro_mm }}
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-gray-500 text-sm mt-2">No hay etiquetas ni elementos en este paquete.</p>
                        @endif

                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 mt-4">No hay paquetes empaquetados.</p>
        @endif

        {{-- @if ($planillaCalculada['elementosSinPaquete']->isNotEmpty())
            <h3 class="text-md font-semibold mt-8 text-gray-800">Elementos sin paquete</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                @foreach ($planillaCalculada['elementosSinPaquete']->reject(fn($e) => $planillaCalculada['subpaquetes']->contains('elemento_id', $e->id)) as $elemento)
                    <div class="bg-white shadow-md rounded-lg p-4 border {{ $elemento->color }} text-gray-800">
                        <strong>Elemento #{{ $elemento->id }}</strong> -
                        Peso: {{ number_format($elemento->peso, 2) }} kg
                        @if (!is_null($elemento->maquina))
                            - Máquina: {{ $elemento->maquina->nombre }}
                        @endif
                        @if (!is_null($elemento->ubicacion))
                            - Ubicación: {{ $elemento->ubicacion->nombre }}
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 mt-4">No hay elementos sin paquete.</p>
        @endif --}}
    </div>
</x-app-layout>
