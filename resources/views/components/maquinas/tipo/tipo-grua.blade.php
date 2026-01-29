<div class="w-full sm:col-span-8">

    <div class="mb-4 flex flex-col sm:flex-row justify-center gap-2">
        <button onclick="abrirModalMovimientoLibre()"
            class="bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-base px-4 py-3 rounded-lg">
            🔧 MOVER MP
        </button>
        <button onclick="abrirModalMoverPaquete()"
            class="bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-bold text-base px-4 py-3 rounded-lg">
            📦 MOVER PAQUETE
        </button>
    </div>
    {{-- 🔴 PENDIENTES --}}
    <div class="bg-red-100 dark:bg-red-950 border-2 border-red-400 dark:border-red-800 rounded-lg p-3 mt-4">
        <h3 class="text-lg font-bold text-red-800 dark:text-red-200 mb-3 flex items-center gap-2">
            ⏳ PENDIENTES
            <span class="bg-red-500 text-white text-sm px-2 py-0.5 rounded-full">{{ $movimientosPendientes->count() }}</span>
        </h3>
        @if ($movimientosPendientes->isEmpty())
            <p class="text-gray-600 dark:text-gray-400 text-base py-4 text-center">✓ No hay movimientos pendientes</p>
        @else
            <ul class="space-y-3">
                @foreach ($movimientosPendientes as $mov)
                    @if (strtolower($mov->tipo) === 'entrada' && $mov->pedido)
                        @php
                            $proveedor = $mov->pedido->fabricante?->nombre ?? ($mov->pedido->distribuidor?->nombre ?? 'No especificado');
                            $productoBase = $mov->productoBase;
                            $descripcionProducto = $productoBase
                                ? sprintf('%s Ø%s%s', ucfirst($productoBase->tipo), $productoBase->diametro,
                                    $productoBase->tipo === 'barra' && $productoBase->longitud ? ' x ' . $productoBase->longitud . 'm' : '')
                                : 'Producto no especificado';
                            $cantidadPedido = $mov->pedidoProducto?->cantidad ?? 'N/A';
                            $codigoLinea = $mov->pedidoProducto?->codigo ?? 'N/A';
                            $productoBaseId = $mov->producto_base_id ?? ($mov->productoBase?->id ?? '');
                            $urlRecepcion = "/pedidos/{$mov->pedido->id}/recepcion/{$productoBaseId}?movimiento_id={$mov->id}&maquina_id={$maquina->id}";
                        @endphp

                        <li class="p-3 bg-white dark:bg-gray-800 border-2 border-blue-300 dark:border-blue-700 rounded-lg">
                            <div class="text-base font-bold text-blue-800 dark:text-blue-200 mb-2">{{ $codigoLinea }}</div>
                            <div class="text-sm text-gray-800 dark:text-gray-200 space-y-1 mb-3">
                                <p><strong>{{ $descripcionProducto }}</strong></p>
                                <p>{{ $proveedor }} · {{ $cantidadPedido }} kg</p>
                            </div>
                            @if($mov->pedidoProducto && $mov->pedidoProducto->coladas->isNotEmpty())
                                <div class="flex flex-wrap gap-1 mb-3">
                                    @foreach($mov->pedidoProducto->coladas as $coladaItem)
                                        <span class="bg-blue-600 text-white text-xs px-2 py-1 rounded font-medium">
                                            {{ $coladaItem->colada }}@if($coladaItem->bulto) · {{ (int)$coladaItem->bulto }}p @endif
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                            <a href="{{ $urlRecepcion }}"
                                class="block w-full bg-orange-500 hover:bg-orange-600 active:bg-orange-700 text-white text-lg font-bold py-4 rounded-lg text-center">
                                📥 ENTRADA
                            </a>
                        </li>
                    @else
                        <li class="p-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded-lg">
                            <div class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-1">{{ ucfirst($mov->tipo) }}</div>
                            <div class="text-base text-gray-900 dark:text-gray-100 mb-3">{{ $mov->descripcion }}</div>

                            {{-- BAJADA DE PAQUETE --}}
                            @if (strtolower($mov->tipo) === 'bajada de paquete')
                                @php $datosMovimiento = ['id' => $mov->id, 'paquete_id' => $mov->paquete_id, 'ubicacion_origen' => $mov->ubicacion_origen, 'descripcion' => $mov->descripcion]; @endphp
                                <button onclick='abrirModalBajadaPaquete(@json($datosMovimiento))'
                                    class="block w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-lg font-bold py-4 rounded-lg">
                                    📦 EJECUTAR BAJADA
                                </button>
                            @endif
                            {{-- RECARGA MATERIA PRIMA --}}
                            @if (strtolower($mov->tipo) === 'recarga materia prima')
                                <button onclick='abrirModalRecargaMateriaPrima(@json($mov->id), @json($mov->tipo), @json(optional($mov->producto)->codigo), @json($mov->maquina_destino), @json($mov->producto_base_id), @json($ubicacionesDisponiblesPorProductoBase[$mov->producto_base_id] ?? []), @json(optional($mov->maquinaDestino)->nombre ?? 'Máquina desconocida'), @json(optional($mov->productoBase)->tipo ?? ''), @json(optional($mov->productoBase)->diametro ?? ''), @json(optional($mov->productoBase)->longitud ?? ''))'
                                    class="block w-full bg-green-600 hover:bg-green-700 active:bg-green-800 text-white text-lg font-bold py-4 rounded-lg">
                                    ✅ EJECUTAR RECARGA
                                </button>
                            @endif
                            {{-- SALIDA --}}
                            @if (strtolower($mov->tipo) === 'salida')
                                <button onclick='ejecutarSalida(@json($mov->id), @json($mov->salida_id))'
                                    class="block w-full bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white text-lg font-bold py-4 rounded-lg">
                                    🚛 EJECUTAR SALIDA
                                </button>
                            @endif
                            {{-- SALIDA ALMACEN --}}
                            @if (strtolower($mov->tipo) === 'salida almacén')
                                <button onclick='ejecutarSalidaAlmacen(@json($mov->id))'
                                    class="block w-full bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white text-lg font-bold py-4 rounded-lg">
                                    🚛 EJECUTAR SALIDA
                                </button>
                            @endif
                            {{-- PREPARACIÓN PAQUETE --}}
                            @if (strtolower($mov->tipo) === 'preparación paquete')
                                <button onclick='abrirModalPreparacionPaquete(@json($mov->id))'
                                    class="block w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-lg font-bold py-4 rounded-lg">
                                    📦 PREPARAR
                                </button>
                            @endif
                            {{-- PREPARACIÓN ELEMENTOS --}}
                            @if (strtolower($mov->tipo) === 'preparación elementos')
                                @php
                                    preg_match('/\[planilla_id:(\d+)\]/', $mov->descripcion ?? '', $planillaIdMatch);
                                    $planillaIdFabricar = $planillaIdMatch[1] ?? null;
                                @endphp
                                @if($planillaIdFabricar)
                                    <a href="{{ route('maquinas.show', ['maquina' => $maquina->id, 'fabricar_planilla' => $planillaIdFabricar]) }}"
                                        class="block w-full bg-orange-600 hover:bg-orange-700 active:bg-orange-800 text-white text-lg font-bold py-4 rounded-lg text-center">
                                        🔧 FABRICAR
                                    </a>
                                @endif
                            @endif
                        </li>
                    @endif
                @endforeach
            </ul>
        @endif
    </div>

    {{-- 🟢 COMPLETADOS --}}
    <div class="bg-green-100 dark:bg-green-950 border-2 border-green-400 dark:border-green-800 rounded-lg p-3 mt-4" id="contenedor-movimientos-completados">
        <h3 class="text-lg font-bold text-green-800 dark:text-green-200 mb-3 flex items-center gap-2">
            ✓ COMPLETADOS
            <span class="bg-green-500 text-white text-sm px-2 py-0.5 rounded-full">{{ $movimientosCompletados->count() }}</span>
        </h3>

        @if ($movimientosCompletados->isEmpty())
            <p class="text-gray-600 dark:text-gray-400 text-base py-4 text-center">Sin movimientos completados</p>
        @else
            <ul class="space-y-2">
                @foreach ($movimientosCompletados as $mov)
                    <li class="p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg movimiento-completado" data-movimiento-id="{{ $mov->id }}">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div class="flex-1">
                                <span class="text-xs font-semibold text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/50 px-2 py-0.5 rounded">
                                    {{ ucfirst($mov->tipo) }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $mov->updated_at->format('d/m H:i') }}</span>
                            </div>
                            <button type="button"
                                onclick="eliminarMovimientoGrua({{ $mov->id }}, '{{ $mov->producto_consumido_id ? (optional($mov->productoConsumido)->codigo ?? '') : '' }}')"
                                class="bg-red-500 hover:bg-red-600 active:bg-red-700 text-white text-sm font-bold px-3 py-2 rounded">
                                🗑️
                            </button>
                        </div>
                        <p class="text-sm text-gray-800 dark:text-gray-200">{!! $mov->descripcion_html !!}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ optional($mov->ejecutadoPor)->nombre_completo ?? 'N/A' }}
                        </p>
                        @if($mov->producto_consumido_id)
                            <p class="text-xs text-orange-600 dark:text-orange-400 mt-1 font-medium">
                                ⚠️ Consumió: {{ optional($mov->productoConsumido)->codigo ?? 'N/A' }}
                            </p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="mt-3 flex justify-center gap-1" id="paginador-movimientos-completados"></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const itemsPorPagina = 5;
        const items = Array.from(document.querySelectorAll('.movimiento-completado'));
        const paginador = document.getElementById('paginador-movimientos-completados');
        const totalPaginas = Math.ceil(items.length / itemsPorPagina);

        function mostrarPagina(pagina) {
            const inicio = (pagina - 1) * itemsPorPagina;
            const fin = inicio + itemsPorPagina;

            items.forEach((item, index) => {
                item.style.display = (index >= inicio && index < fin) ? 'block' : 'none';
            });

            actualizarPaginador(pagina);
        }

        function actualizarPaginador(paginaActual) {
            paginador.innerHTML = '';

            for (let i = 1; i <= totalPaginas; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.className = `min-w-[44px] h-[44px] rounded-lg text-base font-bold ${
                    i === paginaActual
                        ? 'bg-green-600 text-white'
                        : 'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 border-2 border-gray-300 dark:border-gray-600'
                }`;
                btn.onclick = () => mostrarPagina(i);
                paginador.appendChild(btn);
            }
        }

        if (items.length > 0) {
            mostrarPagina(1);
        }
    });

    // Escuchar evento de movimiento de paquete para actualizar la lista
    window.addEventListener('movimiento:paquete-creado', async function() {
        // Cerrar modal si está abierto
        const modal = document.getElementById('modal-mover-paquete');
        if (modal && !modal.classList.contains('hidden')) {
            modal.classList.add('hidden');
        }

        // Actualizar lista de movimientos completados via AJAX
        try {
            const naveId = {{ $maquina->obra_id ?? 1 }};
            const response = await fetch(`/maquinas/movimientos-completados/${naveId}`);
            const data = await response.json();

            if (data.success && data.movimientos) {
                actualizarListaMovimientosCompletados(data.movimientos);
            }
        } catch (error) {
            console.error('Error al actualizar movimientos:', error);
        }
    });

    // Función para actualizar la lista de movimientos completados
    function actualizarListaMovimientosCompletados(movimientos) {
        const contenedor = document.getElementById('contenedor-movimientos-completados');
        if (!contenedor) return;

        const lista = contenedor.querySelector('ul');
        const mensajeVacio = contenedor.querySelector('p.text-gray-600');

        // Si hay movimientos, actualizar la lista
        if (movimientos.length > 0) {
            if (mensajeVacio) mensajeVacio.remove();

            // Crear o actualizar lista
            let ul = lista;
            if (!ul) {
                ul = document.createElement('ul');
                ul.className = 'space-y-3';
                contenedor.appendChild(ul);
            }

            // Limpiar lista existente
            ul.innerHTML = '';

            // Agregar nuevos movimientos
            movimientos.forEach(mov => {
                const li = document.createElement('li');
                li.className = 'p-3 border border-green-200 rounded shadow-sm bg-white text-sm movimiento-completado';
                li.innerHTML = `
                    <div class="flex flex-col gap-2">
                        <p><strong>Tipo:</strong> ${mov.tipo}</p>
                        <p>${mov.descripcion_html}</p>
                        <p><strong>Solicitado por:</strong> ${mov.solicitado_por}</p>
                        <p><strong>Ejecutado por:</strong> ${mov.ejecutado_por}</p>
                        <p><strong>Fecha completado:</strong> ${mov.fecha_completado}</p>
                    </div>
                `;
                ul.appendChild(li);
            });

            // Re-inicializar paginación
            reiniciarPaginacionCompletados();
        }
    }

    // Función para reiniciar la paginación después de actualizar
    function reiniciarPaginacionCompletados() {
        const itemsPorPagina = 5;
        const items = Array.from(document.querySelectorAll('.movimiento-completado'));
        const paginador = document.getElementById('paginador-movimientos-completados');
        const totalPaginas = Math.ceil(items.length / itemsPorPagina);

        function mostrarPagina(pagina) {
            const inicio = (pagina - 1) * itemsPorPagina;
            const fin = inicio + itemsPorPagina;
            items.forEach((item, index) => {
                item.style.display = (index >= inicio && index < fin) ? 'block' : 'none';
            });
            actualizarPaginador(pagina);
        }

        function actualizarPaginador(paginaActual) {
            paginador.innerHTML = '';
            for (let i = 1; i <= totalPaginas; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.className = `min-w-[44px] h-[44px] rounded-lg text-base font-bold ${
                    i === paginaActual
                        ? 'bg-green-600 text-white'
                        : 'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 border-2 border-gray-300 dark:border-gray-600'
                }`;
                btn.onclick = () => mostrarPagina(i);
                paginador.appendChild(btn);
            }
        }

        if (items.length > 0) {
            mostrarPagina(1);
        }
    }
</script>
<script>
    function ejecutarSalida(movimientoId, salidaId) {
        // Abrir modal de ejecutar salida con sistema de escaneo
        abrirModalEjecutarSalida(movimientoId, salidaId);
    }
</script>
<script>
    /* =========================================================
 Salidas de almacén – UI de ejecución (rutas WEB)
 Ahora trabajamos con líneas de albarán (albaranes_venta_lineas)
========================================================= */

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const codigosEscaneados = new Set();
    const productosEscaneadosPorLinea = {}; // { [linea_id]: Array<{codigo, peso_kg, cantidad}> }
    const
        metaLineas = {}; // { [linea_id]: { objetivoKg, objetivoUd, asignadoKg, asignadoUd, label, diametro, longitud, cliente, pedido } }
    window._salidaActualId = null;

    // ====== Utilidad fetch ======
    async function fetchJSON(url, options = {}) {
        const res = await fetch(url, options);
        let data = null;
        try {
            data = await res.json();
        } catch {}
        return {
            ok: res.ok,
            status: res.status,
            data
        };
    }

    // ====== Helpers UI ======
    function mostrarErrorInline(lineaId, mensaje) {
        const box = document.getElementById(`error-ln-${lineaId}`);
        if (!box) return;
        box.textContent = mensaje || 'Ha ocurrido un error.';
        box.classList.remove('hidden');
        const input = document.querySelector(`input[data-ln="${lineaId}"]`);
        if (input) {
            const clear = () => {
                box.classList.add('hidden');
                box.textContent = '';
                input.removeEventListener('input', clear);
            };
            input.addEventListener('input', clear);
        }
    }

    function limpiarErrorInline(lineaId) {
        const box = document.getElementById(`error-ln-${lineaId}`);
        if (!box) return;
        box.classList.add('hidden');
        box.textContent = '';
    }

    function mostrarOkPequenyo(lineaId) {
        const input = document.querySelector(`input[data-ln="${lineaId}"]`);
        if (!input) return;
        let badge = document.getElementById(`ok-ln-${lineaId}`);
        if (!badge) {
            badge = document.createElement('div');
            badge.id = `ok-ln-${lineaId}`;
            badge.className = 'text-xs text-green-600 mt-1';
            input.insertAdjacentElement('afterend', badge);
        }
        badge.textContent = '✓ añadido';
        badge.style.opacity = 1;
        setTimeout(() => {
            badge.style.opacity = 0;
        }, 900);
    }

    function etiquetaPB(diam, long) {
        const d = (diam ?? '').toString().trim();
        const l = (long ?? '').toString().trim();
        return `Ø${d || '—'}${l ? ` · ${l}m` : ''}`;
    }

    // ====== Progreso por línea ======
    function actualizarProgresoLinea(lineaId) {
        const meta = metaLineas[lineaId] || {};
        const asign = meta.objetivoKg != null ? (meta.asignadoKg || 0) : (meta.asignadoUd || 0);
        const obj = meta.objetivoKg != null ? meta.objetivoKg : meta.objetivoUd;
        const pct = obj && obj > 0 ? Math.min(100, Math.round((asign / obj) * 100)) : 0;

        const bar = document.getElementById(`bar-${lineaId}`);
        const chip = document.getElementById(`estado-chip-${lineaId}`);
        const head = document.getElementById(`asignado-head-${lineaId}`);

        if (bar) bar.style.width = pct + '%', bar.style.backgroundColor = pct >= 100 ? '#10B981' : (pct > 0 ?
            '#FBBF24' : '#E5E7EB');
        if (chip) {
            let texto = 'pendiente',
                cls = 'bg-gray-100 text-gray-700';
            if (pct >= 100) {
                texto = 'completo';
                cls = 'bg-green-100 text-green-700';
            } else if (pct > 0) {
                texto = 'parcial';
                cls = 'bg-amber-100 text-amber-700';
            }
            chip.className = `text-xs px-2 py-0.5 rounded ${cls}`;
            chip.textContent = texto;
        }
        if (head) head.textContent = meta.objetivoKg != null ? `${asign} kg` : `${asign} ud`;
    }

    function recomputarTotalesLinea(lineaId) {
        const meta = metaLineas[lineaId] || {};
        const lista = productosEscaneadosPorLinea[lineaId] || [];
        let sumKg = 0,
            sumUd = 0;
        for (const it of lista) {
            if (typeof it.peso_kg === 'number' && it.peso_kg > 0) sumKg += it.peso_kg;
            else if (typeof it.cantidad === 'number' && it.cantidad > 0) sumUd += it.cantidad;
        }
        if (meta.objetivoKg != null) metaLineas[lineaId].asignadoKg = sumKg;
        if (meta.objetivoUd != null) metaLineas[lineaId].asignadoUd = sumUd;
    }

    // ====== Lista escaneados ======
    function renderizarListaPorLinea(lineaId) {
        const cont = document.getElementById(`productos-escaneados-${lineaId}`);
        const lista = productosEscaneadosPorLinea[lineaId] || [];
        if (!cont) return;
        if (!lista.length) {
            cont.innerHTML = `<span class="text-gray-500">Sin productos escaneados.</span>`;
            recomputarTotalesLinea(lineaId);
            actualizarProgresoLinea(lineaId);
            return;
        }
        lista.sort((a, b) => String(a.codigo).localeCompare(String(b.codigo)));
        const totalKg = lista.reduce((acc, p) => acc + (p.peso_kg ? Number(p.peso_kg) : 0), 0);
        const totalUd = lista.reduce((acc, p) => acc + (p.cantidad ? Number(p.cantidad) : 0), 0);

        cont.innerHTML = `
      <div class="mt-2 border rounded overflow-auto max-h-56">
        <table class="w-full text-xs">
          <thead class="bg-gray-50 sticky top-0 z-10">
            <tr>
              <th class="text-left px-2 py-1 border-b">Código</th>
              <th class="text-right px-2 py-1 border-b">Stock</th>
              <th class="text-right px-2 py-1 border-b">${totalKg > 0 ? 'Asignado (kg)' : 'Asignado (ud)'}</th>
              <th class="text-left px-2 py-1 border-b">Acciones</th>
            </tr>
          </thead>
          <tbody>
            ${lista.map((p, i) => `
              <tr class="${i % 2 ? 'bg-gray-50/30' : ''}">
                <td class="px-2 py-1 border-b">${p.codigo}</td>
                <td class="px-2 py-1 border-b text-right">${(p.peso_stock ?? 0).toLocaleString()} kg</td>
                <td class="px-2 py-1 border-b text-right">${p.peso_kg ?? p.cantidad}</td>
                <td class="px-2 py-1 border-b">
                  <button class="text-red-600 hover:underline" onclick="eliminarEscaneado('${p.codigo}', ${lineaId})">Quitar</button>
                </td>
              </tr>`).join('')}
          </tbody>
          <tfoot class="bg-gray-50">
            <tr>
              <td class="px-2 py-1 border-t font-semibold">Total</td>
              <td></td>
              <td class="px-2 py-1 border-t text-right font-semibold">${totalKg > 0 ? totalKg + ' kg' : totalUd + ' ud'}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>`;
        recomputarTotalesLinea(lineaId);
        actualizarProgresoLinea(lineaId);
    }

    // ====== Refrescar desde backend ======
    async function refrescarAsignadosDesdeBackend(salidaId, lineaId = null) {
        const {
            ok,
            data
        } = await fetchJSON(`/salidas-almacen/${salidaId}/asignados`);
        if (!ok || !data?.success) return;
        const mapa = data.asignados || {};
        codigosEscaneados.clear();
        Object.keys(productosEscaneadosPorLinea).forEach(k => delete productosEscaneadosPorLinea[k]);
        Object.entries(mapa).forEach(([ln, arr]) => {
            const id = Number(ln);
            productosEscaneadosPorLinea[id] = Array.isArray(arr) ? arr : [];
            (productosEscaneadosPorLinea[id]).forEach(it => codigosEscaneados.add(it.codigo));
        });
        if (lineaId != null) {
            renderizarListaPorLinea(lineaId);
        } else {
            Object.keys(productosEscaneadosPorLinea).forEach(id => renderizarListaPorLinea(Number(id)));
        }
    }

    // ====== Eliminar escaneado ======
    async function eliminarEscaneado(codigo, lineaId) {
        const salidaId = window._salidaActualId;
        if (!salidaId) {
            mostrarErrorInline(lineaId, 'No se reconoce la salida actual.');
            return;
        }
        const res = await fetch(`/salidas-almacen/${salidaId}/detalle/${encodeURIComponent(codigo)}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });
        const data = await res.json().catch(() => null);
        if (!res.ok || !data?.success) {
            mostrarErrorInline(lineaId, data?.message || 'No se pudo eliminar.');
            return;
        }
        await refrescarAsignadosDesdeBackend(salidaId, lineaId);
    }

    // ====== Abrir modal ======
    async function ejecutarSalidaAlmacen(movimientoId) {
        const {
            ok,
            data
        } = await fetchJSON(`/salidas-almacen/${movimientoId}/productos`);
        if (!ok || !data?.success) {
            Swal.fire('⚠️ Error', data?.message || 'No se pudo obtener info', 'warning');
            return;
        }
        const lineas = data.lineas || [];
        const salidaId = data.salida_id;
        window._salidaActualId = salidaId;

        let html =
            `<p class="mb-3 text-sm text-red-600">${data.observaciones || 'Escanea productos para completar la salida:'}</p>`;
        lineas.forEach(ln => {
            const key = ln.id;
            html += `
          <div class="p-2 border rounded bg-white mb-2">
            <div class="flex items-center justify-between">
              <div>
                <strong>Ø${ln.diametro ?? '—'} ${ln.longitud ? '· ' + ln.longitud + 'm' : ''}</strong>
                <div class="text-xs text-gray-600">Pedido: ${ln.pedido_codigo || '—'} · Cliente: ${ln.cliente || '—'}</div>
                <div class="text-xs text-gray-600">
                  Objetivo: ${ln.peso_objetivo_kg ? ln.peso_objetivo_kg+' kg' : ln.unidades_objetivo+' ud'}
                  · Asignado: <span id="asignado-head-${key}">${ln.asignado_kg || ln.asignado_ud || 0}</span>
                </div>
              </div>
              <span id="estado-chip-${key}" class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700">pendiente</span>
            </div>
            <div class="w-full h-1.5 bg-gray-200 rounded mt-2"><div id="bar-${key}" class="h-1.5 rounded" style="width:0%"></div></div>
            <input type="text" class="w-full mt-2 border px-2 py-1 rounded text-sm" placeholder="Escanea producto..." data-ln="${key}" onkeydown="if(event.key==='Enter'){event.preventDefault(); agregarProductoEscaneado(this, ${key}, ${salidaId});}">
            <div id="error-ln-${key}" class="text-xs text-red-600 mt-1 hidden"></div>
            <div id="productos-escaneados-${key}" class="text-xs text-gray-700 mt-1"></div>
          </div>`;
        });

        await Swal.fire({
            title: 'Salida Almacén',
            html,
            showCancelButton: true,
            confirmButtonText: 'Finalizar salida',
            confirmButtonColor: '#38a169',
            cancelButtonText: 'Cancelar',
            width: '50rem',
            didOpen: () => {
                lineas.forEach(ln => {
                    const key = ln.id;
                    metaLineas[key] = {
                        objetivoKg: ln.peso_objetivo_kg ?? null,
                        objetivoUd: ln.unidades_objetivo ?? null,
                        asignadoKg: ln.asignado_kg ?? 0,
                        asignadoUd: ln.asignado_ud ?? 0,
                        label: etiquetaPB(ln.diametro, ln.longitud),
                        diametro: ln.diametro ?? null,
                        longitud: ln.longitud ?? null
                    };
                    productosEscaneadosPorLinea[key] = ln.asignados || [];
                    (ln.asignados || []).forEach(it => codigosEscaneados.add(it.codigo));
                    actualizarProgresoLinea(key);
                    renderizarListaPorLinea(key);
                });
                refrescarAsignadosDesdeBackend(salidaId);
            },
            preConfirm: async () => {
                const resp = await fetchJSON(
                    `/salidas-almacen/completar-desde-movimiento/${movimientoId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({})
                    });
                if (!resp.ok || !resp.data?.success) {
                    Swal.showValidationMessage(resp.data?.message || 'No se pudo completar.');
                    return false;
                }
                return resp.data;
            }
        }).then(r => {
            if (r.isConfirmed) {
                Swal.fire('', r.value.message || 'Salida completada.', 'success');
                setTimeout(() => location.reload(), 1000);
            }
        });
    }

    // ====== Escaneo / Validación ======
    async function agregarProductoEscaneado(input, lineaId, salidaId) {
        const codigoQR = input.value.trim();
        if (!codigoQR) return;
        limpiarErrorInline(lineaId);
        if (codigosEscaneados.has(codigoQR)) {
            mostrarErrorInline(lineaId, 'Este producto ya ha sido escaneado.');
            input.select();
            return;
        }
        const {
            ok,
            data,
            status
        } = await fetchJSON('/productos/validar-para-salida', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                codigo: codigoQR,
                salida_almacen_id: salidaId,
                albaran_linea_id: lineaId
            })
        });
        if (!ok || !data?.success || !data.producto) {
            mostrarErrorInline(lineaId, data?.message || 'Error validando.');
            input.select();
            return;
        }
        codigosEscaneados.add(codigoQR);
        await refrescarAsignadosDesdeBackend(salidaId, lineaId);
        input.value = '';
        input.focus();
        mostrarOkPequenyo(lineaId);
    }

    // ====== Exponer ======
    window.ejecutarSalidaAlmacen = ejecutarSalidaAlmacen;
    window.agregarProductoEscaneado = agregarProductoEscaneado;
    window.eliminarEscaneado = eliminarEscaneado;
    window.mostrarErrorInline = mostrarErrorInline;
    window.limpiarErrorInline = limpiarErrorInline;
</script>
{{-- Modal de Preparación de Paquete --}}
<div id="modalPreparacionPaquete" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden m-4">
        <div class="flex justify-between items-center p-4 border-b bg-blue-600 text-white">
            <h2 class="text-xl font-bold">
                📦 Preparar Paquete <span id="modalPaqueteCodigo"></span>
            </h2>
            <button onclick="cerrarModalPreparacionPaquete()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
        </div>
        <div id="modalPreparacionContenido" class="p-4 overflow-y-auto" style="max-height: calc(90vh - 140px);">
            <div class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <span class="ml-3 text-gray-600">Cargando etiquetas...</span>
            </div>
        </div>
        <div class="flex justify-end gap-3 p-4 border-t bg-gray-50">
            <button onclick="cerrarModalPreparacionPaquete()"
                class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded font-semibold">
                Cancelar
            </button>
            <button id="btnCompletarPreparacion" onclick="completarPreparacionDesdeModal()"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded font-semibold">
                ✅ Marcar como preparado
            </button>
        </div>
    </div>
</div>

<script>
    // ====== PREPARACIÓN PAQUETE (elementos sin elaborar) ======
    let movimientoActualId = null;

    async function abrirModalPreparacionPaquete(movimientoId) {
        movimientoActualId = movimientoId;
        const modal = document.getElementById('modalPreparacionPaquete');
        const contenido = document.getElementById('modalPreparacionContenido');
        const codigoSpan = document.getElementById('modalPaqueteCodigo');

        // Mostrar modal con loading
        modal.classList.remove('hidden');
        contenido.innerHTML = `
            <div class="flex items-center justify-center py-8">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <span class="ml-3 text-gray-600">Cargando etiquetas...</span>
            </div>
        `;

        try {
            const response = await fetch(`/movimientos/${movimientoId}/etiquetas-paquete`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                codigoSpan.textContent = `#${data.paquete.codigo}`;

                if (data.total_etiquetas === 0) {
                    contenido.innerHTML = `
                        <div class="text-center py-8 text-gray-500">
                            <p class="text-lg">No hay etiquetas con elementos sin elaborar en este paquete.</p>
                        </div>
                    `;
                } else {
                    contenido.innerHTML = `
                        <p class="text-sm text-gray-600 mb-4">
                            ${data.total_etiquetas} etiqueta(s) con elementos sin elaborar:
                        </p>
                        <div class="flex flex-wrap gap-4 justify-center">
                            ${data.html}
                        </div>
                    `;
                }
            } else {
                contenido.innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <p class="text-lg">${data.message || 'Error al cargar las etiquetas.'}</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error:', error);
            contenido.innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <p class="text-lg">Error de conexión al cargar las etiquetas.</p>
                </div>
            `;
        }
    }

    function cerrarModalPreparacionPaquete() {
        document.getElementById('modalPreparacionPaquete').classList.add('hidden');
        movimientoActualId = null;
    }

    async function completarPreparacionDesdeModal() {
        if (!movimientoActualId) return;

        const result = await Swal.fire({
            title: '¿Marcar como preparado?',
            text: 'El paquete quedará listo para su entrega.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, marcar como preparado',
            confirmButtonColor: '#10B981',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        try {
            const response = await fetch(`/movimientos/${movimientoActualId}/completar-preparacion`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                cerrarModalPreparacionPaquete();
                await Swal.fire({
                    title: '¡Preparado!',
                    text: data.message || 'El paquete ha sido marcado como preparado.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
                location.reload();
            } else {
                Swal.fire('Error', data.message || 'No se pudo completar la preparación.', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', 'Ha ocurrido un error de conexión.', 'error');
        }
    }

    // Cerrar modal con Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') cerrarModalPreparacionPaquete();
    });

    // Cerrar modal al hacer clic fuera
    document.getElementById('modalPreparacionPaquete')?.addEventListener('click', (e) => {
        if (e.target.id === 'modalPreparacionPaquete') cerrarModalPreparacionPaquete();
    });

    window.abrirModalPreparacionPaquete = abrirModalPreparacionPaquete;
    window.cerrarModalPreparacionPaquete = cerrarModalPreparacionPaquete;
    window.completarPreparacionDesdeModal = completarPreparacionDesdeModal;
</script>

<script>
    // ====== ELIMINAR MOVIMIENTO COMPLETADO CON RECUPERACIÓN DE PRODUCTO ======
    async function eliminarMovimientoGrua(movimientoId, codigoProductoConsumido) {
        // Preparar mensaje de confirmación según si hay producto consumido o no
        let mensajeHtml = '<p class="text-left">¿Estás seguro de eliminar este movimiento?</p>';
        let tituloAlerta = 'Eliminar Movimiento';
        const tieneProductoConsumido = codigoProductoConsumido && codigoProductoConsumido !== '';

        if (tieneProductoConsumido) {
            mensajeHtml = `
                <div class="text-left">
                    <p class="mb-3">¿Estás seguro de eliminar este movimiento?</p>
                    <div class="bg-orange-100 border-l-4 border-orange-500 p-3 rounded">
                        <p class="text-orange-700 font-semibold">⚠️ Atención:</p>
                        <p class="text-orange-700 text-sm mt-1">
                            Se recuperará el producto <strong>${codigoProductoConsumido}</strong> que fue consumido
                            automáticamente cuando se realizó este movimiento.
                        </p>
                        <p class="text-orange-700 text-sm mt-1">
                            El producto volverá al estado <strong>"fabricando"</strong> en la máquina correspondiente.
                        </p>
                    </div>
                </div>
            `;
            tituloAlerta = '⚠️ Eliminar Movimiento';
        }

        const result = await Swal.fire({
            title: tituloAlerta,
            html: mensajeHtml,
            icon: tieneProductoConsumido ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            confirmButtonColor: '#DC2626',
            cancelButtonText: 'Cancelar',
            cancelButtonColor: '#6B7280',
            reverseButtons: true
        });

        if (!result.isConfirmed) return;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const response = await fetch(`/movimientos/${movimientoId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Construir mensaje de éxito
                let mensajeExito = data.message || 'Movimiento eliminado correctamente.';

                if (data.producto_consumido_recuperado && data.producto_recuperado) {
                    const codigoProducto = data.producto_recuperado.codigo || 'desconocido';
                    mensajeExito += `<br><br><span class="text-green-700">✅ Producto <strong>${codigoProducto}</strong> recuperado exitosamente.</span>`;
                }

                await Swal.fire({
                    title: '¡Eliminado!',
                    html: mensajeExito,
                    icon: 'success',
                    timer: 2500,
                    timerProgressBar: true,
                    showConfirmButton: false
                });

                // Eliminar el elemento de la lista visualmente
                const elemento = document.querySelector(`[data-movimiento-id="${movimientoId}"]`);
                if (elemento) {
                    elemento.style.transition = 'opacity 0.3s, transform 0.3s';
                    elemento.style.opacity = '0';
                    elemento.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        elemento.remove();
                        // Reiniciar paginación
                        reiniciarPaginacionCompletados();

                        // Si no quedan más movimientos, mostrar mensaje vacío
                        const contenedor = document.getElementById('contenedor-movimientos-completados');
                        const items = contenedor?.querySelectorAll('.movimiento-completado');
                        if (items && items.length === 0) {
                            const lista = contenedor.querySelector('ul');
                            if (lista) lista.remove();
                            const pVacio = document.createElement('p');
                            pVacio.className = 'text-gray-600 text-sm';
                            pVacio.textContent = 'No hay movimientos completados.';
                            contenedor.querySelector('h3').insertAdjacentElement('afterend', pVacio);
                        }
                    }, 300);
                } else {
                    // Si no se encuentra el elemento, recargar la página
                    location.reload();
                }
            } else {
                Swal.fire({
                    title: 'Error',
                    text: data.message || 'No se pudo eliminar el movimiento.',
                    icon: 'error'
                });
            }
        } catch (error) {
            console.error('Error al eliminar movimiento:', error);
            Swal.fire({
                title: 'Error',
                text: 'Ha ocurrido un error de conexión.',
                icon: 'error'
            });
        }
    }

    window.eliminarMovimientoGrua = eliminarMovimientoGrua;
</script>
