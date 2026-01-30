<div>
    <div class="w-full">

        <!-- Barra de acciones y alertas -->
        <div class="mb-4 p-3 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex flex-wrap items-center justify-between gap-4">
                {{-- Lado izquierdo: Contadores --}}
                <div class="flex flex-wrap items-center gap-6">
                    @if ($planillasSinAprobar > 0)
                        <div class="flex items-center gap-2">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-white dark:bg-gray-700 shadow-sm border border-gray-200 dark:border-gray-600">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-xl font-bold text-gray-800 dark:text-white">{{ number_format($planillasSinAprobar) }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">sin aprobar</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <button wire:click="verSinAprobar"
                                    class="px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-white dark:hover:bg-gray-700 rounded transition">
                                    Ver
                                </button>
                                <button wire:click="toggleModoSeleccion"
                                    class="px-2 py-1 text-xs font-medium rounded transition flex items-center gap-1
                                    {{ $modoSeleccion
                                        ? 'bg-gray-800 text-white'
                                        : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-white dark:hover:bg-gray-700'
                                    }}">
                                    @if ($modoSeleccion)
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Cancelar
                                    @else
                                        Aprobar
                                    @endif
                                </button>
                            </div>
                        </div>
                    @endif

                    @if ($planillasSinAprobar > 0 && $planillasSinRevisar > 0)
                        <div class="hidden sm:block w-px h-6 bg-gray-300 dark:bg-gray-600"></div>
                    @endif

                    @if ($planillasSinRevisar > 0)
                        <div class="flex items-center gap-2">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-white dark:bg-gray-700 shadow-sm border border-gray-200 dark:border-gray-600">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-xl font-bold text-gray-800 dark:text-white">{{ number_format($planillasSinRevisar) }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">sin revisar</span>
                            </div>
                            <button wire:click="verSinRevisar"
                                class="px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-white dark:hover:bg-gray-700 rounded transition">
                                Ver
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Lado derecho: Acciones --}}
                <div class="flex items-center gap-2">
                    <button type="button" id="btn-abrir-import-lw"
                        onclick="document.getElementById('btn-abrir-import')?.click()"
                        class="px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition shadow-sm flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Importar
                    </button>
                    <button type="button" id="btn-completar-lw"
                        onclick="document.getElementById('btn-completar-planillas')?.click()"
                        class="px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition shadow-sm flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Completar
                    </button>
                    @livewire('sync-monitor')
                </div>
            </div>
        </div>

        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />
        <!-- Tabla con filtros Livewire -->
        <div class="w-full overflow-x-auto bg-white dark:bg-gray-900 shadow-lg rounded-lg">
            <table class="table-global w-full min-w-[2000px] border border-gray-300 dark:border-gray-700 rounded-lg">
                <x-tabla.header>
                    <x-tabla.header-row>
                        @if ($modoSeleccion)
                            <th class="p-2 border w-10">
                                <span class="text-xs">Sel.</span>
                            </th>
                        @endif
                        <x-tabla.encabezado-ordenable campo="id" :sortActual="$sort" :orderActual="$order"
                            texto="ID" />
                        <x-tabla.encabezado-ordenable campo="codigo" :sortActual="$sort" :orderActual="$order"
                            texto="Código" />
                        <x-tabla.encabezado-ordenable campo="codigo_cliente" :sortActual="$sort" :orderActual="$order"
                            texto="Codigo Cliente" />
                        <x-tabla.encabezado-ordenable campo="cliente_id" :sortActual="$sort" :orderActual="$order"
                            texto="Cliente" />
                        <x-tabla.encabezado-ordenable campo="codigo_obra" :sortActual="$sort" :orderActual="$order"
                            texto="Código Obra" />
                        <x-tabla.encabezado-ordenable campo="obra_id" :sortActual="$sort" :orderActual="$order"
                            texto="Obra" />
                        <x-tabla.encabezado-ordenable campo="seccion" :sortActual="$sort" :orderActual="$order"
                            texto="Sección" />
                        <x-tabla.encabezado-ordenable campo="descripcion" :sortActual="$sort" :orderActual="$order"
                            texto="Descripción" />
                        <x-tabla.encabezado-ordenable campo="ensamblado" :sortActual="$sort" :orderActual="$order"
                            texto="Ensamblado" />
                        <x-tabla.encabezado-ordenable campo="comentario" :sortActual="$sort" :orderActual="$order"
                            texto="Comentario" />
                        <th class="p-2 border dark:border-gray-700">Peso Fabricado</th>
                        <x-tabla.encabezado-ordenable campo="peso_total" :sortActual="$sort" :orderActual="$order"
                            texto="Peso Total" />
                        <x-tabla.encabezado-ordenable campo="estado" :sortActual="$sort" :orderActual="$order"
                            texto="Estado" />
                        <x-tabla.encabezado-ordenable campo="fecha_inicio" :sortActual="$sort" :orderActual="$order"
                            texto="Fecha Inicio" />
                        <x-tabla.encabezado-ordenable campo="fecha_finalizacion" :sortActual="$sort" :orderActual="$order"
                            texto="Fecha Finalización" />
                        <x-tabla.encabezado-ordenable campo="created_at" :sortActual="$sort" :orderActual="$order"
                            texto="Fecha Importación" />
                        <x-tabla.encabezado-ordenable campo="fecha_estimada_entrega" :sortActual="$sort"
                            :orderActual="$order" texto="Fecha Entrega" />
                        <x-tabla.encabezado-ordenable campo="usuario_id" :sortActual="$sort" :orderActual="$order"
                            texto="Usuario" />
                        <x-tabla.encabezado-ordenable campo="revisada" :sortActual="$sort" :orderActual="$order"
                            texto="Revisada" />
                        <x-tabla.encabezado-ordenable campo="revisor_id" :sortActual="$sort" :orderActual="$order"
                            texto="Revisada por" />
                        <x-tabla.encabezado-ordenable campo="revisada_at" :sortActual="$sort" :orderActual="$order"
                            texto="Fecha revisión" />
                        <x-tabla.encabezado-ordenable campo="aprobada" :sortActual="$sort" :orderActual="$order"
                            texto="Aprobada" />
                        <x-tabla.encabezado-ordenable campo="aprobada_at" :sortActual="$sort" :orderActual="$order"
                            texto="Fecha aprobación" />
                        <x-tabla.encabezado-ordenable campo="fecha_creacion_ferrawin" :sortActual="$sort" :orderActual="$order"
                            texto="Fecha Ferrawin" />
                        <th class="p-2 border dark:border-gray-700">Tiempo Fab.</th>
                        <th class="p-2 border dark:border-gray-700">Acciones</th>

                    </x-tabla.header-row>
                    <x-tabla.filtro-row>
                        @if ($modoSeleccion)
                            <th class="p-1 border dark:border-gray-700"></th>
                        @endif
                        <th class="p-1 border dark:border-gray-700">
                            <input type="number" wire:model.live.debounce.300ms="id"
                                class="w-full text-xs px-2 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="ID...">
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            <input type="text" wire:model.live.debounce.300ms="codigo"
                                class="w-full text-xs px-2 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Código...">
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            <input type="text" wire:model.live.debounce.300ms="codigo_cliente"
                                class="w-full text-xs px-2 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Cód. Cliente...">
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            <input type="text" wire:model.live.debounce.300ms="cliente"
                                class="w-full text-xs px-2 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Cliente...">
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            <input type="text" wire:model.live.debounce.300ms="cod_obra"
                                class="w-full text-xs px-2 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Cód. Obra...">
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            <input type="text" wire:model.live.debounce.300ms="nom_obra"
                                class="w-full text-xs px-2 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Obra...">
                        </th>
                        <th class="p-1 border relative" x-data="{ open: false }" @click.outside="open = false">
                            <div class="relative">
                                <button type="button" @click="open = !open"
                                    class="w-full text-xs px-2 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none flex items-center justify-between gap-1 {{ count($secciones) > 0 || !empty($seccionTextoLibre) ? 'bg-blue-50 border-blue-400' : '' }}">
                                    <span class="truncate">
                                        @if(count($secciones) > 0)
                                            {{ count($secciones) }} selec.
                                        @elseif(!empty($seccionTextoLibre))
                                            "{{ Str::limit($seccionTextoLibre, 8) }}"
                                        @else
                                            Sección...
                                        @endif
                                    </span>
                                    <svg class="w-3 h-3 flex-shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>

                                <div x-cloak x-show="open" x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     class="absolute z-50 mt-1 w-56 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg"
                                     style="left: 0;" @click.stop>
                                    {{-- Header con título y limpiar --}}
                                    <div class="sticky top-0 bg-gray-100 dark:bg-gray-700 px-3 py-2 border-b dark:border-gray-600 flex justify-between items-center">
                                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Secciones</span>
                                        @if(count($secciones) > 0 || !empty($seccionTextoLibre))
                                            <button wire:click="limpiarSecciones" class="text-xs text-red-600 hover:text-red-800">
                                                Limpiar
                                            </button>
                                        @endif
                                    </div>

                                    {{-- Input de texto libre --}}
                                    <div class="px-3 py-2 border-b dark:border-gray-600 bg-gray-50 dark:bg-gray-750">
                                        <label class="text-xs text-gray-600 dark:text-gray-400 mb-1 block">Búsqueda libre:</label>
                                        <input type="text"
                                            wire:model.live.debounce.300ms="seccionTextoLibre"
                                            placeholder="Escribir sección..."
                                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none {{ !empty($seccionTextoLibre) ? 'bg-blue-50 border-blue-400' : '' }}"
                                            {{ count($secciones) > 0 ? 'disabled' : '' }}>
                                        @if(count($secciones) > 0)
                                            <p class="text-xs text-gray-400 mt-1">Deselecciona los checks para usar texto libre</p>
                                        @endif
                                    </div>

                                    {{-- Separador --}}
                                    <div class="px-3 py-1 bg-gray-100 dark:bg-gray-700 border-b dark:border-gray-600">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">O selecciona de la lista:</span>
                                    </div>

                                    {{-- Lista de checkboxes --}}
                                    <div class="max-h-48 overflow-y-auto">
                                        @forelse($seccionesDisponibles as $seccionItem)
                                            <label class="flex items-center px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0 {{ !empty($seccionTextoLibre) ? 'opacity-50' : '' }}">
                                                <input type="checkbox"
                                                    wire:click="toggleSeccion('{{ $seccionItem }}')"
                                                    {{ in_array($seccionItem, $secciones) ? 'checked' : '' }}
                                                    {{ !empty($seccionTextoLibre) ? 'disabled' : '' }}
                                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                                                <span class="ml-2 text-xs text-gray-700 dark:text-gray-300">{{ $seccionItem }}</span>
                                            </label>
                                        @empty
                                            <div class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">No hay secciones</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            <input type="text" wire:model.live.debounce.300ms="descripcion"
                                class="w-full text-xs px-2 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Descripción...">
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            <input type="text" wire:model.live.debounce.300ms="ensamblado"
                                class="w-full text-xs px-2 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Ensamblado...">
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            <input type="text" wire:model.live.debounce.300ms="comentario"
                                class="w-full text-xs px-2 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Comentario...">
                        </th>
                        <th class="p-1 border dark:border-gray-700"></th> {{-- Peso Fabricado --}}
                        <th class="p-1 border dark:border-gray-700"></th> {{-- Peso Total --}}
                        <th class="p-1 border dark:border-gray-700">
                            <select wire:model.live="estado"
                                class="w-full text-xs px-1 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                                <option value="">Todos</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="fabricando">Fabricando</option>
                                <option value="completada">Completada</option>
                                <option value="montaje">Montaje</option>
                            </select>
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            <input type="date" wire:model.live.debounce.300ms="fecha_inicio"
                                class="w-full text-xs px-1 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            <input type="date" wire:model.live.debounce.300ms="fecha_finalizacion"
                                class="w-full text-xs px-1 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            <input type="date" wire:model.live.debounce.300ms="fecha_importacion"
                                class="w-full text-xs px-1 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            <input type="date" wire:model.live.debounce.300ms="fecha_estimada_entrega"
                                class="w-full text-xs px-1 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            <input type="text" wire:model.live.debounce.300ms="usuario"
                                class="w-full text-xs px-2 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Usuario...">
                        </th>
                        <th class="p-1 border dark:border-gray-700">
                            <select wire:model.live="revisada"
                                class="w-full text-xs px-1 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                                <option value="">Todas</option>
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </th>
                        <th class="p-1 border dark:border-gray-700"></th>
                        <th class="p-1 border dark:border-gray-700"></th>
                        <th class="p-1 border dark:border-gray-700">
                            <select wire:model.live="aprobada"
                                class="w-full text-xs px-1 py-1 border rounded text-blue-900 dark:text-blue-300 bg-white dark:bg-gray-800 dark:border-gray-600 focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                                <option value="">Todas</option>
                                <option value="aprobadas">Aprobadas</option>
                                <option value="0">No aprobadas</option>
                            </select>
                        </th>
                        <th class="p-1 border dark:border-gray-700"></th>
                        <th class="p-1 border dark:border-gray-700"></th> {{-- Fecha Ferrawin --}}
                        <th class="p-1 border dark:border-gray-700"></th> {{-- Tiempo Fabricación --}}
                        <th class="p-1 border text-center align-middle">
                            <button wire:click="limpiarFiltros"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center mx-auto"
                                title="Restablecer filtros">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                                </svg>
                            </button>
                        </th>
                    </x-tabla.filtro-row>
                </x-tabla.header>

                <tbody class="text-gray-700 dark:text-gray-300">
                    @forelse ($planillas as $planilla)
                        <x-tabla.row :selected="in_array($planilla->id, $planillasSeleccionadas)" class="uppercase">
                            @if ($modoSeleccion)
                                <td class="p-2 text-center border dark:border-gray-700">
                                    @if (!$planilla->aprobada)
                                        <input type="checkbox"
                                            wire:click="toggleSeleccion({{ $planilla->id }})"
                                            {{ in_array($planilla->id, $planillasSeleccionadas) ? 'checked' : '' }}
                                            class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500 cursor-pointer">
                                    @else
                                        <span class="text-green-600" title="Ya aprobada">✓</span>
                                    @endif
                                </td>
                            @endif
                            <td class="p-2 text-center border dark:border-gray-700">{{ $planilla->id }}</td>
                            <td class="p-2 text-center border dark:border-gray-700">
                                <a href="{{ route('planillas.show', $planilla->id) }}"
                                    class="text-blue-500 hover:underline">
                                    {{ $planilla->codigo_limpio }}
                                </a>
                            </td>
                            <td class="p-2 text-center border dark:border-gray-700">{{ $planilla->cliente->codigo ?? 'N/A' }}</td>
                            <td class="p-2 text-center border dark:border-gray-700">
                                @if ($planilla->cliente_id)
                                    <a href="{{ route('clientes.index', ['id' => $planilla->cliente_id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $planilla->cliente->empresa ?? 'N/A' }}
                                    </a>
                                @else
                                    {{ $planilla->cliente->empresa ?? 'N/A' }}
                                @endif
                            </td>
                            <td class="p-2 text-center border dark:border-gray-700">{{ $planilla->obra->cod_obra ?? 'N/A' }}</td>
                            <td class="p-2 text-center border dark:border-gray-700">
                                @if ($planilla->cliente_id)
                                    <a href="{{ route('clientes.show', ['cliente' => $planilla->cliente_id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $planilla->obra->obra ?? 'N/A' }}
                                    </a>
                                @else
                                    {{ $planilla->obra->obra ?? 'N/A' }}
                                @endif
                            </td>
                            <td class="p-2 text-center border dark:border-gray-700">{{ $planilla->seccion }}</td>
                            <td class="p-2 text-center border dark:border-gray-700">{{ $planilla->descripcion }}</td>
                            <td class="p-2 text-center border dark:border-gray-700">{{ $planilla->ensamblado }}</td>
                            <td class="p-2 text-center border dark:border-gray-700">{{ $planilla->comentario }}</td>
                            <td class="p-2 text-center border dark:border-gray-700">
                                {{ number_format($planilla->suma_peso_completados ?? 0, 2) }} kg</td>
                            <td class="p-2 text-center border dark:border-gray-700">{{ number_format($planilla->peso_total, 2) }} kg</td>
                            <td class="p-2 text-center border dark:border-gray-700">
                                <span
                                    class="px-2 py-2 rounded text-xs font-semibold
                                    {{ $planilla->estado === 'completada' ? 'bg-green-200 text-green-800' : '' }}
                                    {{ $planilla->estado === 'pendiente' ? 'bg-red-200 text-red-800' : '' }}
                                    {{ $planilla->estado === 'fabricando' ? 'bg-blue-200 text-blue-800' : '' }}
                                    {{ $planilla->estado === 'montaje' ? 'bg-purple-200 text-purple-800' : '' }}">
                                    {{ ucfirst($planilla->estado) }}
                                </span>
                            </td>
                            <td class="p-2 text-center border dark:border-gray-700">
                                {{ $planilla->fecha_inicio ? Str::before($planilla->fecha_inicio, ' ') : '-' }}
                            </td>
                            <td class="p-2 text-center border dark:border-gray-700">
                                {{ $planilla->fecha_finalizacion ? Str::before($planilla->fecha_finalizacion, ' ') : '-' }}
                            </td>
                            <td class="p-2 text-center border dark:border-gray-700">
                                {{ $planilla->created_at->format('d/m/Y') }}
                            </td>
                            <td class="p-2 text-center border dark:border-gray-700">
                                {{ $planilla->fecha_estimada_entrega ? Str::before($planilla->fecha_estimada_entrega, ' ') : '-' }}
                            </td>
                            <td class="p-2 text-center border dark:border-gray-700">{{ $planilla->user->name ?? '-' }}</td>
                            <td class="p-2 text-center border dark:border-gray-700">
                                <span
                                    class="px-2 py-2 rounded text-xs font-semibold {{ $planilla->revisada ? 'bg-green-200 text-green-800' : 'bg-gray-200 text-gray-800' }}">
                                    {{ $planilla->revisada ? 'Sí' : 'No' }}
                                </span>
                            </td>
                            <td class="p-2 text-center border dark:border-gray-700">{{ $planilla->revisor->name ?? '-' }}</td>
                            <td class="p-2 text-center border dark:border-gray-700">
                                {{ $planilla->revisada_at ? $planilla->revisada_at->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="p-2 text-center border dark:border-gray-700">
                                <span
                                    class="px-2 py-2 rounded text-xs font-semibold {{ $planilla->aprobada ? 'bg-green-200 text-green-800' : 'bg-orange-200 text-orange-800' }}">
                                    {{ $planilla->aprobada ? 'Sí' : 'No' }}
                                </span>
                            </td>
                            <td class="p-2 text-center border dark:border-gray-700">
                                @if($planilla->aprobada && $planilla->aprobada_at)
                                    {{ $planilla->aprobada_at->format('d/m/Y H:i') }}
                                    @if($planilla->aprobador)
                                        <br><span class="text-xs text-gray-500">{{ $planilla->aprobador->name }}</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="p-2 text-center border dark:border-gray-700">
                                {{ $planilla->fecha_creacion_ferrawin_formateada ?? '-' }}
                            </td>
                            <td class="p-2 text-center border dark:border-gray-700">
                                @if($planilla->tiempo_fabricacion)
                                    {{ $planilla->tiempo_estimado_finalizacion_formato }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-2 py-2 border dark:border-gray-700 text-xs font-bold">
                                <div class="flex items-center space-x-2 justify-center">
                                    <!-- Botón Reimportar -->
                                    <button onclick="abrirModalReimportar({{ $planilla->id }})"
                                        class="w-6 h-6 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 flex items-center justify-center"
                                        title="Reimportar Planilla">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4 4v6h6M20 20v-6h-6M4 20l4.586-4.586M20 4l-4.586 4.586" />
                                        </svg>
                                    </button>

                                    <!-- Botón Aprobar planilla -->
                                    @if(!$planilla->aprobada)
                                        <button wire:click="aprobarPlanilla({{ $planilla->id }})"
                                            wire:confirm="¿Aprobar esta planilla? La fecha de entrega será {{ now()->addDays(7)->format('d/m/Y') }}"
                                            class="w-6 h-6 bg-orange-100 text-orange-600 rounded hover:bg-orange-200 flex items-center justify-center"
                                            title="Aprobar planilla (establecer fecha entrega)">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    @else
                                        <span class="w-6 h-6 bg-green-100 text-green-600 rounded flex items-center justify-center" title="Ya aprobada">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                                            </svg>
                                        </span>
                                    @endif

                                    <!-- Botón Marcar como revisada -->
                                    <button wire:click="toggleRevisada({{ $planilla->id }})"
                                        class="w-6 h-6 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200 flex items-center justify-center"
                                        title="Marcar como revisada">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                            fill="currentColor">
                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                                        </svg>
                                    </button>

                                    <!-- Botón Ver elementos de esta planilla -->
                                    <button wire:click="verElementosFiltrados({{ $planilla->id }})"
                                        class="w-6 h-6 bg-purple-100 text-purple-600 rounded hover:bg-purple-200 flex items-center justify-center"
                                        title="Ver elementos de esta planilla">
                                        📋
                                    </button>

                                    <!-- Botón Resumir etiquetas -->
                                    <button onclick="resumirEtiquetas({{ $planilla->id }}, null)"
                                        class="w-6 h-6 bg-teal-100 text-teal-600 rounded hover:bg-teal-200 flex items-center justify-center"
                                        title="Resumir etiquetas con mismo diámetro y dimensiones">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                        </svg>
                                    </button>

                                    <!-- Botón Resetear planilla -->
                                    <button onclick="resetearPlanilla({{ $planilla->id }}, '{{ $planilla->codigo }}')"
                                        class="w-6 h-6 bg-orange-100 text-orange-600 rounded hover:bg-orange-200 flex items-center justify-center"
                                        title="Resetear planilla">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    </button>

                                    <!-- Botón Ver Ensamblaje -->
                                    @if($planilla->entidades_count > 0)
                                        <a href="{{ route('planillas.ensamblaje', $planilla->id) }}"
                                            class="w-6 h-6 bg-emerald-100 text-emerald-600 rounded hover:bg-emerald-200 flex items-center justify-center"
                                            title="Ver ensamblaje ({{ $planilla->entidades_count }} entidades)">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                            </svg>
                                        </a>
                                    @endif

                                    <!-- Botón Ver -->
                                    <x-tabla.boton-ver :href="route('planillas.show', $planilla->id)" />

                                    <!-- Botón Eliminar -->
                                    <x-tabla.boton-eliminar :action="route('planillas.destroy', $planilla->id)" />
                                </div>
                            </td>
                        </x-tabla.row>
                    @empty
                        <x-tabla.row :clickable="false">
                            <td colspan="{{ $modoSeleccion ? 27 : 26 }}" class="text-center py-4 text-gray-500 dark:text-gray-400">No hay planillas registradas
                            </td>
                        </x-tabla.row>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Peso total filtrado -->
        @if ($totalPesoFiltrado > 0)
            <div class="mt-4 bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 border-l-4 border-blue-500 rounded-r-lg p-3">
                <div class="flex justify-end items-center gap-4 text-sm text-gray-700 dark:text-gray-300">
                    <span class="font-semibold">Total peso filtrado:</span>
                    <span class="text-base font-bold text-blue-800 dark:text-blue-400">
                        {{ number_format($totalPesoFiltrado, 2, ',', '.') }} kg
                    </span>
                </div>
            </div>
        @endif

        <x-tabla.paginacion-livewire :paginador="$planillas" />
    </div>

    {{-- Barra flotante de selección --}}
    @if ($modoSeleccion)
        <div class="fixed bottom-0 left-0 right-0 bg-gray-800 text-white p-4 shadow-lg z-50 transform transition-transform duration-300 {{ count($planillasSeleccionadas) > 0 ? 'translate-y-0' : 'translate-y-full' }}">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span class="text-lg font-semibold">
                        {{ count($planillasSeleccionadas) }} planilla{{ count($planillasSeleccionadas) !== 1 ? 's' : '' }} seleccionada{{ count($planillasSeleccionadas) !== 1 ? 's' : '' }}
                    </span>
                    @php
                        $idsSinAprobarPagina = $planillas->filter(fn($p) => !$p->aprobada)->pluck('id')->toArray();
                    @endphp
                    <button wire:click="seleccionarTodasPagina({{ json_encode($idsSinAprobarPagina) }})"
                        class="bg-gray-600 hover:bg-gray-500 text-white px-3 py-1 rounded text-sm transition-colors">
                        Seleccionar página ({{ count($idsSinAprobarPagina) }})
                    </button>
                    <button wire:click="deseleccionarTodas"
                        class="bg-gray-600 hover:bg-gray-500 text-white px-3 py-1 rounded text-sm transition-colors">
                        Deseleccionar todas
                    </button>
                </div>
                <div class="flex items-center gap-3">
                    <button wire:click="toggleModoSeleccion"
                        class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded transition-colors">
                        Cancelar
                    </button>
                    <button onclick="confirmarAprobacionMasiva({{ count($planillasSeleccionadas) }}, '{{ now()->addDays(7)->format('d/m/Y') }}')"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded font-bold transition-colors flex items-center gap-2"
                        {{ count($planillasSeleccionadas) === 0 ? 'disabled' : '' }}>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        Aprobar seleccionadas
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Reimportar Planilla --}}
    <div id="modal-reimportar"
        class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg w-11/12 max-w-lg transform transition-all">
            <div class="px-6 py-4">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Añade modificaciones del cliente</h2>

                <form id="form-reimportar" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label for="archivo-reimportar" class="block text-sm font-medium text-gray-700">
                            Selecciona el nuevo archivo:
                        </label>
                        <input type="file" name="archivo" id="archivo-reimportar" accept=".csv,.xlsx,.xls"
                            required class="mt-1 block w-full border border-gray-300 rounded p-2 text-sm">
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="cerrarModalReimportar()"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold px-4 py-2 rounded">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                            Reimportar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Configurar Planilla (antes de resetear) --}}
    <div id="modal-config-planilla"
        class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-800">
                    Configurar Planilla: <span id="config-planilla-codigo" class="text-blue-600"></span>
                </h2>
                <button onclick="cerrarModalConfig()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="flex-1 overflow-y-auto px-6 py-4">
                {{-- Info de la planilla --}}
                <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
                        <div><span class="text-gray-500">Cliente:</span> <span id="config-cliente" class="font-medium"></span></div>
                        <div><span class="text-gray-500">Obra:</span> <span id="config-obra" class="font-medium"></span></div>
                        <div><span class="text-gray-500">Estado:</span> <span id="config-estado" class="font-medium"></span></div>
                    </div>
                </div>

                {{-- Fecha estimada de entrega --}}
                <div class="mb-6">
                    <label for="config-fecha-entrega" class="block text-sm font-medium text-gray-700 mb-1">
                        Fecha estimada de entrega
                    </label>
                    <input type="datetime-local" id="config-fecha-entrega"
                        class="w-full md:w-64 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Tabla de elementos --}}
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="font-medium text-gray-700">Elementos y asignaciones de maquinas</h3>
                        <span id="config-elementos-count" class="text-sm text-gray-500"></span>
                    </div>

                    {{-- Filtro por diametro --}}
                    <div class="mb-3 flex gap-2 items-center">
                        <label class="text-sm text-gray-600">Filtrar por diametro:</label>
                        <select id="config-filtro-diametro" onchange="filtrarElementosPorDiametro()"
                            class="border border-gray-300 rounded px-2 py-1 text-sm">
                            <option value="">Todos</option>
                        </select>
                        <button onclick="asignarMaquinaATodos()" class="ml-auto text-sm bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200">
                            Asignar maquina a filtrados
                        </button>
                    </div>

                    <div class="border rounded-lg overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-3 py-2 text-left">Marca</th>
                                    <th class="px-3 py-2 text-left">Diametro</th>
                                    <th class="px-3 py-2 text-left">Forma</th>
                                    <th class="px-3 py-2 text-left">Uds</th>
                                    <th class="px-3 py-2 text-left">Long.</th>
                                    <th class="px-3 py-2 text-left">Maquina</th>
                                </tr>
                            </thead>
                            <tbody id="config-elementos-tbody" class="divide-y divide-gray-200">
                                {{-- Se llena dinamicamente --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center bg-gray-50">
                <button onclick="cerrarModalConfig()"
                    class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                    Cancelar
                </button>
                <div class="flex gap-2">
                    <button onclick="guardarConfigPlanilla(false)"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Guardar cambios
                    </button>
                    <button onclick="guardarConfigPlanilla(true)"
                        class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                        Guardar y Resetear
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- SweetAlert2 --}}
    @if (!isset($swalLoaded))
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @endif

    <script>
        // Persistir entre navegaciones Livewire para evitar redeclaraciones
        var planillaIdReimportar = window.planillaIdReimportar || null;
        window.planillaIdReimportar = planillaIdReimportar;

        function abrirModalReimportar(planillaId) {
            planillaIdReimportar = planillaId;
            const modal = document.getElementById('modal-reimportar');
            const form = document.getElementById('form-reimportar');
            form.action = `/planillas/${planillaId}/reimportar`;
            modal.classList.remove('hidden');
        }

        function cerrarModalReimportar() {
            const modal = document.getElementById('modal-reimportar');
            modal.classList.add('hidden');
            planillaIdReimportar = null;
        }

        // ========== Modal Configurar Planilla ==========
        var configPlanillaData = {
            planillaId: null,
            planillaCodigo: null,
            elementos: [],
            maquinas: [],
            elementosFiltrados: []
        };

        window.resetearPlanilla = async function(planillaId, codigoPlanilla) {
            // Abrir modal de configuracion en lugar de resetear directamente
            await abrirModalConfigPlanilla(planillaId, codigoPlanilla);
        };

        async function abrirModalConfigPlanilla(planillaId, codigoPlanilla) {
            configPlanillaData.planillaId = planillaId;
            configPlanillaData.planillaCodigo = codigoPlanilla;

            // Mostrar loading
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Cargando...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });
            }

            try {
                const response = await fetch(`/planillas/${planillaId}/config-reset`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (typeof Swal !== 'undefined') Swal.close();

                if (!data.success) {
                    throw new Error(data.message || 'Error al cargar datos');
                }

                // Guardar datos
                configPlanillaData.elementos = data.elementos;
                configPlanillaData.maquinas = data.maquinas;
                configPlanillaData.elementosFiltrados = [...data.elementos];

                // Llenar modal
                document.getElementById('config-planilla-codigo').textContent = data.planilla.codigo;
                document.getElementById('config-cliente').textContent = data.planilla.cliente || '-';
                document.getElementById('config-obra').textContent = data.planilla.obra || '-';
                document.getElementById('config-estado').textContent = data.planilla.estado || '-';
                document.getElementById('config-fecha-entrega').value = data.planilla.fecha_estimada_entrega || '';

                // Llenar select de diametros
                const diametros = [...new Set(data.elementos.map(e => e.diametro))].sort((a, b) => a - b);
                const selectDiametro = document.getElementById('config-filtro-diametro');
                selectDiametro.innerHTML = '<option value="">Todos</option>';
                diametros.forEach(d => {
                    selectDiametro.innerHTML += `<option value="${d}">${d} mm</option>`;
                });

                // Renderizar tabla
                renderizarTablaElementos(data.elementos);

                // Mostrar modal
                document.getElementById('modal-config-planilla').classList.remove('hidden');

            } catch (error) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', error.message, 'error');
                } else {
                    alert(error.message);
                }
            }
        }

        function renderizarTablaElementos(elementos) {
            const tbody = document.getElementById('config-elementos-tbody');
            const countSpan = document.getElementById('config-elementos-count');

            countSpan.textContent = `${elementos.length} elementos`;

            if (elementos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-3 py-4 text-center text-gray-500">No hay elementos</td></tr>';
                return;
            }

            tbody.innerHTML = elementos.map(elem => {
                const maquinaOptions = configPlanillaData.maquinas.map(m => {
                    const selected = m.id === elem.maquina_id ? 'selected' : '';
                    const compatible = (!m.diametro_min || elem.diametro >= m.diametro_min) &&
                                       (!m.diametro_max || elem.diametro <= m.diametro_max);
                    const style = compatible ? '' : 'color: #999;';
                    return `<option value="${m.id}" ${selected} style="${style}">${m.codigo} - ${m.nombre}</option>`;
                }).join('');

                return `
                    <tr class="hover:bg-gray-50" data-elemento-id="${elem.id}" data-diametro="${elem.diametro}">
                        <td class="px-3 py-2 font-medium">${elem.marca || '-'}</td>
                        <td class="px-3 py-2">${elem.diametro} mm</td>
                        <td class="px-3 py-2">${elem.forma || '-'}</td>
                        <td class="px-3 py-2">${elem.n_unidades || '-'}</td>
                        <td class="px-3 py-2">${elem.longitud || '-'}</td>
                        <td class="px-3 py-2">
                            <select class="maquina-select border border-gray-300 rounded px-2 py-1 text-sm w-full"
                                    data-elemento-id="${elem.id}">
                                <option value="">Sin asignar</option>
                                ${maquinaOptions}
                            </select>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function filtrarElementosPorDiametro() {
            const filtro = document.getElementById('config-filtro-diametro').value;
            let elementosFiltrados = configPlanillaData.elementos;

            if (filtro) {
                elementosFiltrados = elementosFiltrados.filter(e => e.diametro == filtro);
            }

            configPlanillaData.elementosFiltrados = elementosFiltrados;
            renderizarTablaElementos(elementosFiltrados);
        }

        function asignarMaquinaATodos() {
            const maquinaOptions = configPlanillaData.maquinas.map(m =>
                `<option value="${m.id}">${m.codigo} - ${m.nombre}</option>`
            ).join('');

            Swal.fire({
                title: 'Asignar maquina',
                html: `
                    <p class="mb-2 text-sm text-gray-600">Se asignara a ${configPlanillaData.elementosFiltrados.length} elementos filtrados</p>
                    <select id="swal-maquina-select" class="w-full border rounded px-3 py-2">
                        <option value="">Sin asignar</option>
                        ${maquinaOptions}
                    </select>
                `,
                showCancelButton: true,
                confirmButtonText: 'Asignar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => document.getElementById('swal-maquina-select').value
            }).then(result => {
                if (result.isConfirmed) {
                    const maquinaId = result.value || null;
                    const idsAActualizar = configPlanillaData.elementosFiltrados.map(e => e.id);

                    // Actualizar en memoria
                    configPlanillaData.elementos.forEach(e => {
                        if (idsAActualizar.includes(e.id)) {
                            e.maquina_id = maquinaId ? parseInt(maquinaId) : null;
                        }
                    });

                    // Actualizar selects visibles
                    document.querySelectorAll('.maquina-select').forEach(select => {
                        const elemId = parseInt(select.dataset.elementoId);
                        if (idsAActualizar.includes(elemId)) {
                            select.value = maquinaId || '';
                        }
                    });

                    Swal.fire({
                        icon: 'success',
                        title: 'Asignado',
                        text: `Maquina asignada a ${idsAActualizar.length} elementos`,
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        }

        async function guardarConfigPlanilla(resetearDespues = false) {
            // Recoger datos del formulario
            const fechaEntrega = document.getElementById('config-fecha-entrega').value;

            // Recoger asignaciones de maquinas desde los selects
            const elementosActualizados = [];
            document.querySelectorAll('.maquina-select').forEach(select => {
                elementosActualizados.push({
                    id: parseInt(select.dataset.elementoId),
                    maquina_id: select.value ? parseInt(select.value) : null
                });
            });

            // Tambien incluir elementos que no estan visibles (por filtro)
            const idsVisibles = elementosActualizados.map(e => e.id);
            configPlanillaData.elementos.forEach(elem => {
                if (!idsVisibles.includes(elem.id)) {
                    elementosActualizados.push({
                        id: elem.id,
                        maquina_id: elem.maquina_id
                    });
                }
            });

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Guardando...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });
            }

            try {
                // Guardar configuracion
                const saveResponse = await fetch(`/planillas/${configPlanillaData.planillaId}/config-reset`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf ?? '',
                    },
                    body: JSON.stringify({
                        fecha_estimada_entrega: fechaEntrega || null,
                        elementos: elementosActualizados
                    })
                });

                const saveData = await saveResponse.json();
                if (!saveData.success) {
                    throw new Error(saveData.message || 'Error al guardar');
                }

                if (resetearDespues) {
                    // Ejecutar reset
                    const resetResponse = await fetch(`/planillas/${configPlanillaData.planillaId}/resetear`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf ?? '',
                        }
                    });

                    const resetData = await resetResponse.json();

                    if (typeof Swal !== 'undefined') Swal.close();

                    if (resetData.success) {
                        const detalles = resetData.detalles ?? {};
                        await Swal.fire({
                            title: 'Planilla configurada y reseteada',
                            html: `
                                <p>Paquetes eliminados: <strong>${detalles.paquetes_eliminados ?? 0}</strong></p>
                                <p>Etiquetas reseteadas: <strong>${detalles.etiquetas_reseteadas ?? 0}</strong></p>
                                <p>Elementos reseteados: <strong>${detalles.elementos_reseteados ?? 0}</strong></p>
                                <p>Maquinas asignadas: <strong>${(detalles.maquinas_asignadas || []).join(', ') || '-'}</strong></p>
                            `,
                            icon: 'success',
                            confirmButtonText: 'Recargar',
                        });
                        window.location.reload();
                    } else {
                        throw new Error(resetData.message || 'Error al resetear');
                    }
                } else {
                    if (typeof Swal !== 'undefined') Swal.close();
                    await Swal.fire({
                        icon: 'success',
                        title: 'Guardado',
                        text: 'Configuracion guardada correctamente',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    cerrarModalConfig();
                    // Refrescar Livewire si existe
                    if (typeof Livewire !== 'undefined') {
                        Livewire.dispatch('refresh');
                    }
                }
            } catch (error) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', error.message, 'error');
                } else {
                    alert(error.message);
                }
            }
        }

        function cerrarModalConfig() {
            document.getElementById('modal-config-planilla').classList.add('hidden');
            configPlanillaData = {
                planillaId: null,
                planillaCodigo: null,
                elementos: [],
                maquinas: [],
                elementosFiltrados: []
            };
        }

        // Cerrar modal config al hacer click fuera
        document.getElementById('modal-config-planilla')?.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalConfig();
            }
        });

        // Cerrar con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModalReimportar();
                cerrarModalConfig();
            }
        });

        // Cerrar al hacer click fuera
        document.getElementById('modal-reimportar')?.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalReimportar();
            }
        });

        // Listener para mensajes de Livewire
        document.addEventListener('livewire:init', () => {
            Livewire.on('planilla-actualizada', (event) => {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: event[0].type === 'warning' ? 'warning' : 'success',
                        title: event[0].type === 'warning' ? 'Aviso' : 'Completado',
                        text: event[0].message,
                        timer: 2500,
                        showConfirmButton: false
                    });
                }
            });
        });

        // Confirmación de eliminación con SweetAlert2
        function confirmarEliminacion(url) {
            Swal.fire({
                title: '¿Eliminar planilla?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (response.ok) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Eliminada',
                                text: 'La planilla ha sido eliminada correctamente',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            // Refrescar el componente Livewire
                            @this.$refresh();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo eliminar la planilla'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error de conexión'
                        });
                    });
                }
            });
        }

        // Confirmación de aprobación masiva con SweetAlert2
        function confirmarAprobacionMasiva(cantidad, fechaEntrega) {
            Swal.fire({
                title: 'Aprobar planillas',
                html: '<div style="text-align: center;">' +
                    '<p style="font-size: 1.1rem; margin-bottom: 1rem;">¿Aprobar las <strong>' + cantidad + '</strong> planilla' + (cantidad !== 1 ? 's' : '') + ' seleccionada' + (cantidad !== 1 ? 's' : '') + '?</p>' +
                    '<p style="background-color: #fef3c7; padding: 0.75rem; border-radius: 0.5rem; color: #92400e;">' +
                    '<strong>Fecha de entrega:</strong> ' + fechaEntrega + ' para todas</p>' +
                    '</div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, aprobar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#6b7280',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.aprobarSeleccionadas();
                }
            });
        }
    </script>

    {{-- Script del sistema de resumen de etiquetas --}}
    <script src="{{ asset('js/resumir-etiquetas.js') }}"></script>
</div>
