<div>
    <div class="w-full">

        <!-- Badge de planillas sin aprobar -->
        @if ($planillasSinAprobar > 0)
            <div class="my-4 bg-orange-100 border-l-4 border-orange-500 p-4 rounded-r-lg shadow">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">📋</span>
                        <div>
                            <h3 class="text-lg font-bold text-orange-800">
                                {{ $planillasSinAprobar }}
                                {{ $planillasSinAprobar === 1 ? 'planilla pendiente' : 'planillas pendientes' }} de
                                aprobación
                            </h3>
                            <p class="text-sm text-orange-700">
                                Las planillas deben ser aprobadas para establecer la fecha de entrega
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="verSinAprobar"
                            class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded transition-colors">
                            Ver sin aprobar
                        </button>
                        <button wire:click="toggleModoSeleccion"
                            class="{{ $modoSeleccion ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} text-white font-bold py-2 px-4 rounded transition-colors flex items-center gap-2">
                            @if ($modoSeleccion)
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                                Cancelar selección
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Aprobar en masa
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Badge de planillas sin revisar -->
        @if ($planillasSinRevisar > 0)
            <div class="my-4 bg-yellow-100 border-l-4 border-yellow-500 p-4 rounded-r-lg shadow">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">⚠️</span>
                        <div>
                            <h3 class="text-lg font-bold text-yellow-800">
                                {{ $planillasSinRevisar }}
                                {{ $planillasSinRevisar === 1 ? 'planilla pendiente' : 'planillas pendientes' }} de
                                revisión
                            </h3>
                            <p class="text-sm text-yellow-700">
                                Las planillas sin revisar aparecen en <strong>GRIS</strong> en el calendario de
                                producción
                            </p>
                        </div>
                    </div>
                    <button wire:click="verSinRevisar"
                        class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded transition-colors">
                        Ver planillas sin revisar
                    </button>
                </div>
            </div>
        @endif

        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />
        <!-- Tabla con filtros Livewire -->
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[2000px] border border-gray-300 rounded-lg">
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
                        <th class="p-2 border">Peso Fabricado</th>
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
                        <th class="p-2 border">Acciones</th>

                    </x-tabla.header-row>
                    <x-tabla.filtro-row>
                        @if ($modoSeleccion)
                            <th class="p-1 border"></th>
                        @endif
                        <th class="p-1 border"></th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="codigo"
                                class="w-full text-xs px-2 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Código...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="codigo_cliente"
                                class="w-full text-xs px-2 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Cód. Cliente...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="cliente"
                                class="w-full text-xs px-2 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Cliente...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="cod_obra"
                                class="w-full text-xs px-2 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Cód. Obra...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="nom_obra"
                                class="w-full text-xs px-2 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Obra...">
                        </th>
                        <th class="p-1 border relative" x-data="{ open: @entangle('seccionesDropdownAbierto') }" @click.outside="open = false">
                            <div class="relative">
                                <button type="button" @click="open = !open"
                                    class="w-full text-xs px-2 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none flex items-center justify-between gap-1 {{ count($secciones) > 0 || !empty($seccionTextoLibre) ? 'bg-blue-50 border-blue-400' : '' }}">
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

                                <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     class="absolute z-50 mt-1 w-56 bg-white border border-gray-300 rounded-lg shadow-lg"
                                     style="left: 0;" @click.stop>
                                    {{-- Header con título y limpiar --}}
                                    <div class="sticky top-0 bg-gray-100 px-3 py-2 border-b flex justify-between items-center">
                                        <span class="text-xs font-semibold text-gray-700">Secciones</span>
                                        @if(count($secciones) > 0 || !empty($seccionTextoLibre))
                                            <button wire:click="limpiarSecciones" class="text-xs text-red-600 hover:text-red-800">
                                                Limpiar
                                            </button>
                                        @endif
                                    </div>

                                    {{-- Input de texto libre --}}
                                    <div class="px-3 py-2 border-b bg-gray-50">
                                        <label class="text-xs text-gray-600 mb-1 block">Búsqueda libre:</label>
                                        <input type="text"
                                            wire:model.live.debounce.300ms="seccionTextoLibre"
                                            placeholder="Escribir sección..."
                                            class="w-full text-xs px-2 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none {{ !empty($seccionTextoLibre) ? 'bg-blue-50 border-blue-400' : '' }}"
                                            {{ count($secciones) > 0 ? 'disabled' : '' }}>
                                        @if(count($secciones) > 0)
                                            <p class="text-xs text-gray-400 mt-1">Deselecciona los checks para usar texto libre</p>
                                        @endif
                                    </div>

                                    {{-- Separador --}}
                                    <div class="px-3 py-1 bg-gray-100 border-b">
                                        <span class="text-xs text-gray-500">O selecciona de la lista:</span>
                                    </div>

                                    {{-- Lista de checkboxes --}}
                                    <div class="max-h-48 overflow-y-auto">
                                        @forelse($seccionesDisponibles as $seccionItem)
                                            <label class="flex items-center px-3 py-2 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0 {{ !empty($seccionTextoLibre) ? 'opacity-50' : '' }}">
                                                <input type="checkbox"
                                                    wire:click="toggleSeccion('{{ $seccionItem }}')"
                                                    {{ in_array($seccionItem, $secciones) ? 'checked' : '' }}
                                                    {{ !empty($seccionTextoLibre) ? 'disabled' : '' }}
                                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                                                <span class="ml-2 text-xs text-gray-700">{{ $seccionItem }}</span>
                                            </label>
                                        @empty
                                            <div class="px-3 py-2 text-xs text-gray-500">No hay secciones</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="descripcion"
                                class="w-full text-xs px-2 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Descripción...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="ensamblado"
                                class="w-full text-xs px-2 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Ensamblado...">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="comentario"
                                class="w-full text-xs px-2 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Comentario...">
                        </th>
                        <th class="p-1 border"></th> {{-- Peso Fabricado --}}
                        <th class="p-1 border"></th> {{-- Peso Total --}}
                        <th class="p-1 border">
                            <select wire:model.live="estado"
                                class="w-full text-xs px-1 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                                <option value="">Todos</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="fabricando">Fabricando</option>
                                <option value="completada">Completada</option>
                                <option value="montaje">Montaje</option>
                            </select>
                        </th>
                        <th class="p-1 border">
                            <input type="date" wire:model.live.debounce.300ms="fecha_inicio"
                                class="w-full text-xs px-1 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                        </th>
                        <th class="p-1 border">
                            <input type="date" wire:model.live.debounce.300ms="fecha_finalizacion"
                                class="w-full text-xs px-1 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                        </th>
                        <th class="p-1 border">
                            <input type="date" wire:model.live.debounce.300ms="fecha_importacion"
                                class="w-full text-xs px-1 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                        </th>
                        <th class="p-1 border">
                            <input type="date" wire:model.live.debounce.300ms="fecha_estimada_entrega"
                                class="w-full text-xs px-1 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                        </th>
                        <th class="p-1 border">
                            <input type="text" wire:model.live.debounce.300ms="usuario"
                                class="w-full text-xs px-2 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Usuario...">
                        </th>
                        <th class="p-1 border">
                            <select wire:model.live="revisada"
                                class="w-full text-xs px-1 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                                <option value="">Todas</option>
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </th>
                        <th class="p-1 border"></th>
                        <th class="p-1 border"></th>
                        <th class="p-1 border">
                            <select wire:model.live="aprobada"
                                class="w-full text-xs px-1 py-1 border rounded text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                                <option value="">Todas</option>
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </th>
                        <th class="p-1 border"></th>
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

                <tbody class="text-gray-700">
                    @forelse ($planillas as $planilla)
                        <tr
                            class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-gray-200 cursor-pointer text-xs leading-none uppercase transition-colors {{ in_array($planilla->id, $planillasSeleccionadas) ? '!bg-green-100' : '' }}">
                            @if ($modoSeleccion)
                                <td class="p-2 text-center border">
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
                            <td class="p-2 text-center border">{{ $planilla->id }}</td>
                            <td class="p-2 text-center border">
                                <a href="{{ route('planillas.show', $planilla->id) }}"
                                    class="text-blue-500 hover:underline">
                                    {{ $planilla->codigo_limpio }}
                                </a>
                            </td>
                            <td class="p-2 text-center border">{{ $planilla->cliente->codigo ?? 'N/A' }}</td>
                            <td class="p-2 text-center border">
                                @if ($planilla->cliente_id)
                                    <a href="{{ route('clientes.index', ['id' => $planilla->cliente_id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $planilla->cliente->empresa ?? 'N/A' }}
                                    </a>
                                @else
                                    {{ $planilla->cliente->empresa ?? 'N/A' }}
                                @endif
                            </td>
                            <td class="p-2 text-center border">{{ $planilla->obra->cod_obra ?? 'N/A' }}</td>
                            <td class="p-2 text-center border">
                                @if ($planilla->cliente_id)
                                    <a href="{{ route('clientes.show', ['cliente' => $planilla->cliente_id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $planilla->obra->obra ?? 'N/A' }}
                                    </a>
                                @else
                                    {{ $planilla->obra->obra ?? 'N/A' }}
                                @endif
                            </td>
                            <td class="p-2 text-center border">{{ $planilla->seccion }}</td>
                            <td class="p-2 text-center border">{{ $planilla->descripcion }}</td>
                            <td class="p-2 text-center border">{{ $planilla->ensamblado }}</td>
                            <td class="p-2 text-center border">{{ $planilla->comentario }}</td>
                            <td class="p-2 text-center border">
                                {{ number_format($planilla->suma_peso_completados ?? 0, 2) }} kg</td>
                            <td class="p-2 text-center border">{{ number_format($planilla->peso_total, 2) }} kg</td>
                            <td class="p-2 text-center border">
                                <span
                                    class="px-2 py-2 rounded text-xs font-semibold
                                    {{ $planilla->estado === 'completada' ? 'bg-green-200 text-green-800' : '' }}
                                    {{ $planilla->estado === 'pendiente' ? 'bg-red-200 text-red-800' : '' }}
                                    {{ $planilla->estado === 'fabricando' ? 'bg-blue-200 text-blue-800' : '' }}
                                    {{ $planilla->estado === 'montaje' ? 'bg-purple-200 text-purple-800' : '' }}">
                                    {{ ucfirst($planilla->estado) }}
                                </span>
                            </td>
                            <td class="p-2 text-center border">
                                {{ $planilla->fecha_inicio ? Str::before($planilla->fecha_inicio, ' ') : '-' }}
                            </td>
                            <td class="p-2 text-center border">
                                {{ $planilla->fecha_finalizacion ? Str::before($planilla->fecha_finalizacion, ' ') : '-' }}
                            </td>
                            <td class="p-2 text-center border">
                                {{ $planilla->created_at->format('d/m/Y') }}
                            </td>
                            <td class="p-2 text-center border">
                                {{ $planilla->fecha_estimada_entrega ? Str::before($planilla->fecha_estimada_entrega, ' ') : '-' }}
                            </td>
                            <td class="p-2 text-center border">{{ $planilla->user->name ?? '-' }}</td>
                            <td class="p-2 text-center border">
                                <span
                                    class="px-2 py-2 rounded text-xs font-semibold {{ $planilla->revisada ? 'bg-green-200 text-green-800' : 'bg-gray-200 text-gray-800' }}">
                                    {{ $planilla->revisada ? 'Sí' : 'No' }}
                                </span>
                            </td>
                            <td class="p-2 text-center border">{{ $planilla->revisor->name ?? '-' }}</td>
                            <td class="p-2 text-center border">
                                {{ $planilla->revisada_at ? $planilla->revisada_at->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="p-2 text-center border">
                                <span
                                    class="px-2 py-2 rounded text-xs font-semibold {{ $planilla->aprobada ? 'bg-green-200 text-green-800' : 'bg-orange-200 text-orange-800' }}">
                                    {{ $planilla->aprobada ? 'Sí' : 'No' }}
                                </span>
                            </td>
                            <td class="p-2 text-center border">
                                @if($planilla->aprobada && $planilla->aprobada_at)
                                    {{ $planilla->aprobada_at->format('d/m/Y H:i') }}
                                    @if($planilla->aprobador)
                                        <br><span class="text-xs text-gray-500">{{ $planilla->aprobador->name }}</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-2 py-2 border text-xs font-bold">
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $modoSeleccion ? 25 : 24 }}" class="text-center py-4 text-gray-500">No hay planillas registradas
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Peso total filtrado -->
        @if ($totalPesoFiltrado > 0)
            <div class="mt-4 bg-gradient-to-r from-blue-50 to-blue-100 border-l-4 border-blue-500 rounded-r-lg p-3">
                <div class="flex justify-end items-center gap-4 text-sm text-gray-700">
                    <span class="font-semibold">Total peso filtrado:</span>
                    <span class="text-base font-bold text-blue-800">
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

        // Cerrar con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModalReimportar();
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
