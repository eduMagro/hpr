<x-app-layout>
    <x-slot name="title">Gestionar Salidas - {{ config('app.name') }}</x-slot>

    {{-- Header con título y botón de volver --}}
    <div class="bg-white border-b border-gray-200 shadow-sm mb-6">
        <div class="container mx-auto px-2 sm:px-4 md:px-6 py-4">
            <div class="flex items-center gap-3">
                <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">
                    Gestionar Salidas para Planillas
                </h1>

                <a href="{{ route('planificacion.index') }}" wire:navigate
                   wire:navigate
                   class="inline-flex items-center gap-2 text-gray-600 hover:text-blue-600 transition-colors group flex-shrink-0"
                   title="Volver a Planificación de Portes">
                    <svg class="w-5 h-5 transform group-hover:-translate-x-1 transition-transform"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-2 sm:px-4 md:px-6 py-6">

        {{-- Resumen de Estados --}}
        @php
            $estadosCounts = $planillas->groupBy('estado')->map->count();
            $estadosConfig = [
                'pendiente' => ['label' => 'Pendiente', 'icon' => '⏳', 'color' => 'bg-yellow-100 text-yellow-800 border-yellow-200'],
                'fabricando' => ['label' => 'Fabricando', 'icon' => '🏭', 'color' => 'bg-blue-100 text-blue-800 border-blue-200'],
                'fabricada' => ['label' => 'Fabricada', 'icon' => '✅', 'color' => 'bg-green-100 text-green-800 border-green-200'],
                'enviada' => ['label' => 'Enviada', 'icon' => '🚚', 'color' => 'bg-purple-100 text-purple-800 border-purple-200'],
                'entregada' => ['label' => 'Entregada', 'icon' => '📦', 'color' => 'bg-teal-100 text-teal-800 border-teal-200'],
            ];
        @endphp

        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Estados de Planillas</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-3">
                @foreach ($estadosConfig as $estado => $config)
                    @php
                        $count = $estadosCounts->get($estado, 0);
                        $porcentaje = $planillas->count() > 0 ? round(($count / $planillas->count()) * 100) : 0;
                    @endphp
                    @if ($count > 0)
                        <div class="border {{ $config['color'] }} rounded-lg p-3">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <span class="text-lg">{{ $config['icon'] }}</span>
                                    <span class="font-medium text-sm">{{ $config['label'] }}</span>
                                </div>
                                <span class="font-bold text-lg">{{ $count }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
                                <div class="h-2 rounded-full transition-all duration-300 {{ str_replace(['text-', 'border-'], ['bg-', 'bg-'], $config['color']) }}"
                                     style="width: {{ $porcentaje }}%"></div>
                            </div>
                            <p class="text-xs text-gray-600 text-center">{{ $porcentaje }}%</p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

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
                                            // Recopilar obras y clientes desde dos fuentes:
                                            // 1. Desde salidaClientes (relación directa)
                                            $obrasDesdeRelacion = $salida->salidaClientes->pluck('obra.obra')->filter();
                                            $clientesDesdeRelacion = $salida->salidaClientes->pluck('cliente.empresa')->filter();

                                            // 2. Desde paquetes asignados
                                            $obrasDesPaquetes = $salida->paquetes->pluck('planilla.obra.obra')->filter();
                                            $clientesDesdePaquetes = $salida->paquetes->pluck('planilla.cliente.empresa')->filter();

                                            // Combinar ambas fuentes y eliminar duplicados
                                            $obras = $obrasDesdeRelacion->concat($obrasDesPaquetes)->unique()->filter();
                                            $clientes = $clientesDesdeRelacion->concat($clientesDesdePaquetes)->unique()->filter();
                                        @endphp
                                        @if($obras->isNotEmpty())
                                            <p class="truncate" title="{{ $obras->implode(', ') }}">🏗️ {{ $obras->implode(', ') }}</p>
                                        @else
                                            <p class="text-gray-400 italic text-xs">Sin obra asignada</p>
                                        @endif
                                        @if($clientes->isNotEmpty())
                                            <p class="truncate" title="{{ $clientes->implode(', ') }}">👤 {{ $clientes->implode(', ') }}</p>
                                        @else
                                            <p class="text-gray-400 italic text-xs">Sin cliente asignado</p>
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
                                            <div class="text-xs text-gray-500 mt-1 border-t border-gray-200 pt-1">
                                                <div class="truncate" title="{{ $paquete->planilla->obra->obra ?? 'N/A' }}">🏗️ {{ $paquete->planilla->obra->obra ?? 'N/A' }}</div>
                                                <div class="truncate" title="{{ $paquete->planilla->cliente->empresa ?? 'N/A' }}">👤 {{ $paquete->planilla->cliente->empresa ?? 'N/A' }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        {{-- Columna de Paquetes Disponibles --}}
                        <div class="bg-gray-50 border-2 border-gray-300 rounded-lg p-4 min-h-[400px]">
                            <div class="mb-3">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="font-semibold text-gray-900">📋 Paquetes Disponibles</div>
                                    <button type="button" id="btn-toggle-paquetes"
                                            onclick="event.preventDefault(); event.stopPropagation(); toggleFiltroPaquetes();"
                                            title="{{ $mostrarTodosPaquetes ? 'Mostrar solo paquetes de las ' . $planillas->count() . ' planillas que estás gestionando' : 'Mostrar también paquetes pendientes de otras planillas (para mezclar salidas)' }}"
                                            class="text-xs px-3 py-1.5 rounded-md transition-colors shadow-sm font-medium {{ $mostrarTodosPaquetes ? 'bg-orange-500 hover:bg-orange-600 text-white' : 'bg-blue-500 hover:bg-blue-600 text-white' }}">
                                        @if($mostrarTodosPaquetes)
                                            <span class="flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                                </svg>
                                                Solo estas planillas
                                            </span>
                                        @else
                                            <span class="flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                Incluir otros paquetes
                                            </span>
                                        @endif
                                    </button>
                                </div>
                                <div class="bg-blue-50 border border-blue-200 rounded-md px-3 py-2 mb-3">
                                    <p class="text-xs text-blue-800">
                                        @if($mostrarTodosPaquetes)
                                            <strong>🌐 Mostrando:</strong> Todos los paquetes pendientes sin asignar ({{ $paquetesTodos->count() }} total)
                                        @else
                                            <strong>📋 Mostrando:</strong> Solo paquetes de las {{ $planillas->count() }} planillas seleccionadas ({{ $paquetesFiltrados->count() }} paquetes)
                                        @endif
                                    </p>
                                </div>

                                {{-- Filtros adicionales --}}
                                <div class="space-y-2 mb-3" wire:ignore>
                                    {{-- Filtro por Obra --}}
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">🏗️ Filtrar por Obra</label>
                                        <select id="filtro-obra"
                                                onchange="aplicarFiltros()"
                                                class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">-- Todas las obras --</option>
                                        </select>
                                    </div>

                                    {{-- Filtro por Cliente --}}
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">👤 Filtrar por Cliente</label>
                                        <select id="filtro-cliente"
                                                onchange="aplicarFiltros()"
                                                class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">-- Todos los clientes --</option>
                                        </select>
                                    </div>

                                    {{-- Filtro por Planilla --}}
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">📄 Filtrar por Planilla</label>
                                        <select id="filtro-planilla"
                                                onchange="aplicarFiltros()"
                                                class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">-- Todas las planillas --</option>
                                        </select>
                                    </div>

                                    {{-- Botón limpiar filtros --}}
                                    <button type="button"
                                            onclick="event.preventDefault(); limpiarFiltros();"
                                            class="w-full text-xs px-2 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                                        🔄 Limpiar Filtros
                                    </button>
                                </div>
                            </div>
                            <div class="paquetes-zona drop-zone bg-white rounded border-2 border-dashed border-gray-400 p-2 min-h-[300px]"
                                data-salida-id="null"
                                wire:ignore.self>
                                @foreach ($paquetesDisponibles as $paquete)
                                    <div class="paquete-item bg-white border border-gray-300 rounded p-2 mb-2 cursor-move hover:shadow-md transition-shadow"
                                        draggable="true"
                                        data-paquete-id="{{ $paquete->id }}"
                                        data-peso="{{ $paquete->peso }}"
                                        data-obra="{{ $paquete->planilla->obra->obra ?? '' }}"
                                        data-obra-id="{{ $paquete->planilla->obra_id ?? '' }}"
                                        data-cliente="{{ $paquete->planilla->cliente->empresa ?? '' }}"
                                        data-cliente-id="{{ $paquete->planilla->cliente_id ?? '' }}"
                                        data-planilla="{{ $paquete->planilla->codigo ?? '' }}"
                                        data-planilla-id="{{ $paquete->planilla_id ?? '' }}">
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
                                        <div class="text-xs text-gray-500 mt-1 border-t border-gray-200 pt-1">
                                            <div class="truncate" title="{{ $paquete->planilla->obra->obra ?? 'N/A' }}">🏗️ {{ $paquete->planilla->obra->obra ?? 'N/A' }}</div>
                                            <div class="truncate" title="{{ $paquete->planilla->cliente->empresa ?? 'N/A' }}">👤 {{ $paquete->planilla->cliente->empresa ?? 'N/A' }}</div>
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
                <div id="canvas-dibujo" class="border max-w-full h-auto"></div>
            </div>
        </div>
    </div>

    @php
        $cfg = [
            'csrf' => csrf_token(),
            'planillasIds' => $planillas->pluck('id')->toArray(),
            'empresas' => $empresas,
            'camiones' => $camiones,
            'mostrarTodosPaquetes' => $mostrarTodosPaquetes,
            'routes' => [
                'crearSalidasVacias' => route('salidas.crearSalidasVaciasMasivo'),
                'guardarAsignacionesPaquetes' => route('planificacion.guardarAsignacionesPaquetes'),
                'recargarVista' => route('salidas-ferralla.gestionar-salidas', ['planillas' => implode(',', $planillas->pluck('id')->toArray())]),
            ],
        ];

        // Función para mapear paquetes con elementos (igual que en PaquetesTable)
        $mapearPaqueteConElementos = function($paquete) {
            $etiquetas = [];
            foreach ($paquete->etiquetas ?? [] as $etiqueta) {
                $elementos = [];
                foreach ($etiqueta->elementos ?? [] as $elemento) {
                    $elementos[] = [
                        'id' => $elemento->id,
                        'dimensiones' => $elemento->dimensiones, // STRING directo de la BD
                    ];
                }
                $etiquetas[] = [
                    'elementos' => $elementos,
                ];
            }
            return [
                'id' => $paquete->id,
                'codigo' => $paquete->codigo,
                'planilla_id' => $paquete->planilla_id,
                'planilla_codigo' => $paquete->planilla->codigo ?? 'N/A',
                'obra' => $paquete->planilla->obra->obra ?? 'N/A',
                'cliente' => $paquete->planilla->cliente->empresa ?? 'N/A',
                'peso' => $paquete->peso,
                'etiquetas' => $etiquetas,
            ];
        };

        // Preparar paquetes filtrados (obra/cliente)
        $paquetesFiltradosJS = $paquetesFiltrados->map($mapearPaqueteConElementos);

        // Preparar todos los paquetes
        $paquetesTodosJS = $paquetesTodos->map($mapearPaqueteConElementos);

        // Combinar todos los paquetes (disponibles + asignados a salidas) con sus elementos
        $todosPaquetes = $paquetesDisponibles->concat($salidasExistentes->flatMap->paquetes)
            ->unique('id')
            ->map(function($paquete) use ($mapearPaqueteConElementos) {
                return $mapearPaqueteConElementos($paquete);
            });

    @endphp

    {{-- Estilos para soporte táctil --}}
    <style>
        /* Mejorar experiencia táctil en móviles */
        .paquete-item {
            touch-action: none;
            user-select: none;
            -webkit-user-select: none;
            transition: opacity 0.2s ease;
        }

        /* Elemento fantasma que sigue el dedo */
        .ghost-dragging {
            transition: none !important;
            will-change: transform;
            border-radius: 0.5rem;
        }

        .drop-zone {
            transition: background-color 0.2s ease;
            min-height: 100px;
        }

        /* Hacer las zonas de drop más visibles en móvil */
        @media (max-width: 768px) {
            .drop-zone {
                min-height: 150px;
                border-width: 3px;
            }

            .paquete-item {
                cursor: grab;
                margin-bottom: 0.75rem;
            }

            .paquete-item:active {
                cursor: grabbing;
            }
        }

        /* Desktop */
        @media (min-width: 769px) {
            .paquete-item {
                cursor: move;
            }
        }
    </style>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Configuración de la aplicación
        window.AppGestionSalidas = @json($cfg);

        // Todos los paquetes (para visualización de dibujos)
        window.paquetes = @json($todosPaquetes);

        // Paquetes filtrados (solo obra/cliente)
        window.paquetesFiltrados = @json($paquetesFiltradosJS);

        // Todos los paquetes pendientes (sin filtro)
        window.paquetesTodos = @json($paquetesTodosJS);
    </script>

    <script src="{{ asset('js/gestion-salidas.js') }}"></script>
    <script src="{{ asset('js/elementosJs/figuraElemento.js') }}"></script>

    {{-- Debug y inicialización de filtros --}}
    <script data-navigate-once>
        function initGestionSalidasPage() {
            console.log('🔍 DEBUG: Verificando elementos...');

            const selectObra = document.getElementById('filtro-obra');
            const selectCliente = document.getElementById('filtro-cliente');
            const selectPlanilla = document.getElementById('filtro-planilla');
            const paquetes = document.querySelectorAll('.paquetes-zona[data-salida-id="null"] .paquete-item');

            console.log('Selector Obra encontrado:', selectObra !== null);
            console.log('Selector Cliente encontrado:', selectCliente !== null);
            console.log('Selector Planilla encontrado:', selectPlanilla !== null);
            console.log('Paquetes encontrados:', paquetes.length);

            if (paquetes.length > 0) {
                const primerPaquete = paquetes[0];
                console.log('Datos del primer paquete:', {
                    obra: primerPaquete.dataset.obra,
                    cliente: primerPaquete.dataset.cliente,
                    planilla: primerPaquete.dataset.planilla
                });
            }

            // Forzar inicialización de filtros después de un pequeño delay
            setTimeout(() => {
                if (typeof inicializarFiltros === 'function') {
                    console.log('🔄 Forzando inicialización de filtros...');
                    inicializarFiltros();
                } else {
                    console.error('❌ Función inicializarFiltros no disponible');
                }
            }, 500);
        }

        // Ejecutar en DOMContentLoaded y en navegación de Livewire
        document.addEventListener('DOMContentLoaded', initGestionSalidasPage);
        document.addEventListener('livewire:navigated', initGestionSalidasPage);
    </script>
</x-app-layout>
