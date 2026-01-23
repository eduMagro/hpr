<div>
    <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

    {{-- Tabla --}}
    <div class="table-container w-full overflow-x-auto">
        <table class="table-global w-full min-w-[1000px]">
            <thead>
                <tr class="text-center">
                    <x-tabla.encabezado-ordenable campo="id" :sortActual="$sort" :orderActual="$order" texto="ID" />
                    <x-tabla.encabezado-ordenable campo="entrada_id" :sortActual="$sort" :orderActual="$order"
                        texto="Albarán" />
                    <x-tabla.encabezado-ordenable campo="codigo" :sortActual="$sort" :orderActual="$order" texto="Código" />
                    <x-tabla.encabezado-ordenable campo="nave" :sortActual="$sort" :orderActual="$order" texto="Nave" />
                    <x-tabla.encabezado-ordenable campo="fabricante" :sortActual="$sort" :orderActual="$order"
                        texto="Fabricante" />
                    <x-tabla.encabezado-ordenable campo="tipo" :sortActual="$sort" :orderActual="$order" texto="Tipo" />
                    <x-tabla.encabezado-ordenable campo="diametro" :sortActual="$sort" :orderActual="$order"
                        texto="Diámetro" />
                    <x-tabla.encabezado-ordenable campo="longitud" :sortActual="$sort" :orderActual="$order"
                        texto="Longitud" />
                    <x-tabla.encabezado-ordenable campo="n_colada" :sortActual="$sort" :orderActual="$order"
                        texto="N° Colada" />
                    <x-tabla.encabezado-ordenable campo="n_paquete" :sortActual="$sort" :orderActual="$order"
                        texto="N° Paquete" />
                    <x-tabla.encabezado-ordenable campo="peso_inicial" :sortActual="$sort" :orderActual="$order"
                        texto="Peso Inicial" />
                    <x-tabla.encabezado-ordenable campo="peso_stock" :sortActual="$sort" :orderActual="$order"
                        texto="Peso Stock" />
                    <x-tabla.encabezado-ordenable campo="estado" :sortActual="$sort" :orderActual="$order" texto="Estado" />
                    <x-tabla.encabezado-ordenable campo="ubicacion" :sortActual="$sort" :orderActual="$order"
                        texto="Ubicación" />
                    <x-tabla.encabezado-ordenable campo="created_at" :sortActual="$sort" :orderActual="$order"
                        texto="Creado" />
                    <th class="p-2 border">Acciones</th>
                </tr>
                {{-- Fila de filtros --}}
                <tr class="filters-row text-center">
                    <th>
                        <input type="text" wire:model.live.debounce.300ms="id" placeholder="ID">
                    </th>
                    <th>
                        <input type="text" wire:model.live.debounce.300ms="albaran" placeholder="Albarán">
                    </th>
                    <th>
                        <input type="text" wire:model.live.debounce.300ms="codigo" placeholder="Código">
                    </th>
                    <th>
                        <select wire:model.live="nave_id">
                            <option value="">Todas</option>
                            @foreach ($naves as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th>
                        <input type="text" wire:model.live.debounce.300ms="fabricante" placeholder="Fabricante">
                    </th>
                    <th>
                        <input type="text" wire:model.live.debounce.300ms="tipo" placeholder="Tipo">
                    </th>
                    <th>
                        <input type="text" wire:model.live.debounce.300ms="diametro" placeholder="Ø">
                    </th>
                    <th>
                        <input type="text" wire:model.live.debounce.300ms="longitud" placeholder="Long.">
                    </th>
                    <th>
                        <input type="text" wire:model.live.debounce.300ms="n_colada" placeholder="N° Colada">
                    </th>
                    <th>
                        <input type="text" wire:model.live.debounce.300ms="n_paquete" placeholder="N° Paquete">
                    </th>
                    <th></th>
                    <th></th>
                    <th>
                        <select wire:model.live="estado">
                            <option value="">Todos</option>
                            <option value="almacenado">Almacenado</option>
                            <option value="fabricando">Fabricando</option>
                            <option value="consumido">Consumido</option>
                        </select>
                    </th>
                    <th>
                        <input type="text" wire:model.live.debounce.300ms="ubicacion" placeholder="Ubicación">
                    </th>
                    <th></th>
                    <th class="text-center align-middle">
                        <div class="flex justify-center gap-2 items-center h-full">
                            {{-- ♻️ Botón reset --}}
                            <button type="button" wire:click="limpiarFiltros"
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
            <tbody>
                @forelse($productos as $producto)
                    <tr wire:key="producto-{{ $producto->id }}" x-data="{
                        editando: false,
                        producto: @js($producto),
                        original: JSON.parse(JSON.stringify(@js($producto)))
                    }"
                        @dblclick="if(!$event.target.closest('input, select, button, a')) {
                            if(!editando) {
                                editando = true;
                            } else {
                                producto = JSON.parse(JSON.stringify(original));
                                editando = false;
                            }
                        }"
                        @keydown.enter.stop="if(editando) { guardarCambiosProducto(producto); editando = false; }"
                        @keydown.escape.stop="if(editando) { producto = JSON.parse(JSON.stringify(original)); editando = false; }"
                        :class="{ 'editing': editando }"
                        class="text-xs leading-none cursor-pointer">
                        <!-- ID -->
                        <td class="px-2 py-3" x-text="producto.id"></td>

                        <!-- ALBARAN -->
                        <td class="px-2 py-3">
                            @if ($producto->entrada)
                                <a href="{{ route('entradas.index', ['albaran' => $producto->entrada->albaran]) }}"
                                    class="text-blue-600 dark:text-blue-400 hover:underline hover:text-blue-800 dark:hover:text-blue-300 transition-colors">
                                    {{ $producto->entrada->albaran }}
                                </a>
                            @else
                                —
                            @endif
                        </td>

                        <!-- CODIGO -->
                        <td class="px-2 py-3">
                            <template x-if="!editando">
                                <span x-text="producto.codigo ?? 'N/A'"></span>
                            </template>
                            <input x-show="editando" x-cloak type="text" x-model="producto.codigo"
                                class="inline-edit-input">
                        </td>

                        <!-- NAVE -->
                        <td class="px-2 py-3">
                            <template x-if="!editando">
                                <span>{{ $producto->obra->obra ?? '—' }}</span>
                            </template>
                            <select x-show="editando" x-cloak x-model="producto.obra_id" class="inline-edit-select">
                                <option value="">Sin nave</option>
                                @foreach ($naves as $naveId => $naveNombre)
                                    <option value="{{ $naveId }}">{{ $naveNombre }}</option>
                                @endforeach
                            </select>
                        </td>

                        <!-- FABRICANTE -->
                        <td class="px-2 py-3">
                            <template x-if="!editando">
                                <span>{{ $producto->fabricante->nombre ?? '—' }}</span>
                            </template>
                            <select x-show="editando" x-cloak x-model="producto.fabricante_id" class="inline-edit-select">
                                <option value="">Sin fabricante</option>
                                @foreach ($fabricantes as $fab)
                                    <option value="{{ $fab->id }}">{{ $fab->nombre }}</option>
                                @endforeach
                            </select>
                        </td>

                        <!-- PRODUCTO BASE (Tipo + Diámetro + Longitud) -->
                        <td class="px-2 py-3">
                            <template x-if="!editando">
                                <span>{{ ucfirst($producto->productoBase->tipo ?? '—') }}</span>
                            </template>
                            <select x-show="editando" x-cloak x-model="producto.producto_base_id" class="inline-edit-select">
                                <option value="">Seleccionar</option>
                                @foreach ($productosBase as $pb)
                                    <option value="{{ $pb->id }}">{{ ucfirst($pb->tipo) }}
                                        Ø{{ $pb->diametro }} - {{ $pb->longitud }}m</option>
                                @endforeach
                            </select>
                        </td>

                        <!-- DIAMETRO (solo lectura, se actualiza con producto_base) -->
                        <td class="px-2 py-3">{{ $producto->productoBase->diametro ?? '—' }}</td>

                        <!-- LONGITUD (solo lectura, se actualiza con producto_base) -->
                        <td class="px-2 py-3">{{ $producto->productoBase->longitud ?? '—' }}</td>

                        <!-- N_COLADA -->
                        <td class="px-2 py-3">
                            <template x-if="!editando">
                                <span x-text="producto.n_colada"></span>
                            </template>
                            <input x-show="editando" x-cloak type="text" x-model="producto.n_colada"
                                class="inline-edit-input">
                        </td>

                        <!-- N_PAQUETE -->
                        <td class="px-2 py-3">
                            <template x-if="!editando">
                                <span x-text="producto.n_paquete"></span>
                            </template>
                            <input x-show="editando" x-cloak type="text" x-model="producto.n_paquete"
                                class="inline-edit-input">
                        </td>

                        <!-- PESO_INICIAL -->
                        <td class="px-2 py-3">
                            <template x-if="!editando">
                                <span x-text="producto.peso_inicial + ' kg'"></span>
                            </template>
                            <input x-show="editando" x-cloak type="number" step="0.01"
                                x-model="producto.peso_inicial" class="inline-edit-input">
                        </td>

                        <!-- PESO_STOCK -->
                        <td class="px-2 py-3" x-text="producto.peso_stock + ' kg'"></td>

                        <!-- ESTADO -->
                        <td class="px-2 py-3">
                            <template x-if="!editando">
                                @if ($producto->estado === 'consumido')
                                    <div class="relative group inline-block">
                                        <span class="cursor-help">{{ $producto->estado }}</span>
                                        <div
                                            class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                                            <div class="font-semibold mb-1">Información de consumo</div>
                                            @if ($producto->fecha_consumido)
                                                <div>
                                                    {{ \Carbon\Carbon::parse($producto->fecha_consumido)->format('d/m/Y H:i') }}
                                                </div>
                                            @endif
                                            @if ($producto->consumidoPor)
                                                <div>{{ $producto->consumidoPor->nombre_completo }}</div>
                                            @endif
                                            <div
                                                class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900">
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span x-text="producto.estado"></span>
                                @endif
                            </template>
                            <select x-show="editando" x-cloak x-model="producto.estado" class="inline-edit-select">
                                <option value="almacenado">Almacenado</option>
                                <option value="fabricando">Fabricando</option>
                                <option value="consumido">Consumido</option>
                            </select>
                        </td>

                        <!-- UBICACION -->
                        <td class="px-2 py-3">
                            <template x-if="!editando">
                                <span>
                                    @if ($producto->ubicacion)
                                        {{ $producto->ubicacion->nombre }}
                                    @elseif($producto->maquina)
                                        {{ $producto->maquina->nombre }}
                                    @else
                                        No está ubicada
                                    @endif
                                </span>
                            </template>
                            <select x-show="editando" x-cloak x-model="producto.ubicacion_id" class="inline-edit-select">
                                <option value="">Sin ubicación</option>
                                <optgroup label="Ubicaciones">
                                    @foreach ($ubicaciones as $ubi)
                                        <option value="{{ $ubi->id }}">{{ $ubi->nombre }}</option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </td>

                        <!-- FECHA CREACION -->
                        <td class="px-2 py-3">{{ $producto->created_at->format('d/m/Y') }}</td>
                        <td class="px-2 py-2 border border-gray-200 dark:border-gray-700 text-xs font-bold">
                            <div class="flex items-center space-x-2 justify-center">
                                <!-- Botones en modo edición -->
                                <button x-show="editando" x-cloak
                                    @click="guardarCambiosProducto(producto); editando = false"
                                    class="table-btn table-btn-green" title="Guardar cambios">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                                <button x-show="editando" x-cloak
                                    @click="producto = JSON.parse(JSON.stringify(original)); editando = false"
                                    class="table-btn table-btn-red" title="Cancelar edición">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>

                                <!-- Botones en modo normal -->
                                <template x-if="!editando">
                                    <div class="flex items-center space-x-2">
                                        <button @click="editando = true"
                                            class="table-btn table-btn-blue" title="Editar inline">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <a href="{{ route('productos.edit', $producto->id) }}"
                                            class="table-btn table-btn-yellow" title="Editar completo">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('productos.show', $producto->id) }}"
                                            class="table-btn table-btn-blue" title="Ver">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <button type="button"
                                            onclick="abrirModalMovimientoLibre('{{ $producto->codigo }}')"
                                            class="table-btn table-btn-green" title="Mover producto">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                viewBox="0 0 24 24" fill="currentColor">
                                                <path
                                                    d="M8.5 2A1.5 1.5 0 0 1 10 3.5V10h.5V4A1.5 1.5 0 0 1 13 4v6h.5V5.5a1.5 1.5 0 0 1 3 0V10h.5V7a1.5 1.5 0 0 1 3 0v9.5a3.5 3.5 0 0 1-7 0V18h-2a3 3 0 0 1-3-3v-4H8V3.5A1.5 1.5 0 0 1 8.5 2z" />
                                            </svg>
                                        </button>
                                        <button type="button"
                                            data-consumir="{{ route('productos.editarConsumir', $producto->id) }}"
                                            class="btn-consumir table-btn table-btn-red" title="Consumir">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                fill="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    d="M13.5 3.5c-2 2-1.5 4-3 5.5s-4 1-4 5a6 6 0 0012 0c0-2-1-3.5-2-4.5s-1-3-3-6z" />
                                            </svg>
                                        </button>
                                        <x-tabla.boton-eliminar :action="route('productos.destroy', $producto->id)" />
                                    </div>
                                </template>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="16" class="text-center py-4 text-gray-500 dark:text-gray-400">No hay productos con esa
                            descripción.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="16">
                        <div class="flex justify-end items-center gap-4 text-sm">
                            <span class="font-semibold">Total peso filtrado:</span>
                            <span class="text-base font-bold text-blue-800 dark:text-blue-400">
                                {{ number_format($totalPesoInicial, 2, ',', '.') }} kg
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                ({{ $productos->total() }} productos)
                            </span>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Paginación --}}
    <div class="mt-4">
        {{ $productos->links('vendor.livewire.tailwind') }}
    </div>

    {{-- Script para edición inline y botón consumir --}}
    <script>
        // Función para guardar cambios de producto
        function guardarCambiosProducto(producto) {
            const datosActualizar = {
                codigo: producto.codigo,
                obra_id: producto.obra_id ? Number(producto.obra_id) : null,
                fabricante_id: producto.fabricante_id ? Number(producto.fabricante_id) : null,
                producto_base_id: producto.producto_base_id ? Number(producto.producto_base_id) : null,
                n_colada: producto.n_colada,
                n_paquete: producto.n_paquete,
                peso_inicial: parseFloat(producto.peso_inicial) || 0,
                estado: producto.estado,
                ubicacion_id: producto.ubicacion_id ? Number(producto.ubicacion_id) : null,
            };

            fetch(`/productos/${producto.id}`, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                        "Accept": "application/json",
                    },
                    body: JSON.stringify(datosActualizar),
                })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success || data.ok) {
                        Swal.fire({
                            icon: "success",
                            title: "Producto actualizado",
                            text: "Los cambios se han guardado correctamente.",
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        let errorMsg = data.message || "Ha ocurrido un error inesperado.";
                        if (data.errors) {
                            errorMsg = Object.values(data.errors).flat().join(" ");
                        }
                        Swal.fire({
                            icon: "error",
                            title: "Error al actualizar",
                            text: errorMsg,
                            confirmButtonText: "OK",
                        });
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Error de conexión",
                        text: "No se pudo actualizar el producto. Inténtalo nuevamente.",
                        confirmButtonText: "OK",
                    });
                });
        }

        function initProductosTablePage() {
            // Prevenir doble inicialización
            if (document.body.dataset.productosTablePageInit === 'true') return;

            console.log('🔍 Inicializando tabla de productos...');

            // Delegación de eventos para botones "Consumir"
            document.body.addEventListener('click', async (e) => {
                const btn = e.target.closest('.btn-consumir');
                if (!btn) return;

                e.preventDefault();

                const url = btn.dataset.consumir;
                if (!url) return;

                const {
                    value: opcion
                } = await Swal.fire({
                    title: '¿Cómo deseas consumir el material?',
                    text: 'Selecciona si quieres consumirlo completo o solo unos kilos.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Consumir completo',
                    cancelButtonText: 'Cancelar',
                    showDenyButton: true,
                    denyButtonText: 'Consumir por kilos'
                });

                if (opcion) {
                    // Consumir completo
                    if (opcion === true) {
                        window.location.href = url + '?modo=total';
                    }
                } else if (opcion === false) {
                    // Consumir por kilos
                    const {
                        value: kilos
                    } = await Swal.fire({
                        title: 'Introduce los kilos a consumir',
                        input: 'number',
                        inputAttributes: {
                            min: 1,
                            step: 0.01
                        },
                        inputPlaceholder: 'Ejemplo: 250',
                        showCancelButton: true,
                        confirmButtonText: 'Consumir',
                        cancelButtonText: 'Cancelar',
                        preConfirm: (value) => {
                            if (!value || value <= 0) {
                                Swal.showValidationMessage(
                                    'Debes indicar un número válido mayor que 0');
                                return false;
                            }
                            return value;
                        }
                    });

                    if (kilos) {
                        window.location.href = url + '?modo=parcial&kgs=' + kilos;
                    }
                }
            });

            // Marcar como inicializado
            document.body.dataset.productosTablePageInit = 'true';
        }

        // Registrar en el sistema global
        window.pageInitializers.push(initProductosTablePage);

        // Configurar listeners
        document.addEventListener('livewire:navigated', initProductosTablePage);
        document.addEventListener('DOMContentLoaded', initProductosTablePage);

        // Limpiar flag antes de navegar
        document.addEventListener('livewire:navigating', () => {
            document.body.dataset.productosTablePageInit = 'false';
        });
    </script>
</div>
