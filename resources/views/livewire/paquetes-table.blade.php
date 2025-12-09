<div>
    <div class="w-full p-4 sm:p-2">
        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

        <!-- Tabla con filtros Livewire -->
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[1600px] border border-gray-300 rounded-lg">
                <x-tabla.header>
                    <x-tabla.header-row>
                        <th class="p-2 cursor-pointer" wire:click="sortBy('id')" wire:navigate>
                            ID @if ($sort === 'id')
                                {{ $order === 'asc' ? '▲' : '▼' }}
                            @endif
                        </th>
                        <th class="p-2 cursor-pointer" wire:click="sortBy('codigo')" wire:navigate>
                            Código @if ($sort === 'codigo')
                                {{ $order === 'asc' ? '▲' : '▼' }}
                            @endif
                        </th>
                        <th class="p-2 cursor-pointer" wire:click="sortBy('planilla_id')" wire:navigate>
                            Planilla @if ($sort === 'planilla_id')
                                {{ $order === 'asc' ? '▲' : '▼' }}
                            @endif
                        </th>
                        <th class="p-2">Cód. Obra</th>
                        <th class="p-2">Obra</th>
                        <th class="p-2">Cód. Cliente</th>
                        <th class="p-2">Cliente</th>
                        <th class="p-2">Nave</th>
                        <th class="p-2">Ubicación</th>
                        <th class="p-2 cursor-pointer" wire:click="sortBy('estado')" wire:navigate>
                            Estado @if ($sort === 'estado')
                                {{ $order === 'asc' ? '▲' : '▼' }}
                            @endif
                        </th>
                        <th class="p-2">Elementos</th>
                        <th class="p-2 cursor-pointer" wire:click="sortBy('peso')" wire:navigate>
                            Peso (Kg) @if ($sort === 'peso')
                                {{ $order === 'asc' ? '▲' : '▼' }}
                            @endif
                        </th>
                        <th class="p-2 cursor-pointer" wire:click="sortBy('created_at')" wire:navigate>
                            Fecha Creación @if ($sort === 'created_at')
                                {{ $order === 'asc' ? '▲' : '▼' }}
                            @endif
                        </th>
                        <th class="p-2">Fecha Límite Reparto</th>
                        <th class="p-2">Acciones</th>

                    </x-tabla.header-row>
                    <x-tabla.filtro-row>
                        <th class="p-2 bg-gray-50">
                            <input type="text" wire:model.live.debounce.300ms="paquete_id"
                                class="w-full text-xs border border-gray-300 rounded px-2 py-2 text-gray-800 bg-gray-50 focus:border-gray-700 focus:ring-1 focus:ring-gray-600 focus:outline-none"
                                placeholder="ID...">
                        </th>
                        <th class="p-2 bg-gray-50">
                            <input type="text" wire:model.live.debounce.300ms="codigo"
                                class="w-full text-xs border border-gray-300 rounded px-2 py-2 text-gray-800 bg-gray-50 focus:border-gray-700 focus:ring-1 focus:ring-gray-600 focus:outline-none"
                                placeholder="Código...">
                        </th>
                        <th class="p-2 bg-gray-50">
                            <input type="text" wire:model.live.debounce.300ms="planilla"
                                class="w-full text-xs border border-gray-300 rounded px-2 py-2 text-gray-800 bg-gray-50 focus:border-gray-700 focus:ring-1 focus:ring-gray-600 focus:outline-none"
                                placeholder="Planilla...">
                        </th>
                        <th class="p-2 bg-gray-50">
                            <input type="text" wire:model.live.debounce.300ms="cod_obra"
                                class="w-full text-xs border border-gray-300 rounded px-2 py-2 text-gray-800 bg-gray-50 focus:border-gray-700 focus:ring-1 focus:ring-gray-600 focus:outline-none"
                                placeholder="Cód. Obra...">
                        </th>
                        <th class="p-2 bg-gray-50">
                            <input type="text" wire:model.live.debounce.300ms="nom_obra"
                                class="w-full text-xs border border-gray-300 rounded px-2 py-2 text-gray-800 bg-gray-50 focus:border-gray-700 focus:ring-1 focus:ring-gray-600 focus:outline-none"
                                placeholder="Obra...">
                        </th>
                        <th class="p-2 bg-gray-50">
                            <input type="text" wire:model.live.debounce.300ms="codigo_cliente"
                                class="w-full text-xs border border-gray-300 rounded px-2 py-2 text-gray-800 bg-gray-50 focus:border-gray-700 focus:ring-1 focus:ring-gray-600 focus:outline-none"
                                placeholder="Cód. Cliente...">
                        </th>
                        <th class="p-2 bg-gray-50">
                            <input type="text" wire:model.live.debounce.300ms="cliente"
                                class="w-full text-xs border border-gray-300 rounded px-2 py-2 text-gray-800 bg-gray-50 focus:border-gray-700 focus:ring-1 focus:ring-gray-600 focus:outline-none"
                                placeholder="Cliente...">
                        </th>
                        <th class="p-2 bg-gray-50">
                            <input type="text" wire:model.live.debounce.300ms="nave"
                                class="w-full text-xs border border-gray-300 rounded px-2 py-2 text-gray-800 bg-gray-50 focus:border-gray-700 focus:ring-1 focus:ring-gray-600 focus:outline-none"
                                placeholder="Nave...">
                        </th>
                        <th class="p-2 bg-gray-50">
                            <input type="text" wire:model.live.debounce.300ms="ubicacion"
                                class="w-full text-xs border border-gray-300 rounded px-2 py-2 text-gray-800 bg-gray-50 focus:border-gray-700 focus:ring-1 focus:ring-gray-600 focus:outline-none"
                                placeholder="Ubicación...">
                        </th>
                        <th class="p-2 bg-gray-50">
                            <select wire:model.live="estado"
                                class="w-full text-xs border border-gray-300 rounded px-2 py-2 text-gray-800 bg-gray-50 focus:border-gray-700 focus:ring-1 focus:ring-gray-600 focus:outline-none">
                                <option value="">Todos</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="asignado_a_salida">Asignado</option>
                                <option value="en_reparto">En Reparto</option>
                                <option value="enviado">Enviado</option>
                            </select>
                        </th>
                        <th class="p-2 bg-gray-50"></th> {{-- Elementos --}}
                        <th class="p-2 bg-gray-50"></th> {{-- Peso --}}
                        <th class="p-2 bg-gray-50">
                            <input type="date" wire:model.live.debounce.300ms="created_at"
                                class="w-full text-xs border border-gray-300 rounded px-2 py-2 text-gray-800 bg-gray-50 focus:border-gray-700 focus:ring-1 focus:ring-gray-600 focus:outline-none">
                        </th>
                        <th class="p-2 bg-gray-50 border-r-0">
                            <input type="date" wire:model.live.debounce.300ms="fecha_limite"
                                class="w-full text-xs border border-gray-300 rounded px-2 py-2 text-gray-800 bg-gray-50 focus:border-gray-700 focus:ring-1 focus:ring-gray-600 focus:outline-none">
                        </th>
                        <th class="p-1 text-center align-middle">
                            <div class="flex justify-center gap-2 items-center h-full">
                                {{-- ♻️ Botón reset --}}
                                <button wire:click="limpiarFiltros"
                                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-2 rounded text-xs flex items-center justify-center"
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
                </x-tabla.header>

                <tbody class="text-gray-700 text-sm">
                    @forelse ($paquetes as $paquete)
                        <tr
                            class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-gray-200 transition-colors text-xs uppercase">
                            <td class="p-2 text-center border">{{ $paquete->id }}</td>
                            <td class="p-2 text-center border">{{ $paquete->codigo }}</td>
                            <td class="p-2 text-center border">
                                <a href="{{ route('planillas.index', ['codigo' => $paquete->planilla->codigo_limpio]) }}"
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
                                    class="inline-flex items-center px-2 py-2 rounded-full text-xs font-semibold {{ $estadoBadge['bg'] }} {{ $estadoBadge['text'] }}">
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
                            <td class="p-2 text-center border border-r-0">
                                {{ optional($paquete->planilla->fecha_estimada_reparto)->format('d/m/Y') ?? 'No disponible' }}
                            </td>
                            <td class="p-2 text-center border-0">
                                <div class="flex flex-row justify-center items-center gap-3">
                                    {{-- Botón QR --}}
                                    <button
                                        onclick="generateAndPrintQR('{{ $paquete->codigo }}', '{{ $paquete->planilla->codigo_limpio }}', 'PAQUETE')"
                                        class="w-6 h-6 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center"
                                        title="Generar QR">
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

                                    {{-- Botón eliminar --}}
                                    <x-tabla.boton-eliminar :action="route('paquetes.destroy', $paquete->id)" />
                                </div>
                            </td>
                        </tr>
                    @empty
                    <tr>
                        <td colspan="15" class="text-center py-4 text-gray-500">No hay paquetes registrados</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-tabla.paginacion-livewire :paginador="$paquetes" />
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

            // Ejecutar en carga inicial
            document.addEventListener('DOMContentLoaded', inicializarPaquetes);

            // Ejecutar después de navegación SPA con Livewire
            document.addEventListener('livewire:navigated', inicializarPaquetes);
        </script>
    @endpush

    <!-- SCRIPT PARA IMPRIMIR QR -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="{{ asset('js/imprimirQrS.js') }}"></script>
    <script src="{{ asset('js/elementosJs/figuraElemento.js') }}" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</div>
