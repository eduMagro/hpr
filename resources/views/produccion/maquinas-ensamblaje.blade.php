<x-app-layout>
    <x-slot name="title">Ensamblaje - Control de Colas</x-slot>

    <div class="py-4 px-2 sm:px-4">
        <!-- Header con totales -->
        <div class="mb-4 bg-white rounded-lg shadow p-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-gray-800">Control de Ensambladoras</h1>
                    <p class="text-sm text-gray-500">Gestiona las colas de trabajo de las ensambladoras</p>
                </div>
                <div class="flex gap-4">
                    <div class="text-center px-4 py-2 bg-blue-50 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600">{{ $totales['en_proceso'] }}</div>
                        <div class="text-xs text-blue-600">En Proceso</div>
                    </div>
                    <div class="text-center px-4 py-2 bg-yellow-50 rounded-lg">
                        <div class="text-2xl font-bold text-yellow-600">{{ $totales['pendientes'] }}</div>
                        <div class="text-xs text-yellow-600">Pendientes</div>
                    </div>
                    <div class="text-center px-4 py-2 bg-green-50 rounded-lg">
                        <div class="text-2xl font-bold text-green-600">{{ $totales['completadas_hoy'] }}</div>
                        <div class="text-xs text-green-600">Hoy</div>
                    </div>
                </div>
            </div>
        </div>

        @if($maquinas->isEmpty())
            <div class="bg-yellow-100 text-yellow-800 p-4 rounded-lg">
                <p>No hay máquinas ensambladoras configuradas en el sistema.</p>
            </div>
        @else
            <!-- Grid de máquinas -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
                @foreach($maquinasConCola as $datos)
                    @php
                        $maquina = $datos['maquina'];
                        $enProceso = $datos['en_proceso'];
                        $colaPendiente = $datos['cola_pendiente'];
                        $completadasHoy = $datos['completadas_hoy'];

                        $estadoColor = match($maquina->estado) {
                            'activa' => 'bg-green-500',
                            'averiada' => 'bg-red-500',
                            'mantenimiento' => 'bg-orange-500',
                            'pausa' => 'bg-gray-400',
                            default => 'bg-gray-300',
                        };

                        $estadoIcono = match($maquina->estado) {
                            'activa' => '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>',
                            'averiada' => '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>',
                            'mantenimiento' => '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>',
                            default => '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd"/></svg>',
                        };
                    @endphp

                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <!-- Cabecera de la máquina -->
                        <div class="p-3 {{ $estadoColor }} text-white flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                {!! $estadoIcono !!}
                                <span class="font-bold">{{ $maquina->nombre }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm">
                                <span class="bg-white/20 px-2 py-1 rounded">{{ $colaPendiente->count() }} en cola</span>
                                <span class="bg-white/20 px-2 py-1 rounded">{{ $completadasHoy }} hoy</span>
                            </div>
                        </div>

                        <!-- Entidad en proceso -->
                        <div class="p-3 border-b">
                            <div class="text-xs text-gray-500 uppercase font-semibold mb-2">En Proceso</div>
                            @if($enProceso)
                                @php
                                    $entidad = $enProceso->entidad;
                                    $planilla = $entidad->planilla ?? null;
                                @endphp
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="font-bold text-blue-800">{{ $entidad->marca }}</span>
                                            <span class="text-sm text-blue-600 ml-2">x{{ $entidad->cantidad }}</span>
                                        </div>
                                        <span class="px-2 py-1 bg-blue-500 text-white text-xs rounded-full animate-pulse">
                                            Ensamblando
                                        </span>
                                    </div>
                                    @if($planilla)
                                        <div class="text-xs text-gray-600 mt-1">
                                            {{ $planilla->obra->nombre ?? 'Sin obra' }} - {{ $planilla->codigo ?? 'Sin código' }}
                                        </div>
                                    @endif
                                    @if($enProceso->fecha_inicio)
                                        <div class="text-xs text-gray-400 mt-1">
                                            Iniciado: {{ \Carbon\Carbon::parse($enProceso->fecha_inicio)->format('H:i') }}
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="text-center py-4 text-gray-400">
                                    <svg class="w-8 h-8 mx-auto mb-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <span class="text-sm">Sin trabajo activo</span>
                                </div>
                            @endif
                        </div>

                        <!-- Cola pendiente (máximo 5 visibles) -->
                        <div class="p-3">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-xs text-gray-500 uppercase font-semibold">Cola de Trabajo</div>
                                <a href="{{ route('ensamblaje.planificacion', ['maquina_id' => $maquina->id]) }}"
                                   class="text-xs text-blue-600 hover:text-blue-800 hover:underline">
                                    Ver completa
                                </a>
                            </div>

                            @if($colaPendiente->isEmpty())
                                <div class="text-center py-3 text-gray-400 text-sm">
                                    Cola vacía
                                </div>
                            @else
                                <div class="space-y-2 max-h-48 overflow-y-auto">
                                    @foreach($colaPendiente->take(5) as $index => $orden)
                                        @php
                                            $entidad = $orden->entidad;
                                            $planilla = $entidad->planilla ?? null;
                                            $estadoOrden = $orden->estado;
                                            $colorOrden = $estadoOrden === 'pausada' ? 'bg-yellow-50 border-yellow-200' : 'bg-gray-50 border-gray-200';
                                        @endphp
                                        <div class="{{ $colorOrden }} border rounded p-2 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="w-6 h-6 flex items-center justify-center bg-gray-200 text-gray-600 rounded-full text-xs font-bold">
                                                    {{ $orden->posicion }}
                                                </span>
                                                <div>
                                                    <div class="text-sm font-medium">{{ $entidad->marca }}</div>
                                                    <div class="text-xs text-gray-500">
                                                        {{ $planilla->obra->nombre ?? '' }}
                                                        @if($entidad->cantidad > 1)
                                                            <span class="text-gray-400">x{{ $entidad->cantidad }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            @if($estadoOrden === 'pausada')
                                                <span class="px-2 py-0.5 bg-yellow-200 text-yellow-800 text-xs rounded">Pausada</span>
                                            @endif
                                        </div>
                                    @endforeach

                                    @if($colaPendiente->count() > 5)
                                        <div class="text-center text-xs text-gray-500 py-1">
                                            +{{ $colaPendiente->count() - 5 }} más en cola
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <!-- Acciones -->
                        <div class="px-3 pb-3 flex gap-2">
                            <a href="{{ route('maquinas.show', $maquina->id) }}"
                               class="flex-1 text-center px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded transition-colors">
                                Ver Máquina
                            </a>
                            <a href="{{ route('ensamblaje.planificacion', ['maquina_id' => $maquina->id]) }}"
                               class="flex-1 text-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm rounded transition-colors">
                                Planificar
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Entidades listas sin asignar -->
            @if($entidadesListasSinAsignar->isNotEmpty())
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-3 bg-orange-500 text-white flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-bold">Entidades Listas Sin Asignar</span>
                        </div>
                        <span class="bg-white/20 px-2 py-1 rounded text-sm">{{ $entidadesListasSinAsignar->count() }} entidades</span>
                    </div>

                    <div class="p-4">
                        <p class="text-sm text-gray-600 mb-3">
                            Estas entidades tienen todos sus elementos fabricados y están listas para ensamblar,
                            pero no han sido asignadas a ninguna máquina.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2">
                            @foreach($entidadesListasSinAsignar->take(12) as $entidad)
                                @php
                                    $planilla = $entidad->planilla ?? null;
                                @endphp
                                <div class="bg-orange-50 border border-orange-200 rounded p-2">
                                    <div class="font-medium text-sm">{{ $entidad->marca }}</div>
                                    <div class="text-xs text-gray-600">
                                        {{ $planilla->obra->nombre ?? 'Sin obra' }}
                                        <span class="text-gray-400">x{{ $entidad->cantidad }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if($entidadesListasSinAsignar->count() > 12)
                            <div class="text-center mt-3">
                                <a href="{{ route('ensamblaje.planificacion') }}"
                                   class="text-orange-600 hover:text-orange-800 text-sm hover:underline">
                                    Ver todas las {{ $entidadesListasSinAsignar->count() }} entidades pendientes
                                </a>
                            </div>
                        @endif

                        <div class="mt-4 text-center">
                            <a href="{{ route('ensamblaje.planificacion') }}"
                               class="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Ir a Planificación
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
