<x-app-layout>
    <x-slot name="title">Máquinas - {{ config('app.name') }}</x-slot>

    <style>
        html {
            scroll-behavior: smooth;
        }
    </style>

    <div class="flex w-full">

        {{-- 🚀 MENÚ LATERAL FIJO --}}
        <aside role="navigation" class="fixed left-0 h-screen w-64 bg-white shadow-md z-20 overflow-y-auto">
            <x-tabla.boton-azul :href="route('maquinas.create')" class="m-4 w-[calc(100%-2rem)] text-center">
                ➕ Crear Nueva Máquina
            </x-tabla.boton-azul>

            <div class="p-4 border-t border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-3 px-2">Navegación por Máquina</h3>

                <ul class="space-y-1">
                    @foreach ($registrosMaquina as $maquina)
                        <li>
                            <a href="#maquina-{{ $maquina->id }}"
                                class="block px-3 py-2 text-sm rounded hover:bg-gray-100 hover:text-blue-800 font-medium transition-colors duration-200 truncate">
                                {{ $maquina->codigo }} — {{ $maquina->nombre }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </aside>

        <!-- Contenido principal -->
        <div class="flex-1 ml-64">

            @forelse($registrosMaquina as $maquina)
                <div id="maquina-{{ $maquina->id }}"
                    class="scroll-mt-28 bg-white border rounded-lg shadow-md mb-8 overflow-hidden">

                    <!-- Imagen -->
                    <div class="w-full h-64 bg-gray-100 flex items-center justify-center">
                        @if ($maquina->imagen)
                            <img src="{{ asset($maquina->imagen) }}" alt="Imagen de {{ $maquina->nombre }}"
                                class="object-contain h-full">
                        @else
                            <span class="text-gray-500">Sin imagen</span>
                        @endif
                    </div>

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

                        @php
                            $asignacionesHoy = $usuariosPorMaquina->get($maquina->id, collect());
                            $ordenTurno = ['noche' => 0, 'mañana' => 1, 'tarde' => 2];
                            $asignacionesOrdenadas = $asignacionesHoy->sortBy(function ($asig) use ($ordenTurno) {
                                $nombreTurno = strtolower($asig->turno->nombre ?? '');
                                return $ordenTurno[$nombreTurno] ?? 99;
                            });
                        @endphp

                        <div class="text-sm text-gray-700 mb-2">
                            <strong>Operarios en turno:</strong>
                            @if ($asignacionesOrdenadas->isEmpty())
                                <span class="text-gray-500">Ninguno</span>
                            @else
                                <ul class="list-disc pl-5">
                                    @foreach ($asignacionesOrdenadas as $asig)
                                        <li>
                                            {{ $asig->user->name }}
                                            <span class="text-gray-500 text-xs">
                                                ({{ ucfirst($asig->turno->nombre) }})
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

                        <!-- Subir imagen -->
                        <form action="{{ route('maquinas.imagen', $maquina->id) }}" method="POST"
                            enctype="multipart/form-data" class="mt-2">
                            @csrf
                            @method('PUT')

                            <div class="flex items-center gap-2">
                                <input type="file" name="imagen" accept="image/*"
                                    class="block w-full text-sm text-gray-600 border border-gray-300 rounded p-1 file:mr-2 file:py-1 file:px-3 file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                    required>
                                <button type="submit"
                                    class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                                    Subir
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Cola de planillas -->
                    @php
                        $elementos = $colaPorMaquina->get($maquina->id, collect());
                        $groupedByPlanilla = $elementos->groupBy(fn($e) => $e->planilla->id);
                    @endphp
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
        </div>
    </div>

    <!-- Paginación -->
    <div class="mt-6 flex justify-center">
        {{ $registrosMaquina->links('vendor.pagination.bootstrap-5') }}
    </div>

    <!-- Alpine.js -->
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</x-app-layout>
