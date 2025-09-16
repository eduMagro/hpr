<div class="w-full sm:col-span-8">
    {{-- 🟡 PENDIENTES --}}
    <div class="mb-4 flex justify-center">
        <button onclick="abrirModalMovimientoLibre()"
            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow">
            ➕ Crear Movimiento Libre
        </button>
    </div>

    <div class="bg-red-200 border border-red-400 rounded-lg p-4 mt-4">
        <h3 class="text-base sm:text-lg font-bold text-red-800 mb-3">📦 Movimientos Pendientes</h3>
        @if ($movimientosPendientes->isEmpty())
            <p class="text-gray-600 text-sm">No hay movimientos pendientes actualmente.</p>
        @else
            <ul class="space-y-3">
                @foreach ($movimientosPendientes as $mov)
                    <li class="p-3 border border-red-200 rounded shadow-sm bg-white text-sm">
                        <div class="flex flex-col gap-2">
                            <p><strong>Tipo:</strong> {{ ucfirst($mov->tipo) }}</p>
                            <p><strong>Descripción:</strong> {{ $mov->descripcion }}</p>
                            <p><strong>Solicitado por:</strong>
                                {{ optional($mov->solicitadoPor)->nombre_completo ?? 'N/A' }}</p>
                            <p><strong>Fecha:</strong> {{ $mov->created_at->format('d/m/Y H:i') }}</p>

                            @if (strtolower($mov->tipo) === 'bajada de paquete')
                                @php
                                    $datosMovimiento = [
                                        'id' => $mov->id,
                                        'paquete_id' => $mov->paquete_id,
                                        'ubicacion_origen' => $mov->ubicacion_origen,
                                        'descripcion' => $mov->descripcion,
                                    ];
                                @endphp

                                <button type="button" onclick='abrirModalBajadaPaquete(@json($datosMovimiento))'
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded w-full sm:w-auto">
                                    📦 Ejecutar bajada
                                </button>
                            @endif

                            @if (strtolower($mov->tipo) === 'recarga materia prima')
                                <button
                                    onclick='abrirModalRecargaMateriaPrima(
                                        @json($mov->id),
                                        @json($mov->tipo),
                                        @json(optional($mov->producto)->codigo),
                                        @json($mov->maquina_destino),
                                        @json($mov->producto_base_id),
                                        @json($ubicacionesDisponiblesPorProductoBase[$mov->producto_base_id] ?? []),
                                        @json(optional($mov->maquinaDestino)->nombre ?? 'Máquina desconocida'),
                                        @json(optional($mov->productoBase)->tipo ?? ''),
                                        @json(optional($mov->productoBase)->diametro ?? ''),
                                        @json(optional($mov->productoBase)->longitud ?? '')
                                    )'
                                    class="bg-green-600 hover:bg-green-700 text-white text-sm px-3 py-2 rounded mt-2 w-full sm:w-auto">
                                    ✅ Ejecutar recarga
                                </button>
                            @endif

                            @if (strtolower($mov->tipo) === 'entrada' && $mov->pedido)
                                <button onclick='abrirModalPedidoDesdeMovimiento(@json($mov))'
                                    style="background-color: orange; color: white;"
                                    class="text-sm px-3 py-2 rounded mt-2 w-full sm:w-auto border border-black">
                                    🏗️ Ver pedido
                                </button>
                            @endif

                            @if (strtolower($mov->tipo) === 'salida')
                                <button onclick='ejecutarSalida(@json($mov->id))'
                                    class="bg-purple-600 hover:bg-purple-700 text-white text-sm px-3 py-2 rounded mt-2 w-full sm:w-auto">
                                    🚛 Ejecutar salida
                                </button>
                            @endif
                            @if (strtolower($mov->tipo) === 'salida almacén')
                                <button onclick='ejecutarSalidaAlmacen(@json($mov->id))'
                                    class="bg-purple-600 hover:bg-purple-700 text-white text-sm px-3 py-2 rounded mt-2 w-full sm:w-auto">
                                    🚛 Ejecutar salida
                                </button>
                            @endif


                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- 🟢 COMPLETADOS --}}
    <div class="bg-green-200 border border-green-300 rounded-lg p-4 mt-6" id="contenedor-movimientos-completados">
        <h3 class="text-base sm:text-lg font-bold text-green-800 mb-3">Movimientos Completados Recientemente</h3>

        @if ($movimientosCompletados->isEmpty())
            <p class="text-gray-600 text-sm">No hay movimientos completados.</p>
        @else
            <ul class="space-y-3">
                @foreach ($movimientosCompletados as $mov)
                    <li class="p-3 border border-green-200 rounded shadow-sm bg-white text-sm movimiento-completado">
                        <div class="flex flex-col gap-2">
                            <p><strong>Tipo:</strong> {{ ucfirst($mov->tipo) }}</p>
                            <p><strong>Descripción:</strong> {{ $mov->descripcion }}</p>
                            <p><strong>Solicitado por:</strong>
                                {{ optional($mov->solicitadoPor)->nombre_completo ?? 'N/A' }}</p>
                            <p><strong>Ejecutado por:</strong>
                                {{ optional($mov->ejecutadoPor)->nombre_completo ?? 'N/A' }}</p>
                            <p><strong>Fecha completado:</strong> {{ $mov->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="flex justify-end mt-2">
                            <x-tabla.boton-eliminar :action="route('movimientos.destroy', $mov->id)" />
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="mt-4 flex justify-center gap-2" id="paginador-movimientos-completados"></div>
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
                btn.className = `px-3 py-1 rounded border text-sm ${
                    i === paginaActual
                        ? 'bg-green-600 text-white'
                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'
                }`;
                btn.onclick = () => mostrarPagina(i);
                paginador.appendChild(btn);
            }
        }

        if (items.length > 0) {
            mostrarPagina(1);
        }
    });
</script>
<script>
    function ejecutarSalida(movimientoId) {
        Swal.fire({
            title: '¿Ejecutar salida?',
            text: '¿Seguro que quieres marcar esta salida como ejecutada?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, ejecutar'
        }).then((result) => {
            if (result.isConfirmed) {
                // 👉 Llamada AJAX directamente aquí
                fetch(`/salidas/completar-desde-movimiento/${movimientoId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('', data.message, 'success');
                            // 👉 Recargar la página o quitar el elemento de la lista
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            Swal.fire('⚠️', data.message, 'warning');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('', 'Hubo un error al completar la salida.', 'error');
                    });
            }
        });
    }
</script>
<script>
    /* =========================================================
  Salidas de almacén – UI de ejecución (rutas WEB)
  ---------------------------------------------------------
  Rutas esperadas (ajústalas si usas otras):
  - GET  /salidas-almacen/{movimientoId}/productos
      -> { success, salida_id, productos_base:[{
           id (sapb.id),
           producto_base_id,
           peso_objetivo_kg?, unidades_objetivo?,
           diametro?, longitud?,
           asignado_kg?, asignado_ud?,
           asignados?: [{ codigo, peso_kg, cantidad }]
         }] }
  - POST /productos/validar-para-salida
      body: { codigo, salida_almacen_id, producto_base_id_esperado }
      -> { success, message?, producto:{ codigo, producto_base_id, aportado_kg?, aportado_ud? } }
  - GET  /salidas-almacen/{salidaId}/asignados
      -> { success, asignados: { [producto_base_id]: [{ codigo, peso_kg, cantidad }] } }
  - PUT  /salidas-almacen/completar-desde-movimiento/{movimientoId}
      -> { success, message }
  - DELETE /salidas-almacen/{salidaId}/detalle/{codigo}
      -> { success, message }
  ========================================================= */

    // ====== Estado global ======
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const codigosEscaneados = new Set(); // evita dobles lecturas en la sesión del modal
    const productosEscaneadosPorTipo = {}; // { [producto_base_id]: Array<{codigo, peso_kg, cantidad}> }
    const
        metaPB = {}; // { [producto_base_id]: { objetivoKg, objetivoUd, asignadoKg, asignadoUd, label, diametro, longitud } }
    window._salidaActualId = null; // id de la salida actual (para eliminar)

    // ====== Utilidad fetch ======
    async function fetchJSON(url, options = {}) {
        const res = await fetch(url, options);
        let data = null;
        try {
            data = await res.json();
        } catch {
            data = null;
        }
        return {
            ok: res.ok,
            status: res.status,
            data
        };
    }

    // ====== Helpers UI ======
    function mostrarErrorInline(pbId, mensaje) {
        const box = document.getElementById(`error-pb-${pbId}`);
        if (!box) return;
        box.textContent = mensaje || 'Ha ocurrido un error.';
        box.classList.remove('hidden');

        const input = document.querySelector(`input[data-pb="${pbId}"]`);
        if (input) {
            const clear = () => {
                box.classList.add('hidden');
                box.textContent = '';
                input.removeEventListener('input', clear);
            };
            input.addEventListener('input', clear);
        }
    }

    function limpiarErrorInline(pbId) {
        const box = document.getElementById(`error-pb-${pbId}`);
        if (!box) return;
        box.classList.add('hidden');
        box.textContent = '';
    }

    function mostrarOkPequenyo(pbId) {
        const input = document.querySelector(`input[data-pb="${pbId}"]`);
        if (!input) return;
        let badge = document.getElementById(`ok-pb-${pbId}`);
        if (!badge) {
            badge = document.createElement('div');
            badge.id = `ok-pb-${pbId}`;
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

    // ====== Progreso por PB ======
    function actualizarProgresoPB(pbId) {
        const meta = metaPB[pbId] || {};
        const asign = meta.objetivoKg != null ? (meta.asignadoKg || 0) : (meta.asignadoUd || 0);
        const obj = meta.objetivoKg != null ? meta.objetivoKg : meta.objetivoUd;

        const pct = obj && obj > 0 ? Math.min(100, Math.round((asign / obj) * 100)) : 0;
        const bar = document.getElementById(`bar-${pbId}`);
        const chip = document.getElementById(`estado-chip-${pbId}`);
        const head = document.getElementById(`asignado-head-${pbId}`);

        if (bar) {
            bar.style.width = pct + '%';
            bar.style.backgroundColor = pct >= 100 ? '#10B981' : (pct > 0 ? '#FBBF24' :
                '#E5E7EB'); // verde / ámbar / gris
        }
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

    function recomputarTotalesPBDesdeLista(pbId) {
        const meta = metaPB[pbId] || {};
        const lista = productosEscaneadosPorTipo[pbId] || [];
        let sumKg = 0,
            sumUd = 0;
        for (const it of lista) {
            if (typeof it.peso_kg === 'number' && it.peso_kg > 0) sumKg += it.peso_kg;
            else if (typeof it.cantidad === 'number' && it.cantidad > 0) sumUd += it.cantidad;
        }
        if (meta.objetivoKg != null) metaPB[pbId].asignadoKg = sumKg;
        if (meta.objetivoUd != null) metaPB[pbId].asignadoUd = sumUd;
    }

    // ====== Lista de asignados por PB ======
    function renderizarListaPorTipo(pbId) {
        const cont = document.getElementById(`productos-escaneados-${pbId}`);
        const lista = productosEscaneadosPorTipo[pbId] || [];
        if (!cont) return;

        if (!lista.length) {
            cont.innerHTML = `<span class="text-gray-500">Sin productos escaneados.</span>`;
            recomputarTotalesPBDesdeLista(pbId);
            actualizarProgresoPB(pbId);
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
          <th class="text-right px-2 py-1 border-b">Stock</th> <!-- NUEVA COLUMNA -->
          <th class="text-right px-2 py-1 border-b">${totalKg > 0 ? 'Asignado (kg)' : 'Asignado (ud)'}</th>
          <th class="text-left px-2 py-1 border-b">Acciones</th>
        </tr>
      </thead>
      <tbody>
        ${lista.map((p, i) => {
          const stockTxt = typeof p.peso_stock === 'number' ? `${p.peso_stock.toLocaleString()} kg` : '0 kg';
          const asignadoTxt = (typeof p.peso_kg === 'number' && p.peso_kg > 0)
            ? p.peso_kg.toLocaleString()
            : (p.cantidad ?? 0);

          return `
            <tr class="${i % 2 ? 'bg-gray-50/30' : ''}">
              <td class="px-2 py-1 border-b">${p.codigo}</td>
              <td class="px-2 py-1 border-b text-right">${stockTxt}</td>        <!-- 👈 p.peso_stock -->
              <td class="px-2 py-1 border-b text-right">${asignadoTxt}</td>
              <td class="px-2 py-1 border-b">
                <button class="text-red-600 hover:underline" onclick="eliminarEscaneado('${p.codigo}', ${pbId})">Quitar</button>
              </td>
            </tr>
          `;
        }).join('')}
      </tbody>
      <tfoot class="bg-gray-50">
        <tr>
          <td class="px-2 py-1 border-t font-semibold">Total</td>
          <td class="px-2 py-1 border-t text-right"></td> <!-- celda vacía para la columna Stock -->
          <td class="px-2 py-1 border-t text-right font-semibold">
            ${totalKg > 0 ? totalKg.toLocaleString() + ' kg' : (totalUd + ' ud')}
          </td>
          <td class="px-2 py-1 border-t"></td>
        </tr>
      </tfoot>
    </table>
  </div>
`;


        if (metaPB[pbId]?.objetivoKg != null) metaPB[pbId].asignadoKg = totalKg;
        if (metaPB[pbId]?.objetivoUd != null) metaPB[pbId].asignadoUd = totalUd;
        actualizarProgresoPB(pbId);
    }

    // ====== Refrescar desde backend (rutas web) ======
    async function refrescarAsignadosDesdeBackend(salidaId, pbIdParaRepintar = null) {
        const {
            ok,
            data
        } = await fetchJSON(`/salidas-almacen/${salidaId}/asignados`);
        if (!ok || !data?.success) return;

        const mapa = data.asignados || {}; // { [producto_base_id]: [...] }

        // 1) Reconstruye caches globales desde backend
        codigosEscaneados.clear();
        Object.keys(productosEscaneadosPorTipo).forEach(k => delete productosEscaneadosPorTipo[k]);

        // 2) Vuelca lo que venga del server
        Object.entries(mapa).forEach(([pb, arr]) => {
            const id = Number(pb);
            productosEscaneadosPorTipo[id] = Array.isArray(arr) ? arr : [];
            (productosEscaneadosPorTipo[id]).forEach(it => codigosEscaneados.add(it.codigo));
        });

        // 3) Repintar
        if (pbIdParaRepintar != null) {
            if (!productosEscaneadosPorTipo[pbIdParaRepintar]) {
                productosEscaneadosPorTipo[pbIdParaRepintar] = []; // fuerza vacío
            }
            recomputarTotalesPBDesdeLista(pbIdParaRepintar);
            actualizarProgresoPB(pbIdParaRepintar);
            renderizarListaPorTipo(pbIdParaRepintar);
        } else {
            Object.keys(productosEscaneadosPorTipo).forEach(key => {
                const id = Number(key);
                recomputarTotalesPBDesdeLista(id);
                actualizarProgresoPB(id);
                renderizarListaPorTipo(id);
            });
        }
    }

    // ====== Eliminar escaneado ======
    async function eliminarEscaneado(codigo, pbId) {
        const salidaId = window._salidaActualId;
        if (!salidaId) {
            mostrarErrorInline(pbId, 'No se reconoce la salida actual.');
            return;
        }

        // feedback sutil: deshabilita temporalmente el botón "Quitar"
        const btns = Array.from(document.querySelectorAll(`#productos-escaneados-${pbId} button`));
        btns.forEach(b => b.disabled = true);

        const res = await fetch(
            `/salidas-almacen/${encodeURIComponent(salidaId)}/detalle/${encodeURIComponent(codigo)}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            }
        );

        let data = null;
        try {
            data = await res.json();
        } catch {}

        if (!res.ok || !data?.success) {
            const msg = data?.message || 'No se pudo eliminar el producto de la salida.';
            mostrarErrorInline(pbId, msg);
            btns.forEach(b => b.disabled = false);
            return;
        }

        await refrescarAsignadosDesdeBackend(salidaId, pbId);
        btns.forEach(b => b.disabled = false);
    }

    // ====== Abrir modal de ejecución (rutas web) ======
    async function ejecutarSalidaAlmacen(movimientoId) {
        const {
            ok,
            data
        } = await fetchJSON(`/salidas-almacen/${movimientoId}/productos`);
        if (!ok || !data?.success) {
            Swal.fire('⚠️ Error', data?.message || 'No se pudo obtener la información de la salida.', 'warning');
            return;
        }

        const productosBase = data.productos_base || [];
        const salidaId = data.salida_id;
        window._salidaActualId = salidaId; // 🔑 guardar para eliminar

        let html = `
    <p class="mb-3 text-sm text-red-600">${data.observaciones || 'Escanea productos para completar la salida:'}</p>

    `;
        productosBase.forEach(pb => {
            const key = pb.producto_base_id; // clave por producto_base_id
            html += `
        <div class="p-2 border rounded bg-white">
          <div class="flex items-center justify-between">
            <div>
              <strong>Ø${pb.diametro ?? '—'} ${pb.longitud ? '· ' + pb.longitud + 'm' : ''}</strong>
              <div class="text-xs text-gray-600">
                Objetivo: ${pb.peso_objetivo_kg ? `${pb.peso_objetivo_kg} kg` : `${pb.unidades_objetivo} ud`}
                · Asignado: <span id="asignado-head-${key}">
                  ${pb.peso_objetivo_kg ? (pb.asignado_kg || 0) + ' kg' : (pb.asignado_ud || 0) + ' ud'}
                </span>
              </div>
            </div>
            <span id="estado-chip-${key}" class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700">pendiente</span>
          </div>

          <div class="w-full h-1.5 bg-gray-200 rounded mt-2">
            <div id="bar-${key}" class="h-1.5 rounded" style="width:0%"></div>
          </div>

          <input type="text"
            class="w-full mt-2 border px-2 py-1 rounded text-sm"
            placeholder="Escanea producto para este tipo..."
            data-pb="${key}"
            onkeydown="if(event.key==='Enter'){event.preventDefault(); agregarProductoEscaneado(this, ${key}, ${salidaId});}">

          <div id="error-pb-${key}" class="text-xs text-red-600 mt-1 hidden"></div>
          <div id="productos-escaneados-${key}" class="text-xs text-gray-700 mt-1"></div>
        </div>
      `;
        });
        html += '</div>';

        await Swal.fire({
            title: 'Salida Almacén',
            html,
            showCancelButton: true,
            confirmButtonText: 'Finalizar salida',
            confirmButtonColor: '#38a169',
            cancelButtonText: 'Cancelar',
            width: '50rem',
            focusConfirm: false,
            didOpen: () => {
                // Inicializar metas y listas visibles por producto_base_id
                productosBase.forEach(pb => {
                    const key = pb.producto_base_id;
                    metaPB[key] = {
                        objetivoKg: pb.peso_objetivo_kg ?? null,
                        objetivoUd: pb.unidades_objetivo ?? null,
                        asignadoKg: pb.asignado_kg ?? 0,
                        asignadoUd: pb.asignado_ud ?? 0,
                        label: etiquetaPB(pb.diametro, pb.longitud),
                        diametro: pb.diametro ?? null,
                        longitud: pb.longitud ?? null,
                    };
                    if (Array.isArray(pb.asignados)) {
                        productosEscaneadosPorTipo[key] = pb.asignados;
                        pb.asignados.forEach(it => codigosEscaneados.add(it.codigo));
                    } else if (!productosEscaneadosPorTipo[key]) {
                        productosEscaneadosPorTipo[key] = [];
                    }
                    actualizarProgresoPB(key);
                    renderizarListaPorTipo(key);
                });

                // Sincroniza con backend (persistencia/multioperario)
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
                    }
                );
                if (!resp.ok || !resp.data?.success) {
                    Swal.showValidationMessage(resp.data?.message || 'No se pudo completar la salida.');
                    return false;
                }
                return resp.data;
            }
        }).then(result => {
            if (result.isConfirmed) {
                Swal.fire('', result.value.message || 'Salida ejecutada correctamente.', 'success');
                setTimeout(() => location.reload(), 1000);
            }
        }).catch(() => {});
    }

    // ====== Escaneo / Validación (rutas web) ======
    async function agregarProductoEscaneado(input, pbId, salidaId) {
        const codigoQR = input.value.trim();
        if (!codigoQR) return;

        limpiarErrorInline(pbId);

        if (codigosEscaneados.has(codigoQR)) {
            mostrarErrorInline(pbId, 'Este producto ya ha sido escaneado.');
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
                // 🔒 Muy importante para que el backend no acepte PBs cruzados:
                producto_base_id_esperado: pbId
            })
        });

        if (!ok || !data?.success || !data.producto) {
            const msg = data?.message || data?.swal?.text ||
                (status === 422 ? 'No se pudo validar el producto.' : 'Error validando el producto.');
            mostrarErrorInline(pbId, msg);
            input.select();
            return;
        }

        // ✅ Verificación local de PB devuelto por el backend
        const producto = data.producto; // { codigo, producto_base_id, ... }
        const actualPbId = Number(producto.producto_base_id);

        if (actualPbId !== Number(pbId)) {
            const esperado = metaPB[pbId]?.label || `PB ${pbId}`;
            const encontrado = metaPB[actualPbId]?.label || `PB ${actualPbId}`;
            mostrarErrorInline(pbId, `Este código pertenece a ${encontrado}, no a ${esperado}.`);
            input.select();
            return; // no añadimos nada ni refrescamos este PB
        }

        // ✅ Coincide el PB → añadimos y refrescamos SOLO ese PB
        codigosEscaneados.add(codigoQR);
        await refrescarAsignadosDesdeBackend(salidaId, pbId);

        input.value = '';
        input.focus();
        mostrarOkPequenyo(pbId);
    }

    // ====== Exponer a window ======
    window.ejecutarSalidaAlmacen = ejecutarSalidaAlmacen;
    window.agregarProductoEscaneado = agregarProductoEscaneado;
    window.eliminarEscaneado = eliminarEscaneado;
    window.mostrarErrorInline = mostrarErrorInline;
    window.limpiarErrorInline = limpiarErrorInline;
</script>
