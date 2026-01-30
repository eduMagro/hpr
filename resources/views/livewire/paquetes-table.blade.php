<div>
    <div class="w-full p-4 sm:p-2">
        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

        <!-- Tabla con filtros Livewire -->
        <div class="w-full overflow-x-auto bg-white dark:bg-gray-900 shadow-lg rounded-lg">
            <table class="table-global w-full min-w-[1600px]">
                <thead>
                    <tr class="text-center">
                        <x-tabla.encabezado-ordenable :sortActual="$sort" :orderActual="$order" campo="id" texto="ID" />
                        <x-tabla.encabezado-ordenable :sortActual="$sort" :orderActual="$order" campo="codigo" texto="Código" />
                        <x-tabla.encabezado-ordenable :sortActual="$sort" :orderActual="$order" campo="planilla_id" texto="Planilla" />
                        <th>Cód. Obra</th>
                        <th>Obra</th>
                        <th>Cód. Cliente</th>
                        <th>Cliente</th>
                        <th>Nave</th>
                        <th>Ubicación</th>
                        <th>Localización</th>
                        <th>Usuario</th>
                        <x-tabla.encabezado-ordenable :sortActual="$sort" :orderActual="$order" campo="estado" texto="Estado" />
                        <x-tabla.encabezado-ordenable :sortActual="$sort" :orderActual="$order" campo="salida" texto="Salida" />
                        <th>Elementos</th>
                        <x-tabla.encabezado-ordenable :sortActual="$sort" :orderActual="$order" campo="peso" texto="Peso (Kg)" />
                        <x-tabla.encabezado-ordenable :sortActual="$sort" :orderActual="$order" campo="created_at" texto="Fecha Creación" />
                        <th>Fecha Límite Reparto</th>
                        <th>Acciones</th>
                    </tr>

                    <x-tabla.filtro-row>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="paquete_id"
                                class="inline-edit-input" placeholder="ID...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="codigo"
                                class="inline-edit-input" placeholder="Código...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="planilla"
                                class="inline-edit-input" placeholder="Planilla...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="cod_obra"
                                class="inline-edit-input" placeholder="Cód. Obra...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="nom_obra"
                                class="inline-edit-input" placeholder="Obra...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="codigo_cliente"
                                class="inline-edit-input" placeholder="Cód. Cliente...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="cliente"
                                class="inline-edit-input" placeholder="Cliente...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="nave"
                                class="inline-edit-input" placeholder="Nave...">
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="ubicacion"
                                class="inline-edit-input" placeholder="Ubicación...">
                        </th>
                        <th></th> {{-- Localización (sin filtro) --}}
                        <th></th> {{-- Usuario (sin filtro) --}}
                        <th>
                            <select wire:model.live="estado" class="inline-edit-select">
                                <option value="">Todos</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="asignado_a_salida">Asignado</option>
                                <option value="en_reparto">En Reparto</option>
                                <option value="enviado">Enviado</option>
                            </select>
                        </th>
                        <th>
                            <input type="text" wire:model.live.debounce.300ms="salida"
                                class="inline-edit-input" placeholder="Salida...">
                        </th>
                        <th></th> {{-- Elementos --}}
                        <th></th> {{-- Peso --}}
                        <th>
                            <input type="date" wire:model.live.debounce.300ms="created_at"
                                class="inline-edit-input">
                        </th>
                        <th>
                            <input type="date" wire:model.live.debounce.300ms="fecha_limite"
                                class="inline-edit-input">
                        </th>
                        <th class="text-center align-middle">
                            <div class="flex justify-center gap-2 items-center h-full">
                                <button wire:click="limpiarFiltros"
                                    class="table-btn bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-600 dark:hover:bg-yellow-700 text-white"
                                    title="Restablecer filtros">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                                    </svg>
                                </button>
                            </div>
                        </th>
                    </x-tabla.filtro-row>
                </thead>

                <tbody>
                    @forelse ($paquetes as $paquete)
                        <x-tabla.row wire:key="paquete-{{ $paquete->id }}">
                            <td class="p-2 text-center border">{{ $paquete->id }}</td>
                            <td class="p-2 text-center border">{{ $paquete->codigo }}</td>
                            <td class="p-2 text-center border">
                                <a href="{{ route('planillas.index', ['codigo' => $paquete->planilla->codigo_limpio]) }}"
                                    class="text-blue-600 dark:text-blue-400 hover:underline">
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
                                    <span class="text-xs text-green-600 dark:text-green-400 font-medium" title="X1:{{ $paquete->localizacionPaquete->x1 }} Y1:{{ $paquete->localizacionPaquete->y1 }} X2:{{ $paquete->localizacionPaquete->x2 }} Y2:{{ $paquete->localizacionPaquete->y2 }}">
                                        ({{ $paquete->localizacionPaquete->x1 }},{{ $paquete->localizacionPaquete->y1 }}) - ({{ $paquete->localizacionPaquete->x2 }},{{ $paquete->localizacionPaquete->y2 }})
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">Sin asignar</span>
                                @endif
                            </td>
                            <td class="p-2 text-center border">{{ $paquete->user->name ?? '-' }}</td>
                            <td class="p-2 text-center border">
                                @php
                                    $estadoBadge = match ($paquete->estado) {
                                        'pendiente' => [
                                            'bg' => 'bg-yellow-100 dark:bg-yellow-900/50',
                                            'text' => 'text-yellow-800 dark:text-yellow-300',
                                            'label' => 'Pendiente',
                                            'icon' => '⏳',
                                        ],
                                        'asignado_a_salida' => [
                                            'bg' => 'bg-blue-100 dark:bg-blue-900/50',
                                            'text' => 'text-blue-800 dark:text-blue-300',
                                            'label' => 'Asignado',
                                            'icon' => '📦',
                                        ],
                                        'en_reparto' => [
                                            'bg' => 'bg-purple-100 dark:bg-purple-900/50',
                                            'text' => 'text-purple-800 dark:text-purple-300',
                                            'label' => 'En Reparto',
                                            'icon' => '🚚',
                                        ],
                                        'enviado' => [
                                            'bg' => 'bg-green-100 dark:bg-green-900/50',
                                            'text' => 'text-green-800 dark:text-green-300',
                                            'label' => 'Enviado',
                                            'icon' => '✅',
                                        ],
                                        default => [
                                            'bg' => 'bg-gray-100 dark:bg-gray-700',
                                            'text' => 'text-gray-800 dark:text-gray-300',
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
                                @if ($paquete->salida->first())
                                    <a href="{{ route('salidas-ferralla.show', $paquete->salida->first()->id) }}"
                                        wire:navigate
                                        class="text-purple-600 dark:text-purple-400 hover:underline font-medium">
                                        {{ $paquete->salida->first()->codigo_salida }}
                                    </a>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="p-2 text-center border">
                                @if ($paquete->etiquetas->isNotEmpty())
                                    @foreach ($paquete->etiquetas as $etiqueta)
                                        <p class="font-semibold text-blue-700 dark:text-blue-400">
                                            🏷️ <a href="{{ route('etiquetas.index', ['id' => $etiqueta->id]) }}"
                                                wire:navigate class="hover:underline">
                                                {{ $etiqueta->nombre }}{{ $etiqueta->etiqueta_sub_id }}
                                            </a> –
                                            {{ $etiqueta->peso_kg }}
                                        </p>
                                        @if ($etiqueta->elementos->isNotEmpty())
                                            <ul class="ml-2 text-xs text-gray-700 dark:text-gray-300">
                                                @foreach ($etiqueta->elementos as $elemento)
                                                    <li>
                                                        <a href="{{ route('elementos.index', ['id' => $elemento->id]) }}"
                                                            wire:navigate class="text-green-600 dark:text-green-400 hover:underline">
                                                            {{ $elemento->codigo }} – {{ $elemento->figura }}
                                                            – {{ $elemento->peso_kg }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="ml-2 text-gray-500 dark:text-gray-400 text-xs">Sin elementos registrados</p>
                                        @endif
                                        <hr class="dark:border-gray-700">
                                    @endforeach
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">Vacío</span>
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
                                        class="btn-qr-paquete table-btn bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-800"
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
                                        class="table-btn bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-800 abrir-modal-paquete"
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
                                            class="table-btn bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400 hover:bg-purple-200 dark:hover:bg-purple-800 btn-ver-mapa-paquete"
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
                        </x-tabla.row>
                    @empty
                        <tr>
                            <td colspan="17" class="text-center py-4 text-gray-500 dark:text-gray-400">No hay paquetes registrados</td>
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
            class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg w-full sm:w-[800px] md:w-[900px] lg:w-[1000px] max-w-[95vw] max-h-[90vh] flex flex-col shadow-lg relative pointer-events-auto border border-gray-300 dark:border-gray-600">
            <button id="cerrar-modal" class="absolute top-2 right-2 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 rounded p-1">✖</button>

            <h2 class="text-xl font-semibold mb-4 text-center text-gray-800 dark:text-gray-200">Elementos del paquete</h2>

            <div class="overflow-y-auto flex-1 min-h-0" style="max-height: 75vh;">
                <div id="canvas-dibujo" class="border dark:border-gray-600 max-w-full h-auto"></div>
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
            class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-[95vw] lg:max-w-[1200px] max-h-[90vh] flex flex-col shadow-2xl relative overflow-hidden">
            {{-- Header del modal --}}
            <div class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-purple-600 to-purple-700 dark:from-purple-700 dark:to-purple-800 text-white">
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
            <div id="modal-mapa-container" class="flex-1 min-h-0 p-2 relative overflow-hidden dark:bg-gray-900" style="height: 70vh; min-height: 400px;">
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
                    console.log('abrirModalMapa:', { naveId, paqueteCodigo, currentNaveId });
                    modalMapaInfo.textContent = `Paquete: ${paqueteCodigo}`;
                    pendingPaqueteCodigo = paqueteCodigo;

                    // Ocultar mensaje de sin ubicación previo
                    const overlayPrevio = modalMapaContainer.querySelector('.mensaje-sin-ubicacion');
                    if (overlayPrevio) overlayPrevio.style.display = 'none';

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
                            console.log('Intento', intentos, 'de mostrar paquete. mostrarPaquete disponible:', typeof mapaSimple.mostrarPaquete);

                            if (typeof mapaSimple.mostrarPaquete === 'function') {
                                // Ocultar todos los paquetes primero
                                const grid = mapaSimple.querySelector('.cuadricula-mapa');
                                console.log('Grid encontrado:', !!grid);

                                if (grid) {
                                    const paquetes = grid.querySelectorAll('.loc-paquete');
                                    console.log('Paquetes en el mapa:', paquetes.length);
                                    paquetes.forEach(p => {
                                        console.log('  - Paquete:', p.dataset.codigo);
                                    });
                                    paquetes.forEach(p => p.style.display = 'none');
                                }

                                // Mostrar solo el paquete seleccionado
                                console.log('Llamando mostrarPaquete con código:', pendingPaqueteCodigo);
                                const resultado = mapaSimple.mostrarPaquete(pendingPaqueteCodigo);
                                console.log('Resultado de mostrarPaquete:', resultado);

                                if (resultado === false) {
                                    mostrarMensajeSinUbicacion(pendingPaqueteCodigo, naveId);
                                }
                            } else if (intentos < maxIntentos) {
                                setTimeout(intentar, 100);
                            } else {
                                console.warn('Timeout esperando mostrarPaquete');
                            }
                        };
                        intentar();
                    };

                    // Siempre recargar el mapa con la nave correcta
                    console.log('Recargando mapa con naveId:', naveId);
                    currentNaveId = naveId;

                    // Esperar a que recargarMapa esté disponible
                    let intentosRecarga = 0;
                    const esperarYRecargar = () => {
                        intentosRecarga++;
                        console.log('Intento recarga', intentosRecarga, '- recargarMapa disponible:', typeof mapaSimple.recargarMapa);

                        if (typeof mapaSimple.recargarMapa === 'function') {
                            console.log('Ejecutando recargarMapa...');
                            mapaSimple.recargarMapa(naveId);
                            // Esperar más tiempo para que cargue completamente
                            setTimeout(mostrarPaqueteEnMapa, 1500);
                        } else if (intentosRecarga < 30) {
                            setTimeout(esperarYRecargar, 100);
                        } else {
                            console.error('Timeout esperando recargarMapa');
                        }
                    };
                    esperarYRecargar();
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

                function cerrarModalMapaFn() {
                    modalMapa.classList.add('hidden');
                    // Ocultar mensaje de sin ubicación si existe
                    const overlay = modalMapaContainer.querySelector('.mensaje-sin-ubicacion');
                    if (overlay) overlay.style.display = 'none';
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
    <script src="{{ asset('js/elementosJs/figuraElemento.js') . '?v=' . time() }}" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</div>
