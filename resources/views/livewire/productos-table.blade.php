<div class="h-full max-h-[calc(100%-35px)] flex flex-col min-h-0 space-y-3">
    <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

        <div class="flex-1 min-h-0 flex flex-col">
            <x-tabla.wrapper minWidth="1000px">
                <x-tabla.header>
                {{-- Fila de encabezados --}}
                <x-tabla.header-row>
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
                    <th class="p-2">Acciones</th>
                </x-tabla.header-row>

                {{-- Fila de filtros --}}
                <x-tabla.filtro-row>
                    <x-tabla.filtro-input model="id" placeholder="ID" />
                    <x-tabla.filtro-input model="albaran" placeholder="Albarán" />
                    <x-tabla.filtro-input model="codigo" placeholder="Código" />

                    <x-tabla.filtro-select model="nave_id" placeholder="Todas">
                        @foreach ($naves as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </x-tabla.filtro-select>

                    <x-tabla.filtro-input model="fabricante" placeholder="Fabricante" />
                    <x-tabla.filtro-input model="tipo" placeholder="Tipo" />
                    <x-tabla.filtro-input model="diametro" placeholder="Ø" />
                    <x-tabla.filtro-input model="longitud" placeholder="Long." />
                    <x-tabla.filtro-input model="n_colada" placeholder="N° Colada" />
                    <x-tabla.filtro-input model="n_paquete" placeholder="N° Paquete" />
                    <x-tabla.filtro-vacio />
                    <x-tabla.filtro-vacio />

                    <x-tabla.filtro-select model="estado" placeholder="Todos">
                        <option value="almacenado">Almacenado</option>
                        <option value="fabricando">Fabricando</option>
                        <option value="consumido">Consumido</option>
                    </x-tabla.filtro-select>

                    <x-tabla.filtro-input model="ubicacion" placeholder="Ubicación" />
                    <x-tabla.filtro-vacio />
                    <x-tabla.filtro-acciones />
                </x-tabla.filtro-row>
            </x-tabla.header>

            <x-tabla.body>
                @forelse($productos as $producto)
                    <x-tabla.row>
                        <x-tabla.cell>{{ $producto->id }}</x-tabla.cell>

                        <x-tabla.cell>
                            @if ($producto->entrada)
                                <a href="{{ route('entradas.index', ['albaran' => $producto->entrada->albaran]) }}"
                                    class="text-blue-600 hover:underline">
                                    {{ $producto->entrada->albaran }}
                                </a>
                            @else
                                —
                            @endif
                        </x-tabla.cell>

                        <x-tabla.cell>{{ $producto->codigo ?? 'N/A' }}</x-tabla.cell>
                        <x-tabla.cell>{{ $producto->obra->obra ?? '—' }}</x-tabla.cell>
                        <x-tabla.cell>{{ $producto->fabricante->nombre ?? '—' }}</x-tabla.cell>
                        <x-tabla.cell>{{ ucfirst($producto->productoBase->tipo ?? '—') }}</x-tabla.cell>
                        <x-tabla.cell>{{ $producto->productoBase->diametro ?? '—' }}</x-tabla.cell>
                        <x-tabla.cell>{{ $producto->productoBase->longitud ?? '—' }}</x-tabla.cell>
                        <x-tabla.cell>{{ $producto->n_colada }}</x-tabla.cell>
                        <x-tabla.cell>{{ $producto->n_paquete }}</x-tabla.cell>
                        <x-tabla.cell>{{ $producto->peso_inicial }} kg</x-tabla.cell>
                        <x-tabla.cell>{{ $producto->peso_stock }} kg</x-tabla.cell>

                        <x-tabla.cell>
                            @if ($producto->estado === 'consumido')
                                <div class="relative group inline-block">
                                    <span class="cursor-help">{{ $producto->estado }}</span>
                                    <div
                                        class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                                        <div class="font-semibold mb-1">Información de consumo</div>
                                        @if ($producto->fecha_consumido)
                                            <div>📅
                                                {{ \Carbon\Carbon::parse($producto->fecha_consumido)->format('d/m/Y H:i') }}
                                            </div>
                                        @endif
                                        @if ($producto->consumidoPor)
                                            <div>👤 {{ $producto->consumidoPor->nombre_completo }}</div>
                                        @endif
                                        <div
                                            class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900">
                                        </div>
                                    </div>
                                </div>
                            @else
                                {{ $producto->estado }}
                            @endif
                        </x-tabla.cell>

                        <x-tabla.cell>
                            @if ($producto->ubicacion)
                                {{ $producto->ubicacion->nombre }}
                            @elseif($producto->maquina)
                                {{ $producto->maquina->nombre }}
                            @else
                                No está ubicada
                            @endif
                        </x-tabla.cell>

                        <x-tabla.cell>{{ $producto->created_at->format('d/m/Y') }}</x-tabla.cell>

                        <x-tabla.cell>
                            <div class="flex items-center space-x-2 justify-center">
                                <button type="button" data-producto-id="{{ $producto->id }}"
                                    class="btn-editar-producto w-6 h-6 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 flex items-center justify-center"
                                    title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                    </svg>
                                </button>
                                <a href="{{ route('productos.show', $producto->id) }}"
                                    class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center"
                                    title="Ver">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <button type="button" onclick="abrirModalMovimientoLibre('{{ $producto->codigo }}')"
                                    class="w-6 h-6 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center"
                                    title="Mover producto">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <path
                                            d="M8.5 2A1.5 1.5 0 0 1 10 3.5V10h.5V4A1.5 1.5 0 0 1 13 4v6h.5V5.5a1.5 1.5 0 0 1 3 0V10h.5V7a1.5 1.5 0 0 1 3 0v9.5a3.5 3.5 0 0 1-7 0V18h-2a3 3 0 0 1-3-3v-4H8V3.5A1.5 1.5 0 0 1 8.5 2z" />
                                    </svg>
                                </button>
                                <a href="{{ route('productos.editarConsumir', $producto->id) }}"
                                    data-consumir="{{ route('productos.editarConsumir', $producto->id) }}"
                                    class="btn-consumir w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center"
                                    title="Consumir">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path
                                            d="M13.5 3.5c-2 2-1.5 4-3 5.5s-4 1-4 5a6 6 0 0012 0c0-2-1-3.5-2-4.5s-1-3-3-6z" />
                                    </svg>
                                </a>
                                <x-tabla.boton-eliminar :action="route('productos.destroy', $producto->id)" />
                            </div>
                        </x-tabla.cell>
                    </x-tabla.row>
                @empty
                    <x-tabla.empty-state colspan="16" mensaje="No hay productos con esa descripción." />
                @endforelse
            </x-tabla.body>

            </x-tabla.wrapper>

            {{-- Total peso filtrado visible siempre --}}
            <div class="mt-4 bg-gradient-to-r from-blue-50 to-blue-100 border-l-4 border-blue-500 rounded-r-lg p-3 shadow">
                <div class="flex justify-end items-center gap-4 text-sm text-gray-700">
                    <span class="font-semibold">Total peso filtrado:</span>
                    <span class="text-base font-bold text-blue-800">
                        {{ number_format($totalPesoInicial, 2, ',', '.') }} kg
                    </span>
                </div>
            </div>
        </div>

        <x-tabla.paginacion-livewire :paginador="$productos" />

    {{-- Modal Editar Producto --}}
    <div id="modal-editar-producto"
        class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Editar Material</h3>
                <button type="button" id="btn-cerrar-modal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="form-editar-producto" class="p-6 space-y-4">
                <input type="hidden" id="edit-producto-id">

                <!-- Código (readonly) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código</label>
                    <input type="text" id="edit-codigo" readonly
                        class="w-full px-3 py-2 border rounded-lg bg-gray-100 text-gray-700">
                </div>

                <!-- Fabricante -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fabricante</label>
                    <select id="edit-fabricante_id" required
                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccione un fabricante</option>
                    </select>
                </div>

                <!-- Producto Base -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Producto Base</label>
                    <select id="edit-producto_base_id" required
                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccione un producto base</option>
                    </select>
                </div>

                <!-- Colada -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nº Colada</label>
                    <input type="text" id="edit-n_colada" required
                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Paquete -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nº Paquete</label>
                    <input type="text" id="edit-n_paquete" required
                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Peso Inicial -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Peso Inicial (kg)</label>
                    <input type="number" step="0.01" min="0" id="edit-peso_inicial" required
                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Ubicación -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ubicación ID</label>
                    <input type="number" id="edit-ubicacion_id"
                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Estado -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select id="edit-estado"
                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">— Ninguno —</option>
                        <option value="almacenado">Almacenado</option>
                        <option value="fabricando">Fabricando</option>
                        <option value="consumido">Consumido</option>
                    </select>
                </div>

                <!-- Otros -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Otros</label>
                    <input type="text" id="edit-otros"
                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Botones -->
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" id="btn-cancelar-edit"
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                        Cancelar
                    </button>
                    <button type="submit" id="btn-guardar-edit"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Script para modal de edición --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modal-editar-producto');
            const form = document.getElementById('form-editar-producto');
            const btnCerrar = document.getElementById('btn-cerrar-modal');
            const btnCancelar = document.getElementById('btn-cancelar-edit');
            const btnGuardar = document.getElementById('btn-guardar-edit');

            // Abrir modal al hacer clic en el botón editar
            document.body.addEventListener('click', async function(e) {
                const btnEditar = e.target.closest('.btn-editar-producto');
                if (!btnEditar) return;

                const productoId = btnEditar.dataset.productoId;
                await cargarProducto(productoId);
            });

            // Cerrar modal
            btnCerrar.addEventListener('click', cerrarModal);
            btnCancelar.addEventListener('click', cerrarModal);
            modal.addEventListener('click', function(e) {
                if (e.target === modal) cerrarModal();
            });

            function cerrarModal() {
                modal.classList.add('hidden');
                form.reset();
            }

            // Actualizar fila de la tabla dinámicamente
            function actualizarFilaTabla(productoId, formData) {
                // Buscar la fila que corresponde a este producto
                const filas = document.querySelectorAll('tbody tr');

                for (const fila of filas) {
                    const celdas = fila.querySelectorAll('td');
                    if (celdas.length === 0) continue; // Saltar empty state

                    const idEnTabla = celdas[0]?.textContent.trim();
                    if (idEnTabla == productoId) {
                        // Actualizar celdas relevantes

                        // Colada (columna 9)
                        if (celdas[8]) celdas[8].textContent = formData.n_colada || '';

                        // Paquete (columna 10)
                        if (celdas[9]) celdas[9].textContent = formData.n_paquete || '';

                        // Peso inicial (columna 11)
                        if (celdas[10]) celdas[10].textContent = formData.peso_inicial + ' kg';

                        // Estado (columna 13) - mantener tooltip si es consumido
                        if (celdas[12]) {
                            const estadoValue = formData.estado || '';
                            if (estadoValue) {
                                celdas[12].textContent = estadoValue.charAt(0).toUpperCase() + estadoValue.slice(1);
                            } else {
                                celdas[12].textContent = '';
                            }
                        }

                        // Fabricante (columna 5) - actualizar con el nombre del select
                        if (celdas[4]) {
                            const selectFabricante = document.getElementById('edit-fabricante_id');
                            const nombreFabricante = selectFabricante.options[selectFabricante.selectedIndex]
                                ?.text || '—';
                            celdas[4].textContent = nombreFabricante;
                        }

                        // Producto Base (columnas 6, 7, 8: tipo, diámetro, longitud)
                        const selectProductoBase = document.getElementById('edit-producto_base_id');
                        const selectedOption = selectProductoBase.options[selectProductoBase.selectedIndex];
                        if (selectedOption && selectedOption.value) {
                            const textoProductoBase = selectedOption.text; // "BARRA | Ø12 | 12 m"
                            const partes = textoProductoBase.split('|').map(p => p.trim());

                            // Tipo (columna 6)
                            if (celdas[5]) celdas[5].textContent = partes[0] || '—';

                            // Diámetro (columna 7)
                            if (celdas[6]) {
                                const diametro = partes[1]?.replace('Ø', '') || '—';
                                celdas[6].textContent = diametro;
                            }

                            // Longitud (columna 8)
                            if (celdas[7]) {
                                const longitud = partes[2]?.replace(' m', '') || '—';
                                celdas[7].textContent = longitud;
                            }
                        }

                        // Efecto visual de actualización
                        fila.classList.add('bg-green-100');
                        setTimeout(() => {
                            fila.classList.remove('bg-green-100');
                        }, 2000);

                        break;
                    }
                }
            }

            // Cargar datos del producto
            async function cargarProducto(id) {
                try {
                    const response = await fetch(`/productos/${id}/edit-data`);
                    const data = await response.json();

                    if (!data.success) {
                        throw new Error(data.message || 'Error al cargar el producto');
                    }

                    // Llenar el formulario
                    document.getElementById('edit-producto-id').value = data.producto.id;
                    document.getElementById('edit-codigo').value = data.producto.codigo;
                    document.getElementById('edit-n_colada').value = data.producto.n_colada || '';
                    document.getElementById('edit-n_paquete').value = data.producto.n_paquete || '';
                    document.getElementById('edit-peso_inicial').value = data.producto.peso_inicial || '';
                    document.getElementById('edit-ubicacion_id').value = data.producto.ubicacion_id || '';
                    document.getElementById('edit-estado').value = data.producto.estado || '';
                    document.getElementById('edit-otros').value = data.producto.otros || '';

                    // Llenar select de fabricantes
                    const selectFabricante = document.getElementById('edit-fabricante_id');
                    selectFabricante.innerHTML = '<option value="">Seleccione un fabricante</option>';
                    data.fabricantes.forEach(fab => {
                        const option = document.createElement('option');
                        option.value = fab.id;
                        option.textContent = fab.nombre;
                        if (fab.id == data.producto.fabricante_id) option.selected = true;
                        selectFabricante.appendChild(option);
                    });

                    // Llenar select de productos base
                    const selectProductoBase = document.getElementById('edit-producto_base_id');
                    selectProductoBase.innerHTML = '<option value="">Seleccione un producto base</option>';
                    data.productosBase.forEach(base => {
                        const option = document.createElement('option');
                        option.value = base.id;
                        option.textContent =
                            `${base.tipo.toUpperCase()} | Ø${base.diametro}${base.longitud ? ' | ' + base.longitud + ' m' : ''}`;
                        if (base.id == data.producto.producto_base_id) option.selected = true;
                        selectProductoBase.appendChild(option);
                    });

                    // Mostrar modal
                    modal.classList.remove('hidden');

                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true
                    });
                }
            }

            // Guardar cambios
            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                const productoId = document.getElementById('edit-producto-id').value;
                const formData = {
                    codigo: document.getElementById('edit-codigo').value,
                    fabricante_id: document.getElementById('edit-fabricante_id').value,
                    producto_base_id: document.getElementById('edit-producto_base_id').value,
                    n_colada: document.getElementById('edit-n_colada').value,
                    n_paquete: document.getElementById('edit-n_paquete').value,
                    peso_inicial: document.getElementById('edit-peso_inicial').value,
                    ubicacion_id: document.getElementById('edit-ubicacion_id').value || null,
                    estado: document.getElementById('edit-estado').value || null,
                    otros: document.getElementById('edit-otros').value || null,
                };

                // Deshabilitar botón mientras se guarda
                btnGuardar.disabled = true;
                btnGuardar.textContent = 'Guardando...';

                try {
                    const response = await fetch(`/productos/${productoId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(formData)
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        // Errores de validación
                        if (result.error && typeof result.error === 'object') {
                            const errores = Object.values(result.error).flat().join('\n');
                            throw new Error(errores);
                        }
                        throw new Error(result.error || 'Error al guardar');
                    }

                    // Éxito - Actualizar la fila en la tabla dinámicamente
                    actualizarFilaTabla(productoId, formData);

                    Swal.fire({
                        icon: 'success',
                        title: '¡Guardado!',
                        text: 'El material se ha actualizado correctamente',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });

                    cerrarModal();

                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'No se pudo guardar el material',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true
                    });

                    btnGuardar.disabled = false;
                    btnGuardar.textContent = 'Guardar Cambios';
                }
            });
        });
    </script>

    {{-- Script para botón consumir con SweetAlert --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Delegación de eventos para botones "Consumir"
            document.body.addEventListener('click', async (e) => {
                const btn = e.target.closest('.btn-consumir');
                if (!btn) return;

                e.preventDefault();

                const url = btn.dataset.consumir || btn.getAttribute('href');

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
        });
    </script>
</div>
