<x-app-layout>
    <x-slot name="title">Máquinas - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Máquinas') }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <!-- Botón para crear una nueva máquina -->
        <div class="mb-6">
            <x-tabla.boton-azul :href="route('maquinas.create')">➕ Crear Nueva Máquina</x-tabla.boton-azul>
        </div>

        @forelse($registrosMaquina as $maquina)
            <div class="bg-white border rounded-lg shadow-md mb-8 overflow-hidden">
                <!-- Imagen -->
                <div class="w-full h-64 bg-gray-100 flex items-center justify-center">
                    @if ($maquina->imagen)
                        <img src="{{ asset($maquina->imagen) }}" alt="Imagen de {{ $maquina->nombre }}"
                            class="object-contain h-full">
                    @else
                        <span class="text-gray-500">Sin imagen</span>
                    @endif
                </div>
                <!-- Cola de planillas -->
                @php
                    $elementos = $colaPorMaquina->get($maquina->id, collect());
                    $groupedByPlanilla = $elementos->groupBy(fn($e) => $e->planilla->id);

                    // 🆕 Usuarios con turno hoy en esta máquina
                    $asignacionesHoy = $usuariosPorMaquina->get($maquina->id, collect());
                @endphp
                <!-- Datos principales -->
                <div class="p-4 space-y-2">
                    <h3 class="text-xl font-bold text-gray-800">
                        {{ $maquina->codigo }} — {{ $maquina->nombre }}
                    </h3>

                    <p class="text-sm text-gray-700">
                        Estado:
                        @php
                            $inProduction =
                                $maquina->tipo == 'ensambladora'
                                    ? $maquina->elementos_ensambladora > 0
                                    : $maquina->elementos_count > 0;
                        @endphp
                        <span class="{{ $inProduction ? 'text-green-600' : 'text-red-500' }}">
                            {{ $inProduction ? 'En producción' : 'Sin trabajo' }}
                        </span>
                    </p>
                    {{-- Operarios en turno hoy --}}
                    <div class="text-sm text-gray-700 mb-2">
                        <strong>Operarios en turno:</strong>
                        @if ($asignacionesHoy->isEmpty())
                            <span class="text-gray-500">Ninguno</span>
                        @else
                            <ul class="list-disc pl-5">
                                @foreach ($asignacionesHoy as $asig)
                                    <li>
                                        {{ $asig->user->name }}
                                        <span class="text-gray-500 text-xs">
                                            ({{ $asig->turno->nombre ?? 'Sin turno' }})
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <p class="text-sm text-gray-700">
                        Rango de diámetros: <strong>{{ $maquina->diametro_min }} mm</strong> -
                        <strong>{{ $maquina->diametro_max }} mm</strong>
                    </p>
                </div>



                <div class="px-4 pb-4">
                    <h4 class="font-semibold text-base text-gray-800 mb-2">
                        Cola de Planillas ({{ $groupedByPlanilla->count() }})
                    </h4>

                    @if ($groupedByPlanilla->isEmpty())
                        <p class="text-sm text-gray-500">No hay planillas en cola.</p>
                    @else
                        @foreach ($groupedByPlanilla as $planillaId => $items)
                            <div x-data="{ openPlanilla: false }" class="mb-3 border rounded-lg overflow-hidden">
                                <button @click="openPlanilla = !openPlanilla"
                                    class="w-full px-4 py-2 bg-gray-100 text-left flex justify-between items-center">
                                    <div>
                                        <strong>{{ $items->first()->planilla->codigo_limpio }}</strong>
                                        <span class="ml-2 text-sm text-gray-600">Entrega:
                                            {{ $items->first()->planilla->fecha_estimada_entrega }}</span>
                                    </div>
                                    <svg :class="openPlanilla ? 'transform rotate-180' : ''"
                                        class="h-5 w-5 transition-transform" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <ul x-show="openPlanilla" x-collapse class="bg-white px-4 py-2 space-y-1">
                                    @foreach ($items as $elemento)
                                        <li class="flex justify-between text-sm text-gray-700">
                                            <span>
                                                #{{ $elemento->id }} — {{ $elemento->figura }}
                                                ({{ ucfirst($elemento->estado) }})
                                            </span>
                                            <a href="{{ route('elementos.show', $elemento->id) }}"
                                                class="text-blue-600 hover:underline">Ver</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @empty
            <p class="text-gray-600">No hay máquinas disponibles.</p>
        @endforelse

        <!-- Paginación -->
        <div class="mt-6 flex justify-center">
            {{ $registrosMaquina->links('vendor.pagination.bootstrap-5') }}
        </div>
    </div>

    <!-- Alpine.js -->
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</x-app-layout>
