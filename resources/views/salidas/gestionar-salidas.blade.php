<x-app-layout>
    <x-slot name="title">Gestionar Salidas - {{ config('app.name') }}</x-slot>
    <x-menu.salidas />

    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6 text-gray-900">Gestionar Salidas para Planillas</h1>

        {{-- Resumen de Planillas --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h2 class="text-xl font-semibold text-blue-900 mb-3">Resumen de Planillas Seleccionadas</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($planillas as $planilla)
                    <div class="bg-white p-3 rounded shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-semibold text-gray-800">{{ $planilla->codigo_limpio }}</span>
                            <span class="text-xs px-2 py-1 rounded {{ $planilla->estado_class }}">
                                {{ ucfirst($planilla->estado) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600">Obra: {{ $planilla->obra->obra }}</p>
                        <p class="text-sm text-gray-600">Cliente: {{ $planilla->cliente->empresa }}</p>
                        <p class="text-sm text-gray-600">Peso: {{ $planilla->peso_total_kg }} kg</p>
                        <p class="text-sm text-gray-600">Paquetes disponibles: {{ $planilla->paquetes->count() }}</p>
                        <p class="text-sm text-gray-600">Entrega: {{ $planilla->fecha_estimada_entrega }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 p-3 bg-blue-100 rounded">
                <p class="text-sm font-semibold text-blue-900">Total: {{ $planillas->count() }} planillas |
                    {{ $paquetesDisponibles->count() }} paquetes disponibles |
                    {{ number_format($planillas->sum('peso_total'), 2) }} kg</p>
            </div>
        </div>

        {{-- Sección: Crear Salidas Nuevas --}}
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Crear Nuevas Salidas</h2>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    ¿Cuántas salidas necesitas crear para estas {{ $planillas->count() }} planillas?
                </label>
                <input type="number" id="num-salidas" min="1" max="10" value="1"
                    class="w-32 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <button type="button" id="btn-generar-formularios"
                    class="ml-3 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Generar Formularios
                </button>
            </div>

            <div id="formularios-salidas" class="space-y-4"></div>

            <div id="btn-crear-container" class="hidden mt-6 text-center">
                <button type="button" id="btn-crear-todas-salidas"
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-md font-semibold">
                    Crear Todas las Salidas
                </button>
            </div>
        </div>

        {{-- Sección: Gestión de Paquetes y Salidas --}}
        @if ($salidasExistentes->isNotEmpty())
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Gestionar Paquetes de las Salidas</h2>

                <p class="text-sm text-gray-600 mb-4">
                    Arrastra los paquetes desde "Paquetes Disponibles" hacia las salidas correspondientes.
                </p>

                    <div class="grid grid-cols-1 lg:grid-cols-{{ min($salidasExistentes->count() + 1, 4) }} gap-4">
                        {{-- Columnas de Salidas --}}
                        @foreach ($salidasExistentes as $salida)
                            <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4 min-h-[400px]">
                                <div class="font-semibold text-blue-900 mb-3">
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex-1">
                                            <p class="text-sm">🚚 {{ $salida->codigo_salida }}</p>
                                        </div>
                                        <button onclick="eliminarSalida({{ $salida->id }})"
                                                class="text-red-600 hover:text-red-800 hover:bg-red-100 rounded p-1 transition-colors"
                                                title="Eliminar salida">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="text-xs text-gray-600 space-y-1">
                                        @php
                                            // Obtener obras y clientes únicos de los paquetes de esta salida
                                            $obras = $salida->paquetes->pluck('planilla.obra.obra')->unique()->filter();
                                            $clientes = $salida->paquetes->pluck('planilla.cliente.empresa')->unique()->filter();
                                        @endphp
                                        @if($obras->isNotEmpty())
                                            <p class="truncate" title="{{ $obras->implode(', ') }}">🏗️ {{ $obras->implode(', ') }}</p>
                                        @endif
                                        @if($clientes->isNotEmpty())
                                            <p class="truncate" title="{{ $clientes->implode(', ') }}">👤 {{ $clientes->implode(', ') }}</p>
                                        @endif
                                        <p>{{ $salida->empresaTransporte->nombre ?? 'Sin empresa' }}</p>
                                        <p>{{ $salida->camion->modelo ?? 'Sin camión' }}</p>
                                        <p class="bg-blue-200 px-2 py-1 rounded inline-block peso-total-salida"
                                           data-salida-id="{{ $salida->id }}">0 kg</p>
                                    </div>
                                </div>

                                <div class="paquetes-zona drop-zone bg-white rounded border-2 border-dashed border-blue-300 p-2 min-h-[300px]"
                                    data-salida-id="{{ $salida->id }}">
                                    {{-- Paquetes asignados a esta salida --}}
                                    @foreach ($salida->paquetes as $paquete)
                                        <div class="paquete-item bg-white border border-gray-300 rounded p-2 mb-2 cursor-move hover:shadow-md transition-shadow"
                                            draggable="true" data-paquete-id="{{ $paquete->id }}"
                                            data-peso="{{ $paquete->peso }}">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="font-medium">📦 {{ $paquete->codigo }}</span>
                                                <button onclick="mostrarDibujo({{ $paquete->id }}); event.stopPropagation();"
                                                    class="text-blue-500 hover:underline text-xs">
                                                    👁️ Ver
                                                </button>
                                            </div>
                                            <div class="flex items-center justify-between text-xs mt-1">
                                                <span class="text-gray-500">{{ $paquete->planilla->codigo ?? 'N/A' }}</span>
                                                <span class="text-gray-600">{{ number_format($paquete->peso, 2) }} kg</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        {{-- Columna de Paquetes Disponibles --}}
                        <div class="bg-gray-50 border-2 border-gray-300 rounded-lg p-4 min-h-[400px]">
                            <div class="font-semibold text-gray-900 mb-3">📋 Paquetes Disponibles</div>
                            <div class="paquetes-zona drop-zone bg-white rounded border-2 border-dashed border-gray-400 p-2 min-h-[300px]"
                                data-salida-id="null">
                                @foreach ($paquetesDisponibles as $paquete)
                                    <div class="paquete-item bg-white border border-gray-300 rounded p-2 mb-2 cursor-move hover:shadow-md transition-shadow"
                                        draggable="true" data-paquete-id="{{ $paquete->id }}"
                                        data-peso="{{ $paquete->peso }}">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="font-medium">📦 {{ $paquete->codigo }}</span>
                                            <button onclick="mostrarDibujo({{ $paquete->id }}); event.stopPropagation();"
                                                class="text-blue-500 hover:underline text-xs">
                                                👁️ Ver
                                            </button>
                                        </div>
                                        <div class="flex items-center justify-between text-xs mt-1">
                                            <span class="text-gray-500">{{ $paquete->planilla->codigo ?? 'N/A' }}</span>
                                            <span class="text-gray-600">{{ number_format($paquete->peso, 2) }} kg</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 text-center">
                        <button type="button" id="btn-guardar-asignaciones"
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-md font-semibold">
                            💾 Guardar Asignaciones de Paquetes
                        </button>
                    </div>
                </div>
        @else
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Gestionar Paquetes de las Salidas</h2>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                    <p class="text-gray-700">
                        📦 No hay salidas pendientes para estas obras.
                    </p>
                    <p class="text-sm text-gray-600 mt-2">
                        Crea salidas usando el formulario de arriba para poder asignar paquetes.
                    </p>
                </div>
            </div>
        @endif
    </div>

    {{-- Modal para visualizar elementos del paquete --}}
    <div id="modal-dibujo" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center p-4">
        <div
            class="bg-white p-4 sm:p-6 rounded-lg w-full sm:w-[800px] md:w-[900px] lg:w-[1000px] max-w-[95vw] max-h-[90vh] flex flex-col shadow-lg relative">
            <button id="cerrar-modal" class="absolute top-2 right-2 text-red-600 hover:bg-red-100">
                ✖
            </button>

            <h2 class="text-xl font-semibold mb-4 text-center">Elementos del paquete</h2>

            <div class="overflow-y-auto flex-1 min-h-0" style="max-height: 75vh;">
                <canvas id="canvas-dibujo" width="800" height="600" class="border max-w-full h-auto"></canvas>
            </div>
        </div>
    </div>

    @php
        $cfg = [
            'csrf' => csrf_token(),
            'planillasIds' => $planillas->pluck('id')->toArray(),
            'empresas' => $empresas,
            'camiones' => $camiones,
            'routes' => [
                'crearSalidasVacias' => route('salidas.crearSalidasVaciasMasivo'),
                'guardarAsignacionesPaquetes' => route('planificacion.guardarAsignacionesPaquetes'),
                'recargarVista' => route('salidas-ferralla.gestionar-salidas', ['planillas' => implode(',', $planillas->pluck('id')->toArray())]),
            ],
        ];

        // Combinar todos los paquetes (disponibles + asignados a salidas) con sus elementos
        $todosPaquetes = $paquetesDisponibles->concat($salidasExistentes->flatMap->paquetes)
            ->unique('id')
            ->map(function($paquete) {
                // Aplanar la estructura etiquetas->elementos para que el JS pueda consumirlo
                $elementos = [];
                foreach ($paquete->etiquetas as $etiqueta) {
                    foreach ($etiqueta->elementos as $elemento) {
                        $elementos[] = [
                            'id' => $elemento->id,
                            'dimensiones' => $elemento->dimensiones,
                        ];
                    }
                }

                return [
                    'id' => $paquete->id,
                    'codigo' => $paquete->codigo,
                    'peso' => $paquete->peso,
                    'elementos' => $elementos,
                ];
            });
    @endphp

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        window.AppGestionSalidas = @json($cfg);
        window.paquetes = @json($todosPaquetes);
    </script>

    <script src="{{ asset('js/gestion-salidas.js') }}"></script>
    <script src="{{ asset('js/elementosJs/figuraElemento.js') }}" defer></script>
</x-app-layout>
