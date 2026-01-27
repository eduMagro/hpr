<div>
    <div class="w-full px-6 py-4">
        <!-- Cabecera -->
        <div class="mb-6 flex flex-wrap justify-between items-center gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('productos.index') }}"
                    class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2.5 px-4 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                            clip-rule="evenodd" />
                    </svg>
                    Volver a Materiales
                </a>
                <h1 class="text-2xl font-bold text-gray-800">Gestión de coladas</h1>
            </div>
            <button onclick="abrirModalCrear()"
                class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-2.5 px-6 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                        clip-rule="evenodd" />
                </svg>
                Nueva Colada
            </button>
        </div>

        <!-- Mensajes flash -->
        @if (session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Filtros aplicados -->
        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

        <!-- Tabla de Coladas -->
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[900px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white text-10">
                    <tr class="text-center text-xs uppercase">
                        <x-tabla.encabezado-ordenable campo="id" :sortActual="$sort" :orderActual="$order"
                            texto="ID" />
                        <x-tabla.encabezado-ordenable campo="numero_colada" :sortActual="$sort" :orderActual="$order"
                            texto="N Colada" />
                        <x-tabla.encabezado-ordenable campo="producto_base" :sortActual="$sort" :orderActual="$order"
                            texto="Producto Base" />
                        <x-tabla.encabezado-ordenable campo="fabricante" :sortActual="$sort" :orderActual="$order"
                            texto="Fabricante" />
                        <x-tabla.encabezado-ordenable campo="codigo_adherencia" :sortActual="$sort" :orderActual="$order"
                            texto="Cod. Adherencia" />
                        <th class="p-2 border border-blue-400 dark:border-blue-600 cursor-pointer hover:bg-blue-500 dark:hover:bg-blue-600 transition-colors">Dió de alta</th>
                        <th class="p-2 border border-blue-400 dark:border-blue-600 cursor-pointer hover:bg-blue-500 dark:hover:bg-blue-600 transition-colors">Última mod.</th>
                        <th class="p-2 border border-blue-400 dark:border-blue-600 cursor-pointer hover:bg-blue-500 dark:hover:bg-blue-600 transition-colors">Documento</th>
                        <x-tabla.encabezado-ordenable campo="created_at" :sortActual="$sort" :orderActual="$order"
                            texto="Fecha Creacion" />
                        <th class="p-2 border border-blue-400 dark:border-blue-600 cursor-pointer hover:bg-blue-500 dark:hover:bg-blue-600 transition-colors">Acciones</th>
                    </tr>

                    <!-- Fila de filtros -->
                    <tr class="text-center text-xs uppercase">
                        <th class="p-2 border border-blue-400 dark:border-blue-600 cursor-pointer hover:bg-blue-500 dark:hover:bg-blue-600 transition-colors">
                            <input type="text" wire:model.live.debounce.300ms="colada_id"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="ID...">
                        </th>
                        <th class="p-2 border border-blue-400 dark:border-blue-600 cursor-pointer hover:bg-blue-500 dark:hover:bg-blue-600 transition-colors">
                            <input type="text" wire:model.live.debounce.300ms="numero_colada"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="N Colada...">
                        </th>
                        <th class="p-2 border border-blue-400 dark:border-blue-600 cursor-pointer hover:bg-blue-500 dark:hover:bg-blue-600 transition-colors">
                            <input type="text" wire:model.live.debounce.300ms="producto_base"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Tipo/Diametro...">
                        </th>
                        <th class="p-2 border border-blue-400 dark:border-blue-600 cursor-pointer hover:bg-blue-500 dark:hover:bg-blue-600 transition-colors">
                            <input type="text" wire:model.live.debounce.300ms="fabricante"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Fabricante...">
                        </th>
                        <th class="p-2 border border-blue-400 dark:border-blue-600 cursor-pointer hover:bg-blue-500 dark:hover:bg-blue-600 transition-colors">
                            <input type="text" wire:model.live.debounce.300ms="codigo_adherencia"
                                class="w-full text-xs border rounded px-2 py-1.5 text-blue-900 bg-white focus:border-blue-900 focus:ring-1 focus:ring-blue-900 focus:outline-none"
                                placeholder="Cod. Adher...">
                        </th>
                        <th class="p-2 border border-blue-400 dark:border-blue-600 cursor-pointer hover:bg-blue-500 dark:hover:bg-blue-600 transition-colors">
                            <!-- Sin filtro para quien dio de alta -->
                        </th>
                        <th class="p-2 border border-blue-400 dark:border-blue-600 cursor-pointer hover:bg-blue-500 dark:hover:bg-blue-600 transition-colors">
                            <!-- Sin filtro para ultima mod -->
                        </th>
                        <th class="p-2 border border-blue-400 dark:border-blue-600 cursor-pointer hover:bg-blue-500 dark:hover:bg-blue-600 transition-colors">
                            <!-- Sin filtro para documento -->
                        </th>
                        <th class="p-2 border border-blue-400 dark:border-blue-600 cursor-pointer hover:bg-blue-500 dark:hover:bg-blue-600 transition-colors">
                            <!-- Sin filtro para fecha -->
                        </th>
                        <th class="p-2 border border-blue-400 dark:border-blue-600 cursor-pointer hover:bg-blue-500 dark:hover:bg-blue-600 transition-colors text-center align-middle">
                            <div class="flex justify-center gap-2 items-center h-full">
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
                    @forelse ($coladas as $colada)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-50 transition-colors text-xs">
                            <td class="px-4 py-3 text-center font-medium text-gray-700">{{ $colada->id }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-semibold">
                                    {{ $colada->numero_colada }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">
                                @if ($colada->productoBase)
                                    {{ strtoupper($colada->productoBase->tipo) }} |
                                    {{ $colada->productoBase->diametro }} mm
                                    @if ($colada->productoBase->longitud)
                                        | {{ $colada->productoBase->longitud }} m
                                    @endif
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">
                                {{ $colada->fabricante->nombre ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">
                                {{ $colada->codigo_adherencia ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">
                                <span class="capitalize">{{ $colada->dioDeAltaPor->name ?? 'Sistema' }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">
                                <span class="capitalize">{{ $colada->ultimoModificadoPor->name ?? '-' }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($colada->documento)
                                    <a href="{{ route('coladas.descargar', $colada) }}"
                                        class="inline-flex items-center gap-1 text-green-600 hover:text-green-800 font-medium">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        PDF
                                    </a>
                                @else
                                    <span class="text-gray-400 text-xs">Sin documento</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600 text-xs">
                                {{ $colada->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-center gap-2">
                                    <button
                                        onclick="abrirModalEditar({{ $colada->id }}, '{{ $colada->numero_colada }}', {{ $colada->producto_base_id }}, {{ $colada->fabricante_id ?? 'null' }}, '{{ addslashes($colada->codigo_adherencia ?? '') }}', '{{ addslashes($colada->observaciones ?? '') }}', {{ $colada->documento ? '\'' . addslashes($colada->documento) . '\'' : 'null' }})"
                                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded text-xs font-medium transition-colors">
                                        Editar
                                    </button>
                                    <form action="{{ route('coladas.destroy', $colada) }}" method="POST"
                                        class="inline form-eliminar">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-xs font-medium transition-colors">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-8">
                                <div class="flex flex-col items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <span class="text-gray-500 font-medium">No hay coladas registradas</span>
                                    <button onclick="abrirModalCrear()"
                                        class="mt-2 text-blue-600 hover:text-blue-800 font-medium">
                                        Crear primera colada
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginacion Livewire -->
        <div class="mt-4">
            {{ $coladas->links('vendor.livewire.tailwind') }}
        </div>
    </div>

    <!-- Modal Crear Colada -->
    <div id="modalCrear" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center"
        wire:ignore.self>
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg relative">
            <h2 class="text-xl font-semibold mb-4">Nueva Colada</h2>

            <form action="{{ route('coladas.store') }}" method="POST" enctype="multipart/form-data"
                class="space-y-4">
                @csrf

                <div>
                    <label for="numero_colada" class="block text-sm font-medium text-gray-700 mb-1">Numero de Colada
                        *</label>
                    <input type="text" id="numero_colada" name="numero_colada" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Ej: ABC123456">
                </div>

                <div>
                    <label for="producto_base_id" class="block text-sm font-medium text-gray-700 mb-1">Producto Base
                        *</label>
                    <select id="producto_base_id" name="producto_base_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccione un producto base</option>
                        @foreach ($productosBase as $producto)
                            <option value="{{ $producto->id }}">
                                {{ strtoupper($producto->tipo) }} | {{ $producto->diametro }} mm
                                @if ($producto->longitud)
                                    | {{ $producto->longitud }} m
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="fabricante_id" class="block text-sm font-medium text-gray-700 mb-1">Fabricante</label>
                    <select id="fabricante_id" name="fabricante_id"
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccione un fabricante</option>
                        @foreach ($fabricantes as $fabricante)
                            <option value="{{ $fabricante->id }}">{{ $fabricante->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="codigo_adherencia" class="block text-sm font-medium text-gray-700 mb-1">Codigo de
                        Adherencia</label>
                    <input type="text" id="codigo_adherencia" name="codigo_adherencia"
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Ej: ADH-2024-001">
                </div>

                <div>
                    <label for="documento" class="block text-sm font-medium text-gray-700 mb-1">Documento PDF</label>
                    <input type="file" id="documento" name="documento" accept=".pdf"
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Maximo 10MB. Solo archivos PDF.</p>
                </div>

                <div>
                    <label for="observaciones"
                        class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" rows="3"
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Notas adicionales..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="cerrarModalCrear()"
                        class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Colada -->
    <div id="modalEditar" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center"
        wire:ignore.self>
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg relative">
            <h2 class="text-xl font-semibold mb-4">Editar Colada</h2>

            <form id="formEditar" action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="edit_numero_colada" class="block text-sm font-medium text-gray-700 mb-1">Numero de
                        Colada *</label>
                    <input type="text" id="edit_numero_colada" name="numero_colada" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="edit_producto_base_id" class="block text-sm font-medium text-gray-700 mb-1">Producto
                        Base *</label>
                    <select id="edit_producto_base_id" name="producto_base_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccione un producto base</option>
                        @foreach ($productosBase as $producto)
                            <option value="{{ $producto->id }}">
                                {{ strtoupper($producto->tipo) }} | {{ $producto->diametro }} mm
                                @if ($producto->longitud)
                                    | {{ $producto->longitud }} m
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="edit_fabricante_id"
                        class="block text-sm font-medium text-gray-700 mb-1">Fabricante</label>
                    <select id="edit_fabricante_id" name="fabricante_id"
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccione un fabricante</option>
                        @foreach ($fabricantes as $fabricante)
                            <option value="{{ $fabricante->id }}">{{ $fabricante->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="edit_codigo_adherencia" class="block text-sm font-medium text-gray-700 mb-1">Codigo de
                        Adherencia</label>
                    <input type="text" id="edit_codigo_adherencia" name="codigo_adherencia"
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Ej: ADH-2024-001">
                </div>

                <div>
                    <label for="edit_documento" class="block text-sm font-medium text-gray-700 mb-1">Documento PDF</label>

                    <!-- Indicador de documento actual -->
                    <div id="documento_actual_container" class="hidden mb-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-green-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-sm font-medium">Documento actual adjunto</span>
                            </div>
                            <a id="link_documento_actual" href="#" target="_blank"
                                class="text-xs text-blue-600 hover:text-blue-800 underline">
                                Ver PDF
                            </a>
                        </div>
                        <p class="text-xs text-green-600 mt-1">Solo sube un nuevo archivo si deseas reemplazarlo.</p>
                    </div>

                    <!-- Indicador sin documento -->
                    <div id="sin_documento_container" class="hidden mb-2 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                        <div class="flex items-center gap-2 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm">Sin documento adjunto</span>
                        </div>
                    </div>

                    <input type="file" id="edit_documento" name="documento" accept=".pdf"
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Maximo 10MB. Solo archivos PDF.</p>
                </div>

                <div>
                    <label for="edit_observaciones"
                        class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="edit_observaciones" name="observaciones" rows="3"
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="cerrarModalEditar()"
                        class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading indicator flotante -->
    <div wire:loading class="fixed top-4 right-4 bg-blue-500 text-white px-4 py-2 rounded-lg shadow-lg z-50">
        <div class="flex items-center gap-2">
            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                    stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <span>Filtrando...</span>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                // Definir funciones en window para que sean accesibles globalmente
                window.abrirModalCrear = function() {
                    const modal = document.getElementById('modalCrear');
                    if (modal) {
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                    }
                };

                window.cerrarModalCrear = function() {
                    const modal = document.getElementById('modalCrear');
                    if (modal) {
                        modal.classList.remove('flex');
                        modal.classList.add('hidden');
                    }
                };

                window.abrirModalEditar = function(id, numeroColada, productoBaseId, fabricanteId, codigoAdherencia,
                    observaciones, documento) {
                    const form = document.getElementById('formEditar');
                    const modal = document.getElementById('modalEditar');

                    if (form && modal) {
                        form.action = '/coladas/' + id;
                        const inputs = {
                            'edit_numero_colada': numeroColada,
                            'edit_producto_base_id': productoBaseId,
                            'edit_fabricante_id': fabricanteId || '',
                            'edit_codigo_adherencia': codigoAdherencia || '',
                            'edit_observaciones': observaciones
                        };

                        Object.entries(inputs).forEach(([id, value]) => {
                            const el = document.getElementById(id);
                            if (el) el.value = value;
                        });

                        // Mostrar/ocultar indicador de documento actual
                        const docActualContainer = document.getElementById('documento_actual_container');
                        const sinDocContainer = document.getElementById('sin_documento_container');
                        const linkDocumento = document.getElementById('link_documento_actual');
                        const inputFile = document.getElementById('edit_documento');

                        // Limpiar input de archivo
                        if (inputFile) inputFile.value = '';

                        if (documento) {
                            docActualContainer.classList.remove('hidden');
                            sinDocContainer.classList.add('hidden');
                            linkDocumento.href = '/storage/' + documento;
                        } else {
                            docActualContainer.classList.add('hidden');
                            sinDocContainer.classList.remove('hidden');
                        }

                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                    }
                };

                window.cerrarModalEditar = function() {
                    const modal = document.getElementById('modalEditar');
                    if (modal) {
                        modal.classList.remove('flex');
                        modal.classList.add('hidden');
                    }
                };

                function handleKeydown(event) {
                    if (event.key === 'Escape') {
                        window.cerrarModalCrear();
                        window.cerrarModalEditar();
                    }
                }

                function handleOutsideClick(event) {
                    const modalCrear = document.getElementById('modalCrear');
                    const modalEditar = document.getElementById('modalEditar');
                    if (event.target === modalCrear) window.cerrarModalCrear();
                    if (event.target === modalEditar) window.cerrarModalEditar();
                }

                function initDeleteForms() {
                    document.querySelectorAll('.form-eliminar').forEach(form => {
                        // Evitar duplicar listeners
                        if (form.dataset.listenerAttached === 'true') return;

                        form.dataset.listenerAttached = 'true';
                        form.addEventListener('submit', function(e) {
                            e.preventDefault();

                            Swal.fire({
                                title: '¿Estás seguro?',
                                text: "Esta acción eliminará la colada de forma permanente.",
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#dc2626',
                                cancelButtonColor: '#6b7280',
                                confirmButtonText: 'Sí, eliminar',
                                cancelButtonText: 'Cancelar'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    form.submit();
                                }
                            });
                        });
                    });
                }

                // Inicializar formulario de edición con AJAX
                function initEditForm() {
                    const form = document.getElementById('formEditar');
                    if (!form || form.dataset.ajaxAttached === 'true') return;

                    form.dataset.ajaxAttached = 'true';
                    form.addEventListener('submit', async function(e) {
                        e.preventDefault();

                        const submitBtn = form.querySelector('button[type="submit"]');
                        const originalText = submitBtn.innerHTML;

                        // Mostrar loading
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = `
                            <svg class="animate-spin h-5 w-5 inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Guardando...
                        `;

                        // Limpiar errores previos
                        form.querySelectorAll('.error-message').forEach(el => el.remove());
                        form.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));

                        try {
                            const formData = new FormData(form);

                            const response = await fetch(form.action, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                }
                            });

                            const data = await response.json();

                            if (data.success) {
                                // Cerrar modal
                                window.cerrarModalEditar();

                                // Mostrar mensaje de éxito
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Actualizado',
                                    text: data.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });

                                // Refrescar la tabla Livewire sin perder estado
                                if (typeof Livewire !== 'undefined') {
                                    const component = Livewire.getByName('coladas-table')[0];
                                    if (component) {
                                        component.$refresh();
                                    }
                                }
                            } else {
                                // Mostrar errores de validación
                                if (data.errors) {
                                    Object.keys(data.errors).forEach(field => {
                                        const input = form.querySelector(`[name="${field}"]`);
                                        if (input) {
                                            input.classList.add('border-red-500');
                                            const errorDiv = document.createElement('p');
                                            errorDiv.className = 'error-message text-red-500 text-xs mt-1';
                                            errorDiv.textContent = data.errors[field][0];
                                            input.parentNode.appendChild(errorDiv);
                                        }
                                    });
                                }

                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Por favor corrige los errores del formulario.',
                                });
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Ocurrió un error al actualizar la colada.',
                            });
                        } finally {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    });
                }

                // Función de inicialización de la página
                function initColadasTablePage() {
                    // Verificar si ya se inicializó
                    if (document.body.dataset.coladasTablePageInit === 'true') return;

                    console.log('Inicializando Coladas Table Page');

                    // Agregar listeners globales
                    document.addEventListener('keydown', handleKeydown);
                    window.addEventListener('click', handleOutsideClick);

                    // Inicializar formularios
                    initDeleteForms();
                    initEditForm();

                    // Hook para reinicializar formularios después de actualizaciones de Livewire (morph)
                    // Livewire 3 usa 'livewire:morph' o simplemente re-renders. 
                    // Siendo un componente, livewire:navigated se dispara una vez.
                    // Para actualizaciones parciales, necesitamos hooks.
                    // Pero como este script corre una vez por navegación (gracias al flag),
                    // necesitamos asegurarnos de que initDeleteForms se llame si el DOM cambia.
                    // Usaremos un hook de Livewire si está disponible.
                    if (typeof Livewire !== 'undefined' && Livewire.hook) {
                        Livewire.hook('morph.updated', ({
                            el,
                            component
                        }) => {
                            initDeleteForms();
                            initEditForm();
                        });
                    }

                    // Marcar como inicializado
                    document.body.dataset.coladasTablePageInit = 'true';
                }

                // Registrar en el sistema global de limpieza
                window.pageInitializers = window.pageInitializers || [];
                window.pageInitializers.push(() => {
                    document.removeEventListener('keydown', handleKeydown);
                    window.removeEventListener('click', handleOutsideClick);
                    // No necesitamos limpiar initDeleteForms porque los nodos se destruyen
                    document.body.dataset.coladasTablePageInit = 'false';
                });

                // Inicializar
                initColadasTablePage();

                // Listeners para navegación
                document.addEventListener('livewire:navigated', initColadasTablePage);
                document.addEventListener('DOMContentLoaded', initColadasTablePage);

                // Limpieza específica al navegar fuera
                document.addEventListener('livewire:navigating', () => {
                    document.body.dataset.coladasTablePageInit = 'false';
                    document.removeEventListener('keydown', handleKeydown);
                    window.removeEventListener('click', handleOutsideClick);
                });

            })();
        </script>
    @endpush
</div>
