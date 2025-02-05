<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Salidas') }}
        </h2>
    </x-slot>

    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">Progreso de Planillas</h1>

        @foreach ($planillasCalculadas as $data)
            @php
                $planilla = $data['planilla'];
                $pesoTotalPaquetes = $data['pesoTotalPaquetes'];
                $pesoElementosNoEmpaquetados = $data['pesoElementosNoEmpaquetados'];
                $pesoEtiquetasNoEmpaquetadas = $data['pesoEtiquetasNoEmpaquetadas'];
                $pesoAcumulado = $data['pesoAcumulado'];
                $pesoRestante = $data['pesoRestante'];
                $progreso = $data['progreso'];
            @endphp

            <div class="bg-white shadow-md rounded-lg p-4 mb-6">
                <h2 class="text-lg font-semibold text-gray-800">
                    Planilla: {{ $planilla->codigo_limpio }} (Peso Total: {{ number_format($planilla->peso_total, 2) }}
                    kg)
                </h2>
                <p>Cliente: {{ $planilla->cliente }} </p>
                <p>Obra: {{ $planilla->nom_obra }} </p>
                <p>Sección: {{ $planilla->seccion }} </p>
                <p>Descripción: {{ $planilla->descripcion }} </p>

                <!-- Barra de progreso -->
                <div class="w-full bg-gray-200 rounded-full h-6 mt-2">
                    <div class="bg-blue-600 text-xs font-medium text-white text-center p-1 leading-none rounded-full"
                        style="width: {{ $progreso }}%">
                        {{ number_format($progreso, 2) }}%
                    </div>
                </div>

                <p class="mt-2 text-gray-700">
                    Peso acumulado (paquetes + elementos no empaquetados + etiquetas no empaquetadas):
                    <strong>{{ number_format($pesoAcumulado, 2) }} kg</strong>
                </p>
                <p class="text-gray-700">
                    Peso restante: <strong>{{ number_format($pesoRestante, 2) }} kg</strong>
                </p>

                <!-- Paquetes en la planilla -->
                <h3 class="mt-4 text-md font-semibold">Paquetes en esta planilla:</h3>
                @if ($planilla->paquetes->isNotEmpty())
                    <ul class="list-disc list-inside text-gray-700">
                        @foreach ($planilla->paquetes as $paquete)
                            <li>
                                <a href="{{ route('paquetes.index', ['id' => $paquete->id]) }}"
                                    class="text-blue-500 hover:underline">
                                    Paquete #{{ $paquete->id }} - Peso: {{ number_format($paquete->peso, 2) }} kg -
                                    Ubicación: {{ $paquete->ubicacion->nombre ?? 'Sin ubicación' }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-500">No hay paquetes creados aún.</p>
                @endif
                <!-- Etiquetas completas no empaquetadas -->
                <h3 class="mt-4 text-md font-semibold">Etiquetas Completas No Empaquetadas:</h3>
                @if ($planilla->etiquetas->isNotEmpty())
                    <ul class="list-disc list-inside text-gray-700">
                        @foreach ($planilla->etiquetas as $etiqueta)
                            <li>
                                <a href="{{ route('etiquetas.index', ['id' => $etiqueta->id]) }}"
                                    class="text-blue-500 hover:underline">
                                    Etiqueta #{{ $etiqueta->id }} - Peso: {{ $etiqueta->peso_kg }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-500">No hay etiquetas completas sin empaquetar.</p>
                @endif
                <!-- Elementos completos no empaquetados -->
                <h3 class="mt-4 text-md font-semibold">Elementos Completos No Empaquetados:</h3>
                @if ($planilla->elementos->isNotEmpty())
                    <ul class="list-disc list-inside text-gray-700">
                        @foreach ($planilla->elementos as $elemento)
                            <li>
                                <a href="{{ route('elementos.index', ['id' => $elemento->id]) }}"
                                    class="text-blue-500 hover:underline">
                                    Elemento #{{ $elemento->id }} - Peso: {{ $elemento->peso_kg }} -
                                    {{ $elemento->ubicacion->nombre }}
                                </a>

                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-500">No hay elementos completos sin empaquetar.</p>
                @endif


            </div>
        @endforeach
    </div>
</x-app-layout>
