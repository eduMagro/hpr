<x-app-layout>
    <x-slot name="title">Coladas - {{ config('app.name') }}</x-slot>

    <div class="w-full px-6 py-4">
        <!-- Cabecera -->
        <div class="mb-6 flex flex-wrap justify-between items-center gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('productos.index') }}"
                    class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2.5 px-4 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                    Volver a Materiales
                </a>
                <h1 class="text-2xl font-bold text-gray-800">Gestion de Coladas</h1>
            </div>
            <button onclick="abrirModalCrear()"
                class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-2.5 px-6 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
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

        <!-- Tabla de Coladas -->
        <div class="bg-white shadow-lg rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gradient-to-r from-blue-500 to-blue-600 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">ID</th>
                            <th class="px-4 py-3 text-left font-semibold">N Colada</th>
                            <th class="px-4 py-3 text-left font-semibold">Producto Base</th>
                            <th class="px-4 py-3 text-left font-semibold">Fabricante</th>
                            <th class="px-4 py-3 text-left font-semibold">Cod. Adherencia</th>
                            <th class="px-4 py-3 text-left font-semibold">Documento</th>
                            <th class="px-4 py-3 text-left font-semibold">Fecha Creacion</th>
                            <th class="px-4 py-3 text-center font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($coladas as $colada)
                            <tr class="hover:bg-blue-50 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-700">{{ $colada->id }}</td>
                                <td class="px-4 py-3">
                                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-semibold">
                                        {{ $colada->numero_colada }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    @if($colada->productoBase)
                                        {{ strtoupper($colada->productoBase->tipo) }} |
                                        {{ $colada->productoBase->diametro }} mm
                                        @if($colada->productoBase->longitud)
                                            | {{ $colada->productoBase->longitud }} m
                                        @endif
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $colada->fabricante->nombre ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $colada->codigo_adherencia ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($colada->documento)
                                        <a href="{{ route('coladas.descargar', $colada) }}"
                                            class="inline-flex items-center gap-1 text-green-600 hover:text-green-800 font-medium">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                            PDF
                                        </a>
                                    @else
                                        <span class="text-gray-400 text-xs">Sin documento</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 text-xs">
                                    {{ $colada->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-center gap-2">
                                        <button onclick="abrirModalEditar({{ $colada->id }}, '{{ $colada->numero_colada }}', {{ $colada->producto_base_id }}, {{ $colada->fabricante_id ?? 'null' }}, '{{ addslashes($colada->codigo_adherencia ?? '') }}', '{{ addslashes($colada->observaciones ?? '') }}')"
                                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded text-xs font-medium transition-colors">
                                            Editar
                                        </button>
                                        <form action="{{ route('coladas.destroy', $colada) }}" method="POST" class="inline form-eliminar">
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
                                <td colspan="8" class="text-center py-8">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        <span class="text-gray-500 font-medium">No hay coladas registradas</span>
                                        <button onclick="abrirModalCrear()" class="mt-2 text-blue-600 hover:text-blue-800 font-medium">
                                            Crear primera colada
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginacion -->
            @if($coladas->hasPages())
                <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                    {{ $coladas->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Crear Colada -->
    <div id="modalCrear" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg relative">
            <h2 class="text-xl font-semibold mb-4">Nueva Colada</h2>

            <form action="{{ route('coladas.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label for="numero_colada" class="block text-sm font-medium text-gray-700 mb-1">Numero de Colada *</label>
                    <input type="text" id="numero_colada" name="numero_colada" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Ej: ABC123456">
                </div>

                <div>
                    <label for="producto_base_id" class="block text-sm font-medium text-gray-700 mb-1">Producto Base *</label>
                    <select id="producto_base_id" name="producto_base_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccione un producto base</option>
                        @foreach ($productosBase as $producto)
                            <option value="{{ $producto->id }}">
                                {{ strtoupper($producto->tipo) }} | {{ $producto->diametro }} mm
                                @if($producto->longitud) | {{ $producto->longitud }} m @endif
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
                    <label for="codigo_adherencia" class="block text-sm font-medium text-gray-700 mb-1">Codigo de Adherencia</label>
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
                    <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
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
    <div id="modalEditar" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg relative">
            <h2 class="text-xl font-semibold mb-4">Editar Colada</h2>

            <form id="formEditar" action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="edit_numero_colada" class="block text-sm font-medium text-gray-700 mb-1">Numero de Colada *</label>
                    <input type="text" id="edit_numero_colada" name="numero_colada" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="edit_producto_base_id" class="block text-sm font-medium text-gray-700 mb-1">Producto Base *</label>
                    <select id="edit_producto_base_id" name="producto_base_id" required
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccione un producto base</option>
                        @foreach ($productosBase as $producto)
                            <option value="{{ $producto->id }}">
                                {{ strtoupper($producto->tipo) }} | {{ $producto->diametro }} mm
                                @if($producto->longitud) | {{ $producto->longitud }} m @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="edit_fabricante_id" class="block text-sm font-medium text-gray-700 mb-1">Fabricante</label>
                    <select id="edit_fabricante_id" name="fabricante_id"
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccione un fabricante</option>
                        @foreach ($fabricantes as $fabricante)
                            <option value="{{ $fabricante->id }}">{{ $fabricante->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="edit_codigo_adherencia" class="block text-sm font-medium text-gray-700 mb-1">Codigo de Adherencia</label>
                    <input type="text" id="edit_codigo_adherencia" name="codigo_adherencia"
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Ej: ADH-2024-001">
                </div>

                <div>
                    <label for="edit_documento" class="block text-sm font-medium text-gray-700 mb-1">Documento PDF (dejar vacio para mantener actual)</label>
                    <input type="file" id="edit_documento" name="documento" accept=".pdf"
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Maximo 10MB. Solo archivos PDF.</p>
                </div>

                <div>
                    <label for="edit_observaciones" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
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

    <script>
        // Modal Crear
        function abrirModalCrear() {
            document.getElementById('modalCrear').classList.remove('hidden');
            document.getElementById('modalCrear').classList.add('flex');
        }

        function cerrarModalCrear() {
            document.getElementById('modalCrear').classList.remove('flex');
            document.getElementById('modalCrear').classList.add('hidden');
        }

        // Modal Editar
        function abrirModalEditar(id, numeroColada, productoBaseId, fabricanteId, codigoAdherencia, observaciones) {
            document.getElementById('formEditar').action = '/coladas/' + id;
            document.getElementById('edit_numero_colada').value = numeroColada;
            document.getElementById('edit_producto_base_id').value = productoBaseId;
            document.getElementById('edit_fabricante_id').value = fabricanteId || '';
            document.getElementById('edit_codigo_adherencia').value = codigoAdherencia || '';
            document.getElementById('edit_observaciones').value = observaciones;

            document.getElementById('modalEditar').classList.remove('hidden');
            document.getElementById('modalEditar').classList.add('flex');
        }

        function cerrarModalEditar() {
            document.getElementById('modalEditar').classList.remove('flex');
            document.getElementById('modalEditar').classList.add('hidden');
        }

        // Cerrar con ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                cerrarModalCrear();
                cerrarModalEditar();
            }
        });

        // Cerrar al hacer clic fuera
        window.addEventListener('click', function(event) {
            const modalCrear = document.getElementById('modalCrear');
            const modalEditar = document.getElementById('modalEditar');
            if (event.target === modalCrear) cerrarModalCrear();
            if (event.target === modalEditar) cerrarModalEditar();
        });

        // Confirmar eliminacion
        document.querySelectorAll('.form-eliminar').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Estas seguro?',
                    text: "Esta accion eliminara la colada de forma permanente.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Si, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
</x-app-layout>
