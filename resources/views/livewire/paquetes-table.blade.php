<div>
    <div class="w-full p-4 sm:p-2">
        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

        <!-- Tabla con filtros Livewire -->
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[1600px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white text-10">
                    <tr class="text-center text-xs uppercase">
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('id')">
                            ID @if ($sort === 'id')
                                {{ $order === 'asc' ? '▲' : '▼' }}
                            @endif
                        </th>
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('codigo')">
                            Código @if ($sort === 'codigo')
                                {{ $order === 'asc' ? '▲' : '▼' }}
                            @endif
                        </th>
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('planilla_id')">
                            Planilla @if ($sort === 'planilla_id')
                                {{ $order === 'asc' ? '▲' : '▼' }}
                            @endif
                        </th>
                        <th class="p-2 border">Cód. Obra</th>
                        <th class="p-2 border">Obra</th>
                        <th class="p-2 border">Cód. Cliente</th>
                        <th class="p-2 border">Cliente</th>
                        <th class="p-2 border">Nave</th>
                        <th class="p-2 border">Ubicación</th>
                        <th class="p-2 border">Localización</th>
                        <th class="p-2 border">Usuario</th>
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('estado')">
                            Estado @if ($sort === 'estado')
                                {{ $order === 'asc' ? '▲' : '▼' }}
                            @endif
                        </th>
                        <th class="p-2 border">Elementos</th>
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('peso')">
                            Peso (Kg) @if ($sort === 'peso')
                                {{ $order === 'asc' ? '▲' : '▼' }}
                            @endif
                        </th>
                        <th class="p-2 border cursor-pointer" wire:click="sortBy('created_at')">
                            Fecha Creación @if ($sort === 'created_at')
                                {{ $order === 'asc' ? '▲' : '▼' }}
                            @endif
                        </th>
                        <th class="p-2 border">Fecha Límite Reparto</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>

                    <tr class="text-center text-xs uppercase">
                        <th class="p-2 border">
                            <input type="text" wire:model.live.debounce.300ms="paquete_id"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="ID...">
                        </th>
                        <th class="p-2 border">
                            <input type="text" wire:model.live.debounce.300ms="codigo"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Código...">
                        </th>
                        <th class="p-2 border">
                            <input type="text" wire:model.live.debounce.300ms="planilla"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Planilla...">
                        </th>
                        <th class="p-2 border">
                            <input type="text" wire:model.live.debounce.300ms="cod_obra"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Cód. Obra...">
                        </th>
                        <th class="p-2 border">
                            <input type="text" wire:model.live.debounce.300ms="nom_obra"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Obra...">
                        </th>
                        <th class="p-2 border">
                            <input type="text" wire:model.live.debounce.300ms="codigo_cliente"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Cód. Cliente...">
                        </th>
                        <th class="p-2 border">
                            <input type="text" wire:model.live.debounce.300ms="cliente"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Cliente...">
                        </th>
                        <th class="p-2 border">
                            <input type="text" wire:model.live.debounce.300ms="nave"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Nave...">
                        </th>
                        <th class="p-2 border">
                            <input type="text" wire:model.live.debounce.300ms="ubicacion"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Ubicación...">
                        </th>
                        <th class="p-2 border"></th> {{-- Localización (sin filtro) --}}
                        <th class="p-2 border"></th> {{-- Usuario (sin filtro) --}}
                        <th class="p-2 border">
                            <select wire:model.live="estado"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                                <option value="">Todos</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="asignado_a_salida">Asignado</option>
                                <option value="en_reparto">En Reparto</option>
                                <option value="enviado">Enviado</option>
                            </select>
                        </th>
                        <th class="p-2 border"></th> {{-- Elementos --}}
                        <th class="p-2 border"></th> {{-- Peso --}}
                        <th class="p-2 border">
                            <input type="date" wire:model.live.debounce.300ms="created_at"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                        </th>
                        <th class="p-2 border">
                            <input type="date" wire:model.live.debounce.300ms="fecha_limite"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                        </th>
                        <th class="p-2 border text-center align-middle">
                            <div class="flex justify-center gap-2 items-center h-full">
                                {{-- ♻️ Botón reset --}}
                                <button wire:click="limpiarFiltros"
                                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                                    title="Restablecer filtros">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                                    </svg>
                                </button>
                            </div>
                        </th>
                    </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">
                    @forelse ($paquetes as $paquete)
                        <tr wire:key="paquete-{{ $paquete->id }}"
                            class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 transition-colors text-xs uppercase">
                            <td class="p-2 text-center border">{{ $paquete->id }}</td>
                            <td class="p-2 text-center border">{{ $paquete->codigo }}</td>
                            <td class="p-2 text-center border">
                                <a href="{{ route('planillas.index', ['planilla_id' => $paquete->planilla->id]) }}"
                                    wire:navigate class="text-blue-500 hover:underline">
                                    {{ $paquete->planilla->codigo_limpio }}
                                </a>
                            </td>
                            <td class="p-2 text-center border">{{ $paquete->planilla->obra->cod_obra ?? '-' }}</td>
                            <td class="p-2 text-center border">{{ $paquete->planilla->obra->obra ?? '-' }}</td>
                            <td class="p-2 text-center border">{{ $paquete->planilla->cliente->codigo ?? '-' }}</td>
                            <td class="p-2 text-center border">{{ $paquete->planilla->cliente->empresa ?? '-' }}</td>
                            <td class="p-2 text-center border">{{ $paquete->nave->obra ?? '-' }}</td>
                            <td class="p-2 text-center border">{{ $paquete->ubicacion->nombre ?? '-' }}</td>
                            <td class="p-2 text-center border">
                                @if ($paquete->localizacionPaquete)
                                    <span class="text-xs text-green-600 font-medium" title="X1:{{ $paquete->localizacionPaquete->x1 }} Y1:{{ $paquete->localizacionPaquete->y1 }} X2:{{ $paquete->localizacionPaquete->x2 }} Y2:{{ $paquete->localizacionPaquete->y2 }}">
                                        ({{ $paquete->localizacionPaquete->x1 }},{{ $paquete->localizacionPaquete->y1 }}) - ({{ $paquete->localizacionPaquete->x2 }},{{ $paquete->localizacionPaquete->y2 }})
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">Sin asignar</span>
                                @endif
                            </td>
                            <td class="p-2 text-center border">{{ $paquete->user->name ?? '-' }}</td>
                            <td class="p-2 text-center border">
                                @php
                                    $estadoBadge = match ($paquete->estado) {
                                        'pendiente' => [
                                            'bg' => 'bg-yellow-100',
                                            'text' => 'text-yellow-800',
                                            'label' => 'Pendiente',
                                            'icon' => '⏳',
                                        ],
                                        'asignado_a_salida' => [
                                            'bg' => 'bg-blue-100',
                                            'text' => 'text-blue-800',
                                            'label' => 'Asignado',
                                            'icon' => '📦',
                                        ],
                                        'en_reparto' => [
                                            'bg' => 'bg-purple-100',
                                            'text' => 'text-purple-800',
                                            'label' => 'En Reparto',
                                            'icon' => '🚚',
                                        ],
                                        'enviado' => [
                                            'bg' => 'bg-green-100',
                                            'text' => 'text-green-800',
                                            'label' => 'Enviado',
                                            'icon' => '✅',
                                        ],
                                        default => [
                                            'bg' => 'bg-gray-100',
                                            'text' => 'text-gray-800',
                                            'label' => ucfirst($paquete->estado ?? '-'),
                                            'icon' => '❓',
                                        ],
                                    };
                                @endphp
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $estadoBadge['bg'] }} {{ $estadoBadge['text'] }}">
                                    {{ $estadoBadge['icon'] }} {{ $estadoBadge['label'] }}
                                </span>
                            </td>
                            <td class="p-2 text-center border">
                                @if ($paquete->etiquetas->isNotEmpty())
                                    @foreach ($paquete->etiquetas as $etiqueta)
                                        <p class="font-semibold text-blue-700">
                                            🏷️ <a href="{{ route('etiquetas.index', ['id' => $etiqueta->id]) }}"
                                                wire:navigate class="hover:underline">
                                                {{ $etiqueta->nombre }}{{ $etiqueta->etiqueta_sub_id }}
                                            </a> –
                                            {{ $etiqueta->peso_kg }}
                                        </p>
                                        @if ($etiqueta->elementos->isNotEmpty())
                                            <ul class="ml-2 text-xs text-gray-700">
                                                @foreach ($etiqueta->elementos as $elemento)
                                                    <li>
                                                        <a href="{{ route('elementos.index', ['id' => $elemento->id]) }}"
                                                            wire:navigate class="text-green-600 hover:underline">
                                                            {{ $elemento->codigo }} – {{ $elemento->figura }}
                                                            – {{ $elemento->peso_kg }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="ml-2 text-gray-500 text-xs">Sin elementos registrados</p>
                                        @endif
                                        <hr>
                                    @endforeach
                                @else
                                    <span class="text-gray-500">Vacío</span>
                                @endif
                            </td>
                            <td class="p-2 text-center border">{{ $paquete->peso }} Kg</td>
                            <td class="p-2 text-center border">{{ $paquete->created_at->format('d/m/Y H:i') }}</td>
                            <td class="p-2 text-center border">
                                {{ optional($paquete->planilla->fecha_estimada_reparto)->format('d/m/Y') ?? 'No disponible' }}
                            </td>
                            <td class="p-2 text-center border">
                                <div class="flex flex-row justify-center items-center gap-3">
                                    {{-- Botón QR --}}
                                    <button
                                        class="btn-qr-paquete w-6 h-6 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center"
                                        data-codigo="{{ $paquete->codigo }}"
                                        data-planilla="{{ $paquete->planilla->codigo_limpio ?? '' }}"
                                        data-cliente="{{ $paquete->planilla->cliente->empresa ?? '' }}"
                                        data-obra="{{ $paquete->planilla->obra->obra ?? '' }}"
                                        data-descripcion="{{ $paquete->planilla->descripcion ?? '' }}"
                                        data-seccion="{{ $paquete->planilla->seccion ?? '' }}"
                                        data-ensamblado="{{ $paquete->planilla->ensamblado ?? '' }}"
                                        data-peso="{{ number_format($paquete->peso ?? 0, 2, ',', '.') }}"
                                        data-etiquetas="{{ $paquete->etiquetas->pluck('etiqueta_sub_id')->filter()->implode(', ') }}"
                                        title="Imprimir QR">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4 4h4v4H4V4zm6 0h4v4h-4V4zm6 0h4v4h-4V4zM4 10h4v4H4v-4zm6 10h4v-4h-4v4zm6 0h4v-4h-4v4z" />
                                        </svg>
                                    </button>

                                    {{-- Botón Ver --}}
                                    <a href="#"
                                        class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center abrir-modal-paquete"
                                        data-paquete-id="{{ $paquete->id }}" title="Ver dibujo del paquete">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>

                                    {{-- Botón Ver ubicación en mapa --}}
                                    @if ($paquete->nave_id)
                                        <button type="button"
                                            class="w-6 h-6 bg-purple-100 text-purple-600 rounded hover:bg-purple-200 flex items-center justify-center btn-ver-mapa-paquete"
                                            data-nave-id="{{ $paquete->nave_id }}"
                                            data-paquete-codigo="{{ $paquete->codigo }}"
                                            data-paquete-id="{{ $paquete->id }}"
                                            title="Ver ubicación en mapa">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                            </svg>
                                        </button>
                                    @endif

                                    {{-- Botón eliminar --}}
                                    <x-tabla.boton-eliminar :action="route('paquetes.destroy', $paquete->id)" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="17" class="text-center py-4 text-gray-500">No hay paquetes registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación Livewire -->
        {{ $paquetes->links() }}
    </div>

    {{-- Modal para visualizar elementos del paquete --}}
    <div id="modal-dibujo" class="hidden fixed inset-0 flex justify-center items-center p-4 pointer-events-none"
        wire:ignore>
        <div
            class="bg-white p-4 sm:p-6 rounded-lg w-full sm:w-[800px] md:w-[900px] lg:w-[1000px] max-w-[95vw] max-h-[90vh] flex flex-col shadow-lg relative pointer-events-auto border border-gray-300">
            <button id="cerrar-modal" class="absolute top-2 right-2 text-red-600 hover:bg-red-100">✖</button>

            <h2 class="text-xl font-semibold mb-4 text-center">Elementos del paquete</h2>

            <div class="overflow-y-auto flex-1 min-h-0" style="max-height: 75vh;">
                <div id="canvas-dibujo" class="border max-w-full h-auto"></div>
            </div>
        </div>
    </div>

    {{-- CSS para el mapa --}}
    @once
        <link rel="stylesheet" href="{{ asset('css/localizaciones/styleLocIndex.css') }}">
    @endonce

    {{-- Modal para ver ubicación en mapa --}}
    <div id="modal-mapa-paquete" class="hidden fixed inset-0 z-50 flex justify-center items-center p-4 bg-black/50"
        wire:ignore>
        <div
            class="bg-white rounded-lg w-full max-w-[95vw] lg:max-w-[1200px] max-h-[90vh] flex flex-col shadow-2xl relative overflow-hidden">
            {{-- Header del modal --}}
            <div class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-purple-600 to-purple-700 text-white">
                <div>
                    <h2 class="text-lg font-bold">Ubicación del paquete</h2>
                    <p id="modal-mapa-paquete-info" class="text-sm text-purple-100"></p>
                </div>
                <button id="cerrar-modal-mapa" type="button"
                    class="p-2 rounded-full hover:bg-white/20 transition-colors" title="Cerrar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Contenedor del mapa con componente --}}
            <div id="modal-mapa-container" class="flex-1 min-h-0 p-2 relative overflow-hidden" style="height: 70vh; min-height: 400px;">
                <x-mapa-simple :nave-id="1" :modo-edicion="false" class="h-full w-full" />
            </div>
        </div>
    </div>

    <script>
        // Preparar datos de paquetes
        window.paquetes = @json($paquetesJson);
    </script>

    @push('scripts')
        <script>
            function inicializarPaquetes() {
                const modal = document.getElementById("modal-dibujo");
                const canvasContainer = document.getElementById("canvas-dibujo");
                const cerrar = document.getElementById("cerrar-modal");

                if (!modal || !canvasContainer) return;

                let timeoutCerrar = null;

                function abrirModal(ojo) {
                    if (timeoutCerrar) {
                        clearTimeout(timeoutCerrar);
                        timeoutCerrar = null;
                    }

                    const paqueteId = parseInt(ojo.dataset.paqueteId);
                    const paquete = window.paquetes.find(p => p.id === paqueteId);

                    if (!paquete) return;

                    // Obtener elementos del paquete
                    const elementos = [];
                    if (paquete.etiquetas && paquete.etiquetas.length > 0) {
                        paquete.etiquetas.forEach(etiqueta => {
                            if (etiqueta.elementos && etiqueta.elementos.length > 0) {
                                etiqueta.elementos.forEach(elemento => {
                                    elementos.push({
                                        id: elemento.id,
                                        dimensiones: elemento.dimensiones
                                    });
                                });
                            }
                        });
                    }

                    if (elementos.length === 0) return;

                    // Limpiar contenedor
                    canvasContainer.innerHTML = '';

                    // Crear contenedores para cada elemento
                    elementos.forEach((elemento) => {
                        const elementoDiv = document.createElement('div');
                        elementoDiv.id = `elemento-${elemento.id}`;
                        elementoDiv.style.width = '100%';
                        elementoDiv.style.height = '200px';
                        elementoDiv.style.border = '1px solid #e5e7eb';
                        elementoDiv.style.borderRadius = '4px';
                        elementoDiv.style.background = 'white';
                        elementoDiv.style.position = 'relative';
                        canvasContainer.appendChild(elementoDiv);
                    });

                    modal.classList.remove("hidden");

                    // Dibujar elementos
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            elementos.forEach((elemento) => {
                                if (typeof window.dibujarFiguraElemento === 'function') {
                                    window.dibujarFiguraElemento(`elemento-${elemento.id}`,
                                        elemento.dimensiones, null);
                                }
                            });
                        });
                    });
                }

                function cerrarModal() {
                    timeoutCerrar = setTimeout(() => {
                        modal.classList.add("hidden");
                    }, 100);
                }

                function mantenerModalAbierto() {
                    if (timeoutCerrar) {
                        clearTimeout(timeoutCerrar);
                        timeoutCerrar = null;
                    }
                }

                // Eliminar event listeners anteriores (si los hay) y agregar nuevos
                const ojos = document.querySelectorAll(".abrir-modal-paquete");
                ojos.forEach(ojo => {
                    // Clonar nodo para eliminar todos los event listeners
                    const nuevoOjo = ojo.cloneNode(true);
                    ojo.parentNode.replaceChild(nuevoOjo, ojo);
                });

                // Agregar nuevos event listeners
                document.querySelectorAll(".abrir-modal-paquete").forEach(ojo => {
                    ojo.addEventListener("mouseenter", () => abrirModal(ojo));
                    ojo.addEventListener("mouseleave", cerrarModal);
                    ojo.addEventListener("click", e => e.preventDefault());
                });

                // Event listeners del modal (solo una vez)
                if (!modal._initialized) {
                    modal.addEventListener("mouseenter", mantenerModalAbierto);
                    modal.addEventListener("mouseleave", cerrarModal);
                    modal._initialized = true;
                }

                if (cerrar && !cerrar._initialized) {
                    cerrar.addEventListener("click", () => {
                        if (timeoutCerrar) {
                            clearTimeout(timeoutCerrar);
                            timeoutCerrar = null;
                        }
                        modal.classList.add("hidden");
                    });
                    cerrar._initialized = true;
                }
            }

            function initPaquetesTablePage() {
                // Prevenir doble inicialización
                if (document.body.dataset.paquetesTablePageInit === 'true') return;

                console.log('🔍 Inicializando tabla de paquetes...');

                // Llamar a la función de inicialización
                if (typeof inicializarPaquetes === 'function') inicializarPaquetes();

                // Marcar como inicializado
                document.body.dataset.paquetesTablePageInit = 'true';
            }

            // Registrar en el sistema global
            window.pageInitializers.push(initPaquetesTablePage);

            // Configurar listeners
            document.addEventListener('livewire:navigated', initPaquetesTablePage);
            document.addEventListener('DOMContentLoaded', initPaquetesTablePage);

            // Limpiar flag antes de navegar
            document.addEventListener('livewire:navigating', () => {
                document.body.dataset.paquetesTablePageInit = 'false';
            });

            // =============================================
            // Modal de Mapa de Ubicación del Paquete
            // =============================================
            function inicializarModalMapaPaquete() {
                const modalMapa = document.getElementById('modal-mapa-paquete');
                const modalMapaContainer = document.getElementById('modal-mapa-container');
                const modalMapaInfo = document.getElementById('modal-mapa-paquete-info');
                const cerrarModalMapa = document.getElementById('cerrar-modal-mapa');

                if (!modalMapa || !modalMapaContainer) return;

                let currentNaveId = null;
                let pendingPaqueteCodigo = null;

                function abrirModalMapa(naveId, paqueteCodigo) {
                    modalMapaInfo.textContent = `Paquete: ${paqueteCodigo}`;
                    pendingPaqueteCodigo = paqueteCodigo;

                    // Obtener el componente mapa-simple
                    const mapaSimple = modalMapaContainer.querySelector('[data-mapa-simple]');

                    if (!mapaSimple) {
                        console.error('No se encontró el componente mapa-simple');
                        return;
                    }

                    // Mostrar el modal
                    modalMapa.classList.remove('hidden');

                    // Función para mostrar el paquete una vez cargado el mapa
                    const mostrarPaqueteEnMapa = () => {
                        let intentos = 0;
                        const maxIntentos = 50;

                        const intentar = () => {
                            intentos++;
                            if (typeof mapaSimple.mostrarPaquete === 'function') {
                                // Ocultar todos los paquetes primero
                                const grid = mapaSimple.querySelector('.cuadricula-mapa');
                                if (grid) {
                                    grid.querySelectorAll('.loc-paquete').forEach(p => p.style.display = 'none');
                                }

                                // Mostrar solo el paquete seleccionado
                                const resultado = mapaSimple.mostrarPaquete(pendingPaqueteCodigo);
                                if (resultado === false) {
                                    mostrarMensajeSinUbicacion(pendingPaqueteCodigo, naveId);
                                }
                            } else if (intentos < maxIntentos) {
                                setTimeout(intentar, 100);
                            }
                        };
                        intentar();
                    };

                    // Si es diferente nave, recargar el mapa
                    if (currentNaveId !== naveId) {
                        currentNaveId = naveId;

                        if (typeof mapaSimple.recargarMapa === 'function') {
                            mapaSimple.recargarMapa(naveId);
                            // Esperar a que cargue y luego mostrar el paquete
                            setTimeout(mostrarPaqueteEnMapa, 500);
                        } else {
                            // Si no tiene recargarMapa, esperar a que esté disponible
                            let intentos = 0;
                            const esperarRecarga = () => {
                                intentos++;
                                if (typeof mapaSimple.recargarMapa === 'function') {
                                    mapaSimple.recargarMapa(naveId);
                                    setTimeout(mostrarPaqueteEnMapa, 500);
                                } else if (intentos < 30) {
                                    setTimeout(esperarRecarga, 100);
                                }
                            };
                            esperarRecarga();
                        }
                    } else {
                        // Misma nave, solo mostrar el paquete
                        mostrarPaqueteEnMapa();
                    }
                }

                function mostrarMensajeSinUbicacion(paqueteCodigo, naveId) {
                    // Crear overlay con mensaje
                    let overlay = modalMapaContainer.querySelector('.mensaje-sin-ubicacion');
                    if (!overlay) {
                        overlay = document.createElement('div');
                        overlay.className = 'mensaje-sin-ubicacion absolute inset-0 flex items-center justify-center bg-white/90 z-50';
                        modalMapaContainer.appendChild(overlay);
                    }

                    overlay.innerHTML = `
                        <div class="text-center max-w-md p-6">
                            <div class="bg-yellow-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Sin ubicación asignada</h3>
                            <p class="text-gray-600 mb-4">El paquete <strong>${paqueteCodigo}</strong> no tiene una localización asignada en el mapa.</p>
                            <p class="text-sm text-gray-500">Puedes asignarle una ubicación desde el <a href="/mapa-paquetes?nave=${naveId}" class="text-purple-600 hover:underline font-medium">Mapa de Localizaciones</a>.</p>
                        </div>
                    `;
                    overlay.style.display = 'flex';
                }

                function inicializarMapaModal(mapId, data, paqueteCodigo) {
                    console.log('inicializarMapaModal llamado con mapId:', mapId);
                    const { ctx, localizacionesZonas, localizacionesMaquinas, paquetesConLocalizacion } = data;
                    const escenario = document.getElementById(`${mapId}-escenario`);
                    const grid = document.getElementById(`${mapId}-cuadricula`);

                    console.log('Elementos encontrados:', { escenario: !!escenario, grid: !!grid });

                    if (!escenario || !grid) {
                        console.error('No se encontraron los elementos del mapa:', mapId);
                        return;
                    }

                    const W = ctx.columnasReales;
                    const H = ctx.filasReales;
                    const isVertical = !ctx.estaGirado;
                    const viewCols = W;
                    const viewRows = H;

                    let cellSize = 15;
                    let zoomLevel = 1;

                    function realToViewRect(x1r, y1r, x2r, y2r) {
                        const x1 = Math.min(x1r, x2r);
                        const x2 = Math.max(x1r, x2r);
                        const y1 = Math.min(y1r, y2r);
                        const y2 = Math.max(y1r, y2r);

                        function mapPointToView(x, y) {
                            if (isVertical) return { x, y: (H - y + 1) };
                            return { x: y, y: x };
                        }

                        const p1 = mapPointToView(x1, y1);
                        const p2 = mapPointToView(x2, y1);
                        const p3 = mapPointToView(x1, y2);
                        const p4 = mapPointToView(x2, y2);

                        const xs = [p1.x, p2.x, p3.x, p4.x];
                        const ys = [p1.y, p2.y, p3.y, p4.y];

                        return {
                            x: Math.min(...xs),
                            y: Math.min(...ys),
                            w: (Math.max(...xs) - Math.min(...xs) + 1),
                            h: (Math.max(...ys) - Math.min(...ys) + 1),
                        };
                    }

                    function renderExistentes() {
                        grid.querySelectorAll('.loc-existente').forEach(el => {
                            const rect = realToViewRect(
                                +el.dataset.x1, +el.dataset.y1,
                                +el.dataset.x2, +el.dataset.y2
                            );
                            el.style.left = ((rect.x - 1) * cellSize) + 'px';
                            el.style.top = ((rect.y - 1) * cellSize) + 'px';
                            el.style.width = (rect.w * cellSize) + 'px';
                            el.style.height = (rect.h * cellSize) + 'px';
                        });
                    }

                    function updateMap() {
                        // Usar tamaño fijo de celda para mejor visualización en el modal
                        const baseCellSize = 12; // Tamaño base más grande para mejor visibilidad
                        cellSize = Math.max(8, baseCellSize * zoomLevel);

                        const gridWidth = viewCols * cellSize;
                        const gridHeight = viewRows * cellSize;

                        console.log('updateMap - grid size:', { gridWidth, gridHeight, cellSize, viewCols, viewRows });

                        grid.style.width = `${gridWidth}px`;
                        grid.style.height = `${gridHeight}px`;
                        grid.style.minWidth = `${gridWidth}px`;
                        grid.style.minHeight = `${gridHeight}px`;
                        grid.style.backgroundColor = '#ffffff';
                        grid.style.backgroundSize = `${cellSize}px ${cellSize}px`;
                        grid.style.backgroundImage = `
                            linear-gradient(to right, #e5e7eb 1px, transparent 1px),
                            linear-gradient(to bottom, #e5e7eb 1px, transparent 1px)
                        `;
                        grid.style.border = '1px solid #d1d5db';
                        grid.style.borderRadius = '4px';
                        grid.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';

                        renderExistentes();
                    }

                    function crearElemento(item, claseExtra, label) {
                        const div = document.createElement('div');
                        div.className = `loc-existente ${claseExtra}`;
                        div.dataset.id = item.id;
                        div.dataset.x1 = item.x1;
                        div.dataset.y1 = item.y1;
                        div.dataset.x2 = item.x2;
                        div.dataset.y2 = item.y2;
                        div.style.position = 'absolute';

                        if (label) {
                            const span = document.createElement('span');
                            span.className = 'loc-label';
                            span.textContent = label;
                            div.appendChild(span);
                        }

                        return div;
                    }

                    // Renderizar elementos
                    grid.innerHTML = '';

                    localizacionesMaquinas.forEach(loc => {
                        const div = crearElemento(loc, 'loc-maquina', loc.nombre);
                        div.style.backgroundColor = 'rgba(34, 197, 94, 0.2)';
                        div.style.border = '1px solid #22c55e';
                        div.style.borderRadius = '2px';
                        div.style.zIndex = '5';
                        grid.appendChild(div);
                    });

                    localizacionesZonas.forEach(loc => {
                        const tipo = loc.tipo.replace(/-/g, '_');
                        const div = crearElemento(loc, `loc-zona tipo-${tipo}`, loc.nombre);
                        div.style.backgroundColor = 'rgba(107, 114, 128, 0.15)';
                        div.style.border = '1px dashed #6b7280';
                        div.style.borderRadius = '2px';
                        div.style.zIndex = '1';
                        grid.appendChild(div);
                    });

                    // Renderizar paquetes (ocultos por defecto excepto el seleccionado)
                    let paqueteEncontrado = null;
                    console.log('Buscando paquete con código:', paqueteCodigo);
                    console.log('Paquetes con localización:', paquetesConLocalizacion);

                    paquetesConLocalizacion.forEach(paq => {
                        const tipo = paq.tipo_contenido || 'mixto';
                        const div = crearElemento(paq, `loc-paquete tipo-${tipo}`, '');
                        div.dataset.paqueteId = paq.id;
                        div.dataset.codigo = paq.codigo;

                        // Estilos base para paquetes
                        div.style.backgroundColor = 'rgba(168, 85, 247, 0.4)';
                        div.style.border = '2px solid #a855f7';
                        div.style.borderRadius = '4px';

                        // Solo mostrar el paquete que buscamos
                        if (paq.codigo === paqueteCodigo) {
                            console.log('¡Paquete encontrado!', paq);
                            div.style.display = 'flex';
                            div.style.backgroundColor = 'rgba(168, 85, 247, 0.6)';
                            div.style.border = '3px solid #9333ea';
                            div.style.boxShadow = '0 0 0 4px rgba(168, 85, 247, 0.3), 0 0 20px rgba(168, 85, 247, 0.5)';
                            div.style.zIndex = '50';
                            paqueteEncontrado = div;
                        } else {
                            div.style.display = 'none';
                        }

                        grid.appendChild(div);
                    });

                    console.log('Paquete encontrado div:', paqueteEncontrado);

                    // Zoom
                    const zoomInBtn = document.getElementById(`${mapId}-zoom-in`);
                    const zoomOutBtn = document.getElementById(`${mapId}-zoom-out`);

                    if (zoomInBtn) {
                        zoomInBtn.addEventListener('click', () => {
                            zoomLevel = Math.min(3, zoomLevel + 0.2);
                            updateMap();
                        });
                    }

                    if (zoomOutBtn) {
                        zoomOutBtn.addEventListener('click', () => {
                            zoomLevel = Math.max(0.5, zoomLevel - 0.2);
                            updateMap();
                        });
                    }

                    // Pan/Drag
                    let isPanning = false;
                    let panStartX = 0, panStartY = 0;
                    let panStartScrollLeft = 0, panStartScrollTop = 0;

                    escenario.addEventListener('mousedown', (e) => {
                        if (e.target.closest('.loc-existente') || e.target.closest('button')) return;
                        isPanning = true;
                        panStartX = e.clientX;
                        panStartY = e.clientY;
                        panStartScrollLeft = escenario.scrollLeft;
                        panStartScrollTop = escenario.scrollTop;
                        escenario.style.cursor = 'grabbing';
                        e.preventDefault();
                    });

                    escenario.addEventListener('mousemove', (e) => {
                        if (!isPanning) return;
                        escenario.scrollLeft = panStartScrollLeft - (e.clientX - panStartX);
                        escenario.scrollTop = panStartScrollTop - (e.clientY - panStartY);
                    });

                    escenario.addEventListener('mouseup', () => {
                        isPanning = false;
                        escenario.style.cursor = 'grab';
                    });

                    escenario.addEventListener('mouseleave', () => {
                        isPanning = false;
                        escenario.style.cursor = 'grab';
                    });

                    // Inicializar
                    updateMap();

                    // Centrar en el paquete encontrado
                    if (paqueteEncontrado) {
                        console.log('Paquete después de updateMap:', {
                            left: paqueteEncontrado.style.left,
                            top: paqueteEncontrado.style.top,
                            width: paqueteEncontrado.style.width,
                            height: paqueteEncontrado.style.height,
                            display: paqueteEncontrado.style.display,
                            x1: paqueteEncontrado.dataset.x1,
                            y1: paqueteEncontrado.dataset.y1,
                            x2: paqueteEncontrado.dataset.x2,
                            y2: paqueteEncontrado.dataset.y2
                        });

                        setTimeout(() => {
                            // Obtener posición del paquete
                            const paqLeft = parseFloat(paqueteEncontrado.style.left) || 0;
                            const paqTop = parseFloat(paqueteEncontrado.style.top) || 0;
                            const paqWidth = parseFloat(paqueteEncontrado.style.width) || 0;
                            const paqHeight = parseFloat(paqueteEncontrado.style.height) || 0;

                            // Calcular centro del paquete
                            const paqCenterX = paqLeft + paqWidth / 2;
                            const paqCenterY = paqTop + paqHeight / 2;

                            // Calcular scroll para centrar el paquete en el viewport
                            const scrollX = paqCenterX - escenario.clientWidth / 2;
                            const scrollY = paqCenterY - escenario.clientHeight / 2;

                            console.log('Centrando scroll:', {
                                paqCenterX, paqCenterY,
                                escenarioWidth: escenario.clientWidth,
                                escenarioHeight: escenario.clientHeight,
                                scrollX, scrollY
                            });

                            escenario.scrollTo({
                                left: Math.max(0, scrollX),
                                top: Math.max(0, scrollY),
                                behavior: 'smooth'
                            });
                        }, 200);
                    } else {
                        console.warn('No se encontró el paquete en el mapa');
                    }

                    // Exponer funciones en el contenedor
                    const mapaSimple = modalMapaContainer.querySelector('[data-mapa-simple]');
                    if (mapaSimple) {
                        mapaSimple.mostrarPaquete = function(codigo) {
                            grid.querySelectorAll('.loc-paquete').forEach(p => {
                                if (p.dataset.codigo === codigo) {
                                    p.style.display = 'flex';
                                    p.classList.add('loc-paquete--highlight');
                                    // Centrar
                                    setTimeout(() => {
                                        const centerX = (parseFloat(p.style.left) + parseFloat(p.style.width) / 2) - escenario.clientWidth / 2;
                                        const centerY = (parseFloat(p.style.top) + parseFloat(p.style.height) / 2) - escenario.clientHeight / 2;
                                        escenario.scrollTo({
                                            left: Math.max(0, centerX),
                                            top: Math.max(0, centerY),
                                            behavior: 'smooth'
                                        });
                                    }, 50);
                                } else {
                                    p.style.display = 'none';
                                    p.classList.remove('loc-paquete--highlight');
                                }
                            });
                            // Actualizar info del modal
                            modalMapaInfo.textContent = `Paquete: ${codigo}`;
                        };
                    }
                }

                function cerrarModalMapaFn() {
                    modalMapa.classList.add('hidden');
                }

                // Event listener para cerrar
                if (cerrarModalMapa && !cerrarModalMapa._initialized) {
                    cerrarModalMapa.addEventListener('click', cerrarModalMapaFn);
                    cerrarModalMapa._initialized = true;
                }

                // Cerrar con click fuera
                if (!modalMapa._clickOutsideInit) {
                    modalMapa.addEventListener('click', (e) => {
                        if (e.target === modalMapa) {
                            cerrarModalMapaFn();
                        }
                    });
                    modalMapa._clickOutsideInit = true;
                }

                // Cerrar con Escape
                if (!window._modalMapaEscapeInit) {
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape') {
                            const modal = document.getElementById('modal-mapa-paquete');
                            if (modal && !modal.classList.contains('hidden')) {
                                modal.classList.add('hidden');
                            }
                        }
                    });
                    window._modalMapaEscapeInit = true;
                }

                // Event delegation para botones de mapa
                if (!window._modalMapaBtnInit) {
                    document.addEventListener('click', (e) => {
                        const btn = e.target.closest('.btn-ver-mapa-paquete');
                        if (btn) {
                            e.preventDefault();
                            const naveId = btn.dataset.naveId;
                            const paqueteCodigo = btn.dataset.paqueteCodigo;
                            if (naveId && paqueteCodigo) {
                                abrirModalMapa(parseInt(naveId), paqueteCodigo);
                            }
                        }
                    });
                    window._modalMapaBtnInit = true;
                }
            }

            // Inicializar modal de mapa
            inicializarModalMapaPaquete();
        </script>
    @endpush

    <!-- SCRIPT PARA IMPRIMIR QR -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="{{ asset('js/imprimirQrS.js') }}"></script>
    <script>
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-qr-paquete');
            if (btn) {
                generateAndPrintQRPaquete({
                    codigo: btn.dataset.codigo,
                    planilla: btn.dataset.planilla,
                    cliente: btn.dataset.cliente,
                    obra: btn.dataset.obra,
                    descripcion: btn.dataset.descripcion,
                    seccion: btn.dataset.seccion,
                    ensamblado: btn.dataset.ensamblado,
                    peso: btn.dataset.peso,
                    etiquetas: btn.dataset.etiquetas
                });
            }
        });
    </script>
    <script src="{{ asset('js/elementosJs/figuraElemento.js') }}" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</div>
