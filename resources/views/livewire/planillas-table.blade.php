<div>
    <div class="w-full px-6 py-4">

        <!-- Badge de planillas sin revisar -->
        @if ($planillasSinRevisar > 0)
            <div class="mb-4 bg-yellow-100 border-l-4 border-yellow-500 p-4 rounded-r-lg shadow">
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
                <thead class="bg-blue-500 text-white text-4">
                    <tr class="text-center text-xs uppercase">
                        <x-tabla.encabezado-ordenable campo="id" :sortActual="$sort" :orderActual="$order" texto="ID" />
                        <x-tabla.encabezado-ordenable campo="codigo" :sortActual="$sort" :orderActual="$order" texto="Código" />
                        <x-tabla.encabezado-ordenable campo="codigo_cliente" :sortActual="$sort" :orderActual="$order" texto="Codigo Cliente" />
                        <x-tabla.encabezado-ordenable campo="cliente_id" :sortActual="$sort" :orderActual="$order" texto="Cliente" />
                        <x-tabla.encabezado-ordenable campo="codigo_obra" :sortActual="$sort" :orderActual="$order" texto="Código Obra" />
                        <x-tabla.encabezado-ordenable campo="obra_id" :sortActual="$sort" :orderActual="$order" texto="Obra" />
                        <x-tabla.encabezado-ordenable campo="seccion" :sortActual="$sort" :orderActual="$order" texto="Sección" />
                        <x-tabla.encabezado-ordenable campo="descripcion" :sortActual="$sort" :orderActual="$order" texto="Descripción" />
                        <x-tabla.encabezado-ordenable campo="ensamblado" :sortActual="$sort" :orderActual="$order" texto="Ensamblado" />
                        <x-tabla.encabezado-ordenable campo="comentario" :sortActual="$sort" :orderActual="$order" texto="Comentario" />
                        <th class="p-2 border">Peso Fabricado</th>
                        <x-tabla.encabezado-ordenable campo="peso_total" :sortActual="$sort" :orderActual="$order" texto="Peso Total" />
                        <x-tabla.encabezado-ordenable campo="estado" :sortActual="$sort" :orderActual="$order" texto="Estado" />
                        <x-tabla.encabezado-ordenable campo="fecha_inicio" :sortActual="$sort" :orderActual="$order" texto="Fecha Inicio" />
                        <x-tabla.encabezado-ordenable campo="fecha_finalizacion" :sortActual="$sort" :orderActual="$order" texto="Fecha Finalización" />
                        <x-tabla.encabezado-ordenable campo="created_at" :sortActual="$sort" :orderActual="$order" texto="Fecha Importación" />
                        <x-tabla.encabezado-ordenable campo="fecha_estimada_entrega" :sortActual="$sort" :orderActual="$order" texto="Fecha Entrega" />
                        <x-tabla.encabezado-ordenable campo="usuario_id" :sortActual="$sort" :orderActual="$order" texto="Usuario" />
                        <x-tabla.encabezado-ordenable campo="revisada" :sortActual="$sort" :orderActual="$order" texto="Revisada" />
                        <x-tabla.encabezado-ordenable campo="revisor_id" :sortActual="$sort" :orderActual="$order" texto="Revisada por" />
                        <x-tabla.encabezado-ordenable campo="revisada_at" :sortActual="$sort" :orderActual="$order" texto="Fecha revisión" />
                        <th class="p-2 border">Acciones</th>
                    </tr>

                    <tr class="text-center text-xs uppercase">
                        <th class="p-2 border"></th>
                        <th class="p-2 border">
                            <input type="text" wire:model.live.debounce.300ms="codigo"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Código...">
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
                            <input type="text" wire:model.live.debounce.300ms="seccion"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Sección...">
                        </th>
                        <th class="p-2 border">
                            <input type="text" wire:model.live.debounce.300ms="descripcion"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Descripción...">
                        </th>
                        <th class="p-2 border">
                            <input type="text" wire:model.live.debounce.300ms="ensamblado"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Ensamblado...">
                        </th>
                        <th class="p-2 border">
                            <input type="text" wire:model.live.debounce.300ms="comentario"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Comentario...">
                        </th>
                        <th class="p-2 border"></th> {{-- Peso Fabricado --}}
                        <th class="p-2 border"></th> {{-- Peso Total --}}
                        <th class="p-2 border">
                            <select wire:model.live="estado"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                                <option value="">Todos</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="fabricando">Fabricando</option>
                                <option value="completada">Completada</option>
                                <option value="montaje">Montaje</option>
                            </select>
                        </th>
                        <th class="p-2 border">
                            <input type="date" wire:model.live.debounce.300ms="fecha_inicio"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                        </th>
                        <th class="p-2 border">
                            <input type="date" wire:model.live.debounce.300ms="fecha_finalizacion"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                        </th>
                        <th class="p-2 border">
                            <input type="date" wire:model.live.debounce.300ms="fecha_importacion"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                        </th>
                        <th class="p-2 border">
                            <input type="date" wire:model.live.debounce.300ms="fecha_estimada_entrega"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                        </th>
                        <th class="p-2 border">
                            <input type="text" wire:model.live.debounce.300ms="usuario"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Usuario...">
                        </th>
                        <th class="p-2 border">
                            <select wire:model.live="revisada"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none">
                                <option value="">Todas</option>
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </th>
                        <th class="p-2 border"></th>
                        <th class="p-2 border"></th>
                        <th class="p-2 border text-center align-middle">
                            <button wire:click="limpiarFiltros"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                                title="Restablecer filtros">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                                </svg>
                            </button>
                        </th>
                    </tr>
                </thead>

                <tbody class="text-gray-700">
                    @forelse ($planillas as $planilla)
                        <tr wire:key="planilla-{{ $planilla->id }}"
                            class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer text-xs leading-none uppercase transition-colors">
                            <td class="p-2 text-center border">{{ $planilla->id }}</td>
                            <td class="p-2 text-center border">
                                <a href="{{ route('planillas.show', $planilla->id) }}"
                                    class="text-blue-500 hover:underline">
                                    {{ $planilla->codigo_limpio }}
                                </a>
                            </td>
                            <td class="p-2 text-center border">{{ $planilla->cliente->codigo ?? 'N/A' }}</td>
                            <td class="p-2 text-center border">
                                <a href="{{ route('clientes.index', ['id' => $planilla->cliente_id]) }}"
                                    class="text-blue-500 hover:underline">
                                    {{ $planilla->cliente->empresa ?? 'N/A' }}
                                </a>
                            </td>
                            <td class="p-2 text-center border">{{ $planilla->obra->cod_obra ?? 'N/A' }}</td>
                            <td class="p-2 text-center border">
                                <a href="{{ route('clientes.show', ['cliente' => $planilla->cliente_id]) }}"
                                    class="text-blue-500 hover:underline">
                                    {{ $planilla->obra->obra ?? 'N/A' }}
                                </a>
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
                                    class="px-2 py-1 rounded text-xs font-semibold
                                    {{ $planilla->estado === 'completada' ? 'bg-green-200 text-green-800' : '' }}
                                    {{ $planilla->estado === 'pendiente' ? 'bg-red-200 text-red-800' : '' }}
                                    {{ $planilla->estado === 'fabricando' ? 'bg-blue-200 text-blue-800' : '' }}
                                    {{ $planilla->estado === 'montaje' ? 'bg-purple-200 text-purple-800' : '' }}">
                                    {{ ucfirst($planilla->estado) }}
                                </span>
                            </td>
                            <td class="p-2 text-center border">
                                @if ($planilla->fecha_inicio)
                                    {{ is_string($planilla->fecha_inicio) && str_contains($planilla->fecha_inicio, '/')
                                        ? \Carbon\Carbon::createFromFormat('d/m/Y H:i', $planilla->fecha_inicio)->format('d/m/Y')
                                        : \Carbon\Carbon::parse($planilla->fecha_inicio)->format('d/m/Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="p-2 text-center border">
                                @if ($planilla->fecha_finalizacion)
                                    {{ is_string($planilla->fecha_finalizacion) && str_contains($planilla->fecha_finalizacion, '/')
                                        ? \Carbon\Carbon::createFromFormat('d/m/Y H:i', $planilla->fecha_finalizacion)->format('d/m/Y')
                                        : \Carbon\Carbon::parse($planilla->fecha_finalizacion)->format('d/m/Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="p-2 text-center border">
                                {{ is_string($planilla->created_at) && str_contains($planilla->created_at, '/')
                                    ? \Carbon\Carbon::createFromFormat('d/m/Y H:i', $planilla->created_at)->format('d/m/Y')
                                    : \Carbon\Carbon::parse($planilla->created_at)->format('d/m/Y') }}
                            </td>
                            <td class="p-2 text-center border">
                                @if ($planilla->fecha_estimada_entrega)
                                    {{ is_string($planilla->fecha_estimada_entrega) && str_contains($planilla->fecha_estimada_entrega, '/')
                                        ? \Carbon\Carbon::createFromFormat('d/m/Y H:i', $planilla->fecha_estimada_entrega)->format('d/m/Y')
                                        : \Carbon\Carbon::parse($planilla->fecha_estimada_entrega)->format('d/m/Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="p-2 text-center border">{{ $planilla->user->name ?? '-' }}</td>
                            <td class="p-2 text-center border">
                                <span
                                    class="px-2 py-1 rounded text-xs font-semibold {{ $planilla->revisada ? 'bg-green-200 text-green-800' : 'bg-gray-200 text-gray-800' }}">
                                    {{ $planilla->revisada ? 'Sí' : 'No' }}
                                </span>
                            </td>
                            <td class="p-2 text-center border">{{ $planilla->revisor->name ?? '-' }}</td>
                            <td class="p-2 text-center border">
                                @if ($planilla->fecha_revision)
                                    {{ is_string($planilla->fecha_revision) && str_contains($planilla->fecha_revision, '/')
                                        ? \Carbon\Carbon::createFromFormat('d/m/Y H:i', $planilla->fecha_revision)->format('d/m/Y H:i')
                                        : \Carbon\Carbon::parse($planilla->fecha_revision)->format('d/m/Y H:i') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-2 py-2 border text-xs font-bold">
                                <div class="flex items-center space-x-2 justify-center">
                                    <!-- Botón Resetear -->
                                    <button onclick="resetearPlanilla({{ $planilla->id }}, '{{ $planilla->codigo_limpio }}')"
                                        class="w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center"
                                        title="Resetear planilla a estado inicial">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>

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

                                    <!-- Botón Ver -->
                                    <x-tabla.boton-ver :href="route('planillas.show', $planilla->id)" />

                                    <!-- Botón Eliminar -->
                                    <x-tabla.boton-eliminar :action="route('planillas.destroy', $planilla->id)" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="22" class="text-center py-4 text-gray-500">No hay planillas registradas
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

        <!-- Paginación Livewire -->
        <div class="mt-4">
            {{ $planillas->links('vendor.livewire.tailwind') }}
        </div>
    </div>

    {{-- Modal Reimportar Planilla --}}
    <div id="modal-reimportar"
        class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg w-11/12 max-w-lg transform transition-all">
            <div class="px-6 py-4">
                <h2 class="text-lg font-bold text-gray-800 mb-4">📤 Añade modificaciones del cliente</h2>

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
                            🔄 Reimportar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Form para eliminar --}}
    <form id="formulario-eliminar" method="POST" style="display:none;">
        @csrf
        @method('DELETE')
    </form>

    {{-- SweetAlert2 --}}
    @if (!isset($swalLoaded))
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @endif

    <script>
        function confirmarEliminacion(actionUrl) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "¡No podrás revertir esta acción!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formulario = document.getElementById('formulario-eliminar');
                    formulario.action = actionUrl;
                    formulario.submit();
                }
            });
        }
    </script>

    <script>
        let planillaIdReimportar = null;

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

        // Función para resetear planilla
        async function resetearPlanilla(planillaId, codigo) {
            const result = await Swal.fire({
                title: '¿Resetear planilla?',
                html: `
                    <div class="text-left">
                        <p class="mb-3">Se reseteará la planilla <strong>${codigo}</strong> a su estado inicial:</p>
                        <ul class="list-disc ml-5 text-sm text-gray-600">
                            <li>Estado de planilla → <strong>Pendiente</strong></li>
                            <li>Etiquetas → Estado pendiente, sin operarios</li>
                            <li>Elementos → Estado pendiente, sin operarios</li>
                            <li>Paquetes → <strong class="text-red-600">Se eliminarán</strong></li>
                            <li>Fechas de fabricación → <strong>Se borrarán</strong></li>
                        </ul>
                        <p class="mt-3 text-red-600 font-semibold">⚠️ Esta acción no se puede deshacer</p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, resetear',
                cancelButtonText: 'Cancelar'
            });

            if (!result.isConfirmed) return;

            try {
                Swal.fire({
                    title: 'Reseteando planilla...',
                    text: 'Por favor espera',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const response = await fetch(`/planillas/${planillaId}/resetear`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    const maquinas = data.detalles.maquinas_asignadas?.length > 0
                        ? data.detalles.maquinas_asignadas.join(', ')
                        : 'Ninguna';

                    await Swal.fire({
                        icon: 'success',
                        title: 'Planilla reseteada',
                        html: `
                            <p>${data.message}</p>
                            <div class="mt-3 text-sm text-gray-600">
                                <p>Paquetes eliminados: <strong>${data.detalles.paquetes_eliminados}</strong></p>
                                <p>Etiquetas reseteadas: <strong>${data.detalles.etiquetas_reseteadas}</strong></p>
                                <p>Elementos reseteados: <strong>${data.detalles.elementos_reseteados}</strong></p>
                                <p>Máquina asignada: <strong>${maquinas}</strong></p>
                            </div>
                        `,
                        confirmButtonText: 'Aceptar'
                    });

                    // Recargar la tabla
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Error desconocido');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'No se pudo resetear la planilla'
                });
            }
        }

        // Listener para mensajes de Livewire
        document.addEventListener('livewire:init', () => {
            Livewire.on('planilla-actualizada', (event) => {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: event[0].message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        });
    </script>
</div>
