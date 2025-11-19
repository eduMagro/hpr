{{-- CSS para el componente de mapa (necesario para el modal de mover paquete) --}}
@once
    <link rel="stylesheet" href="{{ asset('css/localizaciones/styleLocIndex.css') }}">
@endonce

@php
    $mapaData = $mapaData ?? [];
@endphp

{{-- 🔄 MODAL MOVIMIENTO GENERAL --}}
<div id="modalMovimientoLibre"
    class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white p-6 rounded-2xl shadow-xl w-full max-w-lg">
        <h2 class="text-lg sm:text-xl font-bold mb-4 text-center text-gray-800">
            ➕ Nuevo Movimiento
        </h2>

        <form method="POST" action="{{ route('movimientos.store') }}"
            id="form-movimiento-general">
            @csrf
            <input type="hidden" name="tipo" value="movimiento libre">

            <!-- Producto -->
            <x-tabla.input-movil type="text" id="codigo_general_general"
                label="Escanear Producto o Paquete"
                placeholder="Escanea QR Ferralla" autocomplete="off"
                inputmode="text" />

            <div id="mostrar_qrs"
                data-api-info-url="{{ route('api.codigos.info') }}"></div>
            <input type="hidden" name="lista_qrs" id="lista_qrs">


            <!-- Ubicación destino (campo libre) -->
            <div class="mt-4">
                <x-tabla.input-movil name="ubicacion_destino"
                    id="ubicacion_destino_general"
                    label="Escanear Ubicación destino"
                    placeholder="Escanea ubicación o escribe Nº"
                    autocomplete="off" />

            </div>

            <!-- Máquina destino (select filtrado por obra_id de la grúa) -->
            <div class="mt-4">
                <label for="maquina_destino"
                    class="block text-sm font-medium text-gray-700">Máquina
                    destino</label>
                <select name="maquina_destino" id="maquina_destino"
                    class="w-full border border-gray-300 rounded text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    style="height:2cm; padding:0.75rem 1rem; font-size:1.5rem;">
                    <option value="">-- Selecciona máquina --</option>
                    @foreach ($maquinasDisponibles as $maq)
                        <option value="{{ $maq->id }}">
                            {{ $maq->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Botones -->
            <div class="flex justify-end gap-3 mt-6">
                <button id="cancelar_btn" type="button"
                    onclick="cerrarModalMovimientoLibre()"
                    class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg">Cancelar</button>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Registrar</button>
            </div>
        </form>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Selecciona solo los inputs del modal movimiento general
        const inputs = document.querySelectorAll(
            '#modalMovimientoLibre input');

        inputs.forEach(input => {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e
                        .preventDefault(); // ⛔ Bloquea el enter automático del escáner
                    // No hace ni submit ni salta a otro campo
                }
            });
        });
    });
</script>

{{-- 🔄 MODAL BAJADA PAQUETE --}}
<div id="modal-bajada-paquete"
    class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Reubicar paquete</h2>

        <p class="mb-2 text-sm text-gray-700"><strong>Descripción:</strong>
            <span id="descripcion_paquete"></span>
        </p>

        <form method="POST" action="{{ route('movimientos.store') }}">
            @csrf

            {{-- <input type="hidden" name="tipo" value="bajada de paquete"> --}}
            <input type="hidden" name="movimiento_id" id="movimiento_id">
            <input type="hidden" name="paquete_id" id="paquete_id">
            <input type="hidden" name="ubicacion_origen" id="ubicacion_origen">
            <!-- Escanear paquete -->
            <x-tabla.input-movil id="codigo_general" name="codigo_general"
                placeholder="ESCANEA PAQUETE" inputmode="none"
                autocomplete="off" />
            <p id="estado_verificacion" class="text-sm mt-1"></p>

            <!-- Ubicación destino -->
            <x-tabla.input-movil id="ubicacion_destino" name="ubicacion_destino"
                placeholder="ESCANEA UBICACIÓN" required />

            <!-- Botones -->
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" id="cancelar_btn"
                    onclick="cerrarModalBajadaPaquete()"
                    class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg">Cancelar</button>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Registrar</button>
            </div>
        </form>
    </div>
</div>
{{-- 🔄 MODAL RECARGA MP --}}
<div id="modalMovimiento"
    class="fixed inset-0 z-50 bg-black bg-opacity-50 hidden items-center justify-center">
    <div
        class="bg-white p-6 rounded-2xl shadow-xl w-full max-w-md mx-0 sm:mx-0">

        <h2 class="text-lg sm:text-xl font-bold mb-4 text-center text-gray-800">
            RECARGAR MÁQUINA
        </h2>

        <!-- Información tipo tabla -->
        <div
            class="grid grid-cols-2 gap-3 mb-4 text-sm sm:text-base text-gray-700">
            <div class="bg-gray-100 rounded-lg p-3 shadow-sm">
                <p class="font-semibold text-gray-600 text-xs sm:text-sm"><i
                        class="fas fa-industry"></i>
                    {{-- fa-industry --}}
                </p>
                <p id="maquina-nombre-destino"
                    class="text-green-700 font-bold text-lg sm:text-xl mt-1">
                </p>
            </div>
            <div class="bg-gray-100 rounded-lg p-3 shadow-sm">
                <p class="font-semibold text-gray-600 text-xs sm:text-sm">🧱
                    Tipo</p>
                <p id="producto-tipo"
                    class="text-gray-800 font-bold text-xl mt-1"></p>
            </div>
            <div class="bg-gray-100 rounded-lg p-3 shadow-sm">
                <p class="font-semibold text-gray-600 text-xs sm:text-sm">⌀
                    Diámetro</p>
                <p id="producto-diametro"
                    class="text-gray-800 font-bold text-lg sm:text-xl mt-1">
                </p>
            </div>
            <div class="bg-gray-100 rounded-lg p-3 shadow-sm">
                <p class="font-semibold text-gray-600 text-xs sm:text-sm">📏
                    Longitud</p>
                <p id="producto-longitud"
                    class="text-gray-800 font-bold text-lg sm:text-xl mt-1">
                </p>
            </div>
        </div>

        <!-- Ubicaciones sugeridas -->
        <div id="ubicaciones-actuales" class="mb-4 hidden">
            <div class="border-t pt-3">
                <label
                    class="font-semibold block mb-2 text-gray-700 text-sm sm:text-base">
                    📍 Ubicaciones con producto disponible
                </label>
                <ul id="ubicaciones-lista"
                    class="list-disc list-inside text-gray-700 text-sm pl-4 space-y-1">
                </ul>
            </div>
        </div>

        <!-- Formulario -->
        <form method="POST" action="{{ route('movimientos.store') }}"
            id="form-ejecutar-movimiento">
            @csrf
            <input type="hidden" name="tipo" id="modal_tipo">
            <input type="hidden" name="producto_base_id"
                id="modal_producto_base_id">
            <input type="hidden" name="maquina_destino"
                id="modal_maquina_id">

            <x-tabla.input-movil type="text" name="codigo_general"
                id="modal_producto_id" placeholder="ESCANEA QR MATERIA PRIMA"
                inputmode="none" autocomplete="off" required />
            <input type="hidden" name="lista_qrs" id="modal_lista_qrs">

            <!-- Botones -->
            <div class="flex justify-end gap-3 mt-6">
                <button type="button"
                    onclick="cerrarModalRecargaMateriaPrima()"
                    class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg">Cancelar</button>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Registrar</button>
            </div>

        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById(
                'form-ejecutar-movimiento');
            const inputQR = document.getElementById('modal_producto_id');
            const hiddenLista = document.getElementById('modal_lista_qrs');

            form.addEventListener('submit', (e) => {
                const valor = inputQR.value.trim();

                if (valor) {
                    // Convertimos el valor en array JSON (aunque sea uno solo)
                    hiddenLista.value = JSON.stringify([valor]);
                } else {
                    // Si está vacío, lo dejamos vacío para que el backend valide
                    hiddenLista.value = '';
                }
            });
        });
    </script>


</div>
{{-- 🔄 MODAL DESCARGA MATERIA PRIMA --}}
<div id="modal-ver-pedido"
    class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white w-full max-w-2xl rounded shadow-lg p-6 relative">
        <button onclick="cerrarModalPedido()"
            class="absolute top-2 right-2 text-gray-500 hover:text-black text-xl">&times;</button>

        <h2 class="text-xl font-bold mb-4">Pedido vinculado al movimiento</h2>

        <div id="contenidoPedido" class="space-y-3 text-sm">
            {{-- El contenido se rellena por JavaScript --}}
        </div>
    </div>
</div>
<script>
    function abrirModalRecargaMateriaPrima(id, tipo, productoCodigo, maquinaId,
        productoBaseId, ubicacionesSugeridas,
        maquinaNombre, tipoBase, diametroBase, longitudBase) {

        document.getElementById('modal_tipo').value = tipo;
        document.getElementById('modal_maquina_id').value = maquinaId;
        document.getElementById('modal_producto_id').value =
            productoCodigo; // ← aquí va el código
        document.getElementById('modal_producto_base_id').value =
            productoBaseId;

        document.getElementById('maquina-nombre-destino').textContent =
            maquinaNombre;
        document.getElementById('producto-tipo').textContent = tipoBase;
        document.getElementById('producto-diametro').textContent =
            `${diametroBase} mm`;
        document.getElementById('producto-longitud').textContent =
            `${longitudBase} mm`;

        const lista = document.getElementById('ubicaciones-lista');
        lista.innerHTML = '';

        if (ubicacionesSugeridas && ubicacionesSugeridas.length > 0) {
            document.getElementById('ubicaciones-actuales').classList.remove(
                'hidden');
            ubicacionesSugeridas.forEach(u => {
                const li = document.createElement('li');
                li.textContent = `${u.nombre} (Código: ${u.codigo})`;

                lista.appendChild(li);
            });
        } else {
            document.getElementById('ubicaciones-actuales').classList.add(
                'hidden');
        }

        document.getElementById('modalMovimiento').classList.remove('hidden');
        document.getElementById('modalMovimiento').classList.add('flex');

        // Focus en el campo QR
        setTimeout(() => {
            document.getElementById("modal_producto_id")?.focus();
        }, 100);
    }

    function cerrarModalRecargaMateriaPrima() {
        document.getElementById('modalMovimiento').classList.add('hidden');
        document.getElementById('modalMovimiento').classList.remove('flex');
    }

    function abrirModalMovimientoLibre(codigo = null) {
        const modal = document.getElementById('modalMovimientoLibre');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        const eventoEnter = new KeyboardEvent("keydown", {
            key: "Enter",
            code: "Enter",
            keyCode: 13,
            which: 13,
            bubbles: true,
        });

        const inputQR = document.getElementById("codigo_general_general");

        setTimeout(() => {
            if (!inputQR) return;

            if (codigo) {
                const code = String(codigo).trim().toUpperCase();
                inputQR.value = code;
                inputQR.dispatchEvent(eventoEnter);
            }

            inputQR.focus();
        }, 100);
    }

    function cerrarModalMovimientoLibre() {
        const modal = document.getElementById('modalMovimientoLibre');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Mostrar/ocultar campos según tipo
    document.addEventListener('DOMContentLoaded', function() {
        const tipoSelect = document.getElementById('tipo');
        const productoSection = document.getElementById(
            'producto-section');
        const paqueteSection = document.getElementById(
            'paquete-section');

        tipoSelect.addEventListener('change', function() {
            if (this.value === 'producto') {
                productoSection.classList.remove('hidden');
                paqueteSection.classList.add('hidden');
            } else if (this.value === 'paquete') {
                productoSection.classList.add('hidden');
                paqueteSection.classList.remove('hidden');
            }
        });
    });

    let paqueteEsperadoId = null;

    function abrirModalBajadaPaquete(data) {
        document.getElementById('movimiento_id').value = data.id;
        document.getElementById('paquete_id').value = data.paquete_id;
        document.getElementById('ubicacion_origen').value = data
            .ubicacion_origen;
        document.getElementById('descripcion_paquete').innerText = data
            .descripcion;

        paqueteEsperadoId = data.paquete_id;
        document.getElementById('codigo_general').value = '';
        document.getElementById('estado_verificacion').innerText = '';
        document.getElementById('codigo_general').classList.remove(
            'border-green-500', 'border-red-500');

        document.getElementById('modal-bajada-paquete').classList.remove(
            'hidden');

        // Esperar un poco para que se renderice el DOM
        setTimeout(() => {
            const input = document.getElementById('codigo_general');
            if (input) input.focus();
        }, 100);
    }

    function cerrarModalBajadaPaquete() {
        document.getElementById('modal-bajada-paquete').classList.add('hidden');
    }

    function abrirModalPedidoDesdeMovimiento(movimiento) {
        if (!movimiento || !movimiento.pedido || !movimiento.pedido_producto)
            return;

        const pedido = movimiento.pedido;
        const linea = movimiento.pedido_producto; // 👈 línea de pedido
        const producto = movimiento.producto_base;

        const tipo = producto?.tipo ?? '—';
        const diametro = producto?.diametro ?? '—';
        const longitud = producto?.longitud ??
            '—'; // suele venir en metros si es barra

        const proveedor = (pedido.fabricante_id && pedido.fabricante?.nombre) ?
            pedido.fabricante.nombre :
            (pedido.distribuidor?.nombre ?? '—');

        // Datos de línea
        const cantidadKg = Number(linea.cantidad ??
            0); // total pedido para esa línea
        const recepcionadoKg = Number(linea.cantidad_recepcionada ?? 0);
        const restanteKg = Math.max(0, cantidadKg - recepcionadoKg);
        const estadoLinea = linea.estado ?? '—';
        const fechaLinea = linea.fecha_estimada_entrega ?
            new Date(linea.fecha_estimada_entrega).toLocaleDateString('es-ES') :
            '—';

        // Info general del pedido (opcionales)
        const pesoPedidoRed = Math.round(pedido.peso_total || 0) + ' kg';

        // Formateo longitud según tipo
        const longitudFmt = (tipo === 'barra' && !isNaN(Number(longitud))) ?
            `${longitud} m` :
            (longitud ? `${longitud} mm` : '—');

        const contenedor = document.getElementById('contenidoPedido');
        const modal = document.getElementById('modal-ver-pedido');
        // Este maquinaId lo usaremos para mostrar las ubicaciones correctas en la recepcion dependiendo de la grua en la que estemos.
        //Añadimos maquinaId en la url hrefRecepcion para pasarlo a la vista recepcion
        const maquinaId = window.maquinaId || '';
        // ⚠️ Importante: pasamos pedido_producto_id por query para que el back lo coja.
        const hrefRecepcion =
            `/pedidos/${pedido.id}/recepcion/${producto?.id ?? movimiento.producto_base_id}?movimiento_id=${movimiento.id}&maquina_id=${maquinaId}`;


        contenedor.innerHTML = `
    <p><strong>Proveedor:</strong> ${proveedor}</p>
    <p><strong>Código Pedido:</strong> ${pedido.codigo ?? pedido.id}</p>

    <hr class="my-3" />

    <p><strong>Línea de pedido:</strong> #${linea.id}</p>
    <p><strong>Fecha estimada:</strong> ${fechaLinea}</p>
    <p><strong>Cantidad pedida:</strong> ${cantidadKg.toLocaleString('es-ES')} kg</p>

    <hr class="my-3" />

    <p><strong>Tipo:</strong> ${tipo}</p>
    <p><strong>Diámetro:</strong> ${diametro} mm</p>
    <p><strong>Longitud:</strong> ${longitudFmt}</p>

    <a href="${hrefRecepcion}"
       class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded shadow inline-block mt-4">
      RECEPCIONAR
    </a>
  `;

        modal.classList.remove('hidden');
    }



    function cerrarModalPedido() {
        document.getElementById('modal-ver-pedido').classList.add('hidden');
    }
</script>

<style>
    /* Contenedor de los códigos mostrados como píldoras */
    #mostrar_qrs {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-top: .5rem;
    }

    /* Estilo de cada “píldora” de código (clicable para borrar) */
    .qr-chip {
        display: inline-flex;
        align-items: center;
        padding: .25rem .6rem;
        border-radius: 9999px;
        background: #e5e7eb;
        font-weight: 600;
        cursor: pointer;
        user-select: none;
        transition: background .15s ease, color .15s ease, transform .05s ease;
        white-space: nowrap;
    }

    .qr-chip:hover {
        background: #fee2e2;
        color: #991b1b;
    }

    .qr-chip:active {
        transform: scale(0.98);
    }

    /* Estado cargando (spinner simple con dots) */
    .qr-chip.loading {
        position: relative;
        color: #374151;
        /* slate-700 */
    }

    .qr-chip.loading::after {
        content: "";
        width: 12px;
        height: 12px;
        margin-left: .4rem;
        border-radius: 50%;
        border: 2px solid currentColor;
        border-top-color: transparent;
        display: inline-block;
        animation: spin .8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Estado error (píldora en tono más rojizo) */
    .qr-chip.error {
        background: #fecaca;
        color: #7f1d1d;
    }
</style>

<script src="{{ asset('js/movimientos/movimientosgrua.js') }}"></script>

{{-- 📦 MODAL MOVER PAQUETE (3 pasos: escanear, validar, ubicar en mapa) --}}
<div id="modal-mover-paquete"
    class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div
        class="bg-white rounded-2xl shadow-xl w-full max-w-6xl max-h-[95vh] overflow-hidden flex flex-col">
        {{-- Header --}}
        <div
            class="bg-gradient-to-r from-green-600 to-green-700 text-white p-4 flex justify-between items-center">
            <h2 class="text-lg sm:text-xl font-bold">📦 Mover Paquete a Nueva
                Ubicación</h2>
            <button onclick="cerrarModalMoverPaquete()"
                class="text-white hover:text-gray-200 text-2xl">&times;</button>
        </div>

        {{-- Contenido --}}
        <div class="flex-1 overflow-auto p-4">
            {{-- PASO 1: Escanear código --}}
            <div id="paso-escanear-paquete" class="space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-800 mb-2">Paso 1:
                        Escanear Código del Paquete</h3>
                    <p class="text-sm text-blue-600 mb-3">Escanea o introduce
                        el código QR del paquete que deseas mover.</p>

                    <x-tabla.input-movil type="text"
                        id="codigo_paquete_mover" label="Código del Paquete"
                        placeholder="Escanea o escribe el código (ej: ETQ123456.01)"
                        autocomplete="off" inputmode="text" />

                    <div id="error-paquete-mover"
                        class="hidden mt-2 text-sm text-red-600 bg-red-50 p-2 rounded">
                    </div>
                    <div id="loading-paquete-mover"
                        class="hidden mt-2 text-sm text-gray-600">
                        <div class="flex items-center gap-2">
                            <div
                                class="animate-spin rounded-full h-4 w-4 border-b-2 border-green-600">
                            </div>
                            <span>Buscando paquete...</span>
                        </div>
                    </div>
                </div>

                {{-- Info del paquete (se muestra despuÃ©s de validar) --}}
                <div id="info-paquete-validado"
                    class="hidden bg-green-50 border border-green-200 rounded-lg p-4">
                    <h3 class="font-semibold text-green-800 mb-3">✓ Paquete
                        Encontrado</h3>
                    <div
                        class="flex flex-col md:flex-row justify-start gap-3 text-sm">
                        <div class="bg-white p-2 rounded border">
                            <p class="text-gray-600 text-xs">Código Paquete</p>
                            <p id="paquete-codigo-info"
                                class="font-bold text-gray-800"></p>
                        </div>
                        <div class="bg-white p-2 rounded border">
                            <p class="text-gray-600 text-xs">Etiquetas /
                                Elementos</p>
                            <p id="paquete-peso-info"
                                class="font-bold text-gray-800"></p>
                        </div>
                    </div>
                    <button onclick="mostrarPasoMapa()"
                        class="mt-4 w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg shadow">
                        Seleccionar Ubicación en Mapa
                    </button>
                </div>
            </div>

            {{-- PASO 2: Seleccionar ubicación en mapa --}}
            <div id="paso-mapa-paquete" class="hidden space-y-4">
                <div
                    class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h3 class="font-semibold text-yellow-800 mb-2">Paso 2:
                        Seleccionar Nueva Ubicación</h3>

                    {{-- Información del paquete seleccionado --}}
                    <div class="bg-white p-3 rounded border mb-3">
                        <p class="text-sm"><strong>Paquete:</strong> <span
                                id="paquete-codigo-mapa"
                                class="text-green-700 font-bold"></span></p>
                    </div>
                </div>

                @if (!empty($mapaData))
                    <div class="bg-white p-4 rounded-lg border space-y-3">
                        <div class="space-y-1">
                            <p
                                class="text-xs uppercase tracking-wide text-gray-500">
                                Nave</p>
                            <p class="text-lg font-semibold text-gray-800">
                                {{ $mapaData['dimensiones']['obra'] ?? 'Sin nave' }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-gray-200 overflow-hidden bg-white shadow-inner"
                            style="min-height: 420px;">
                            <x-mapa-component :ctx="$mapaData['ctx'] ?? []"
                                :localizaciones-zonas="$mapaData['localizacionesZonas'] ??
                                    []" :localizaciones-maquinas="$mapaData[
                                    'localizacionesMaquinas'
                                ] ?? []"
                                :paquetes-con-localizacion="$mapaData[
                                    'paquetesConLocalizacion'
                                ] ?? []" :dimensiones="$mapaData['dimensiones'] ?? null"
                                :obra-actual-id="$mapaData['obraActualId'] ?? null" :map-id="$mapaData['mapaId'] ?? null"
                                :show-controls="false" :mostrarObra="false"
                                :show-scan-result="false" :ruta-paquete="route('paquetes.tamaño')"
                                :ruta-guardar="route('localizaciones.storePaquete')" :modo-modal="true"
                                :enable-drag-paquetes="true" height="100%"
                                class="w-full h-[420px]" />
                        </div>
                    </div>
                @else
                    <div id="contenedor-mapa-paquete"
                        class="bg-gray-100 rounded-lg border-2 border-dashed border-gray-300 min-h-[400px] flex items-center justify-center">
                        <div class="text-center text-gray-500">
                            <svg class="w-16 h-16 mx-auto mb-3 text-gray-300"
                                fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round"
                                    stroke-linejoin="round" stroke-width="2"
                                    d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            <p class="font-medium">Mapa no disponible</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Footer con botones --}}
        <div class="border-t p-4 bg-gray-50 flex justify-end gap-3">
            <button onclick="cerrarModalMoverPaquete()"
                class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg font-medium">
                Cancelar
            </button>
        </div>
    </div>
</div>

{{-- Scripts para el modal de mover paquete --}}
<script>
    let paqueteMoverData = null;
    let coordenadasPaquete = {
        x1: null,
        y1: null,
        x2: null,
        y2: null
    };
    const MAPA_MODAL_ID = '{{ $mapaData['mapaId'] ?? '' }}';
    let mapaModalApi = null;
    let mapaModalUnsubscribe = null;

    function abrirModalMoverPaquete() {
        const modal = document.getElementById('modal-mover-paquete');
        modal.classList.remove('hidden');

        // resetearModalMoverPaquete();

        setTimeout(() => {
            document.getElementById('codigo_paquete_mover')?.focus();
        }, 100);
    }

    function cerrarModalMoverPaquete() {
        document.getElementById('modal-mover-paquete').classList.add('hidden');
        resetearModalMoverPaquete();
    }

    function resetearModalMoverPaquete() {
        const inputCodigo = document.getElementById('codigo_paquete_mover');
        if (inputCodigo) inputCodigo.value = '';

        document.getElementById('info-paquete-validado').classList.add(
            'hidden');
        document.getElementById('paso-mapa-paquete').classList.add('hidden');
        document.getElementById('paso-escanear-paquete').classList.remove(
            'hidden');
        document.getElementById('error-paquete-mover').classList.add('hidden');
        document.getElementById('loading-paquete-mover').classList.add(
            'hidden');
        document.getElementById('coordenadas-seleccionadas').classList.add(
            'hidden');
        document.getElementById('coord-x1').value = '';
        document.getElementById('coord-y1').value = '';
        document.getElementById('coord-x2').value = '';
        document.getElementById('coord-y2').value = '';

        paqueteMoverData = null;
        coordenadasPaquete = {
            x1: null,
            y1: null,
            x2: null,
            y2: null
        };

        if (mapaModalUnsubscribe) {
            mapaModalUnsubscribe();
            mapaModalUnsubscribe = null;
        }
        if (mapaModalApi?.showAllPaquetes) {
            mapaModalApi.showAllPaquetes();
        }
        mapaModalApi = null;
    }

    async function mostrarPasoMapa() {
        document.getElementById('paso-escanear-paquete').classList.add(
            'hidden');
        document.getElementById('paso-mapa-paquete').classList.remove(
            'hidden');
        document.getElementById('paquete-codigo-mapa').textContent =
            paqueteMoverData?.codigo || '';
        const idPaquete = document.getElementById("paquete-codigo-info")
            .innerText;

        // ocultar los demás paquetes en el mapa
        // Obtenemos el canvas del mapa (el div con data-mapa-canvas)
        const canvas = document.querySelector('[data-mapa-canvas]');
        // La instancia JS del mapa la expone el componente en canvas.mapaInstance
        const mapaInstance = canvas?.mapaInstance;

        if (!mapaInstance) {
            console.warn('No se encontró la instancia del mapa');
            return;
        }

        let codigoPaquete = document.getElementById("paquete-codigo-info")
            .innerText;
        document.querySelectorAll('.loc-paquete').forEach(paquete => {
            let id = paquete.dataset.codigo;

            if (id != codigoPaquete) {
                paquete.style.display = 'none';
                paquete.classList.remove('loc-paquete--highlight');
            } else {
                paquete.click();
                document.querySelector('[title="Mover paquete"]').click();
            }
        });
    }

    function volverPasoEscaneo() {
        document.getElementById('paso-mapa-paquete').classList.add('hidden');
        document.getElementById('paso-escanear-paquete').classList.remove(
            'hidden');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const inputCodigo = document.getElementById(
            'codigo_paquete_mover');
        if (inputCodigo) {
            inputCodigo.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    buscarPaqueteParaMover();
                }
            });
        }
    });

    async function buscarPaqueteParaMover() {
        const codigo = document.getElementById('codigo_paquete_mover').value
            .trim();
        if (!codigo) {
            mostrarErrorPaquete('Debes introducir un código de paquete.');
            return;
        }

        document.getElementById('loading-paquete-mover').classList.remove(
            'hidden');
        document.getElementById('error-paquete-mover').classList.add(
            'hidden');
        document.getElementById('info-paquete-validado').classList.add(
            'hidden');

        try {
            const response = await fetch('/paquetes/tamaño', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector(
                            'meta[name="csrf-token"]')?.content ||
                        '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    codigo: codigo
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || data.message ||
                    'Paquete no encontrado');
            }

            paqueteMoverData = data;

            document.getElementById('paquete-codigo-info').textContent =
                data.codigo || codigo;
            document.getElementById('paquete-peso-info').textContent =
                `${data.etiquetas_count || 0} etq / ${data.elementos_count || 0} elem`;

            document.getElementById('info-paquete-validado').classList
                .remove('hidden');

        } catch (error) {
            console.error('Error al buscar paquete:', error);
            mostrarErrorPaquete(error.message ||
                'No se encontró el paquete. Verifica el código.');
        } finally {
            document.getElementById('loading-paquete-mover').classList.add(
                'hidden');
        }
    }

    function mostrarErrorPaquete(mensaje) {
        const errorDiv = document.getElementById('error-paquete-mover');
        errorDiv.textContent = mensaje;
        errorDiv.classList.remove('hidden');
    }
</script>

{{-- 🚛 MODAL EJECUTAR SALIDA (con mapa y escaneo de paquetes) --}}
<div id="modal-ejecutar-salida"
    class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-7xl max-h-[95vh] overflow-hidden flex flex-col">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-purple-600 to-purple-700 text-white p-4 flex justify-between items-center">
            <div>
                <h2 class="text-lg sm:text-xl font-bold">🚛 Ejecutar Salida</h2>
                <p class="text-sm text-purple-100" id="salida-codigo-header">Cargando...</p>
            </div>
            <button onclick="cerrarModalEjecutarSalida()"
                class="text-white hover:text-gray-200 text-2xl">&times;</button>
        </div>

        {{-- Barra de escaneo --}}
        <div class="bg-gray-50 border-b p-4">
            <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                <div class="flex-1 w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Escanear Etiqueta / Subetiqueta
                    </label>
                    <input type="text" id="codigo_etiqueta_salida"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                        placeholder="Escanea el código de la etiqueta..."
                        autocomplete="off">
                </div>
                <div class="flex items-end gap-2">
                    <div class="text-sm">
                        <p class="text-gray-600">Escaneadas: <span id="contador-escaneadas" class="font-bold text-purple-600">0</span></p>
                        <p class="text-gray-600">Total: <span id="contador-total" class="font-bold">0</span></p>
                    </div>
                </div>
            </div>
            <div id="mensaje-escaneo" class="hidden mt-2 p-2 rounded-lg text-sm"></div>
        </div>

        {{-- Contenido principal: Mapa + Lista --}}
        <div class="flex-1 overflow-hidden flex flex-col lg:flex-row">
            {{-- MAPA (Izquierda) --}}
            <div class="flex-1 p-4 overflow-auto">
                <div class="bg-white rounded-lg border h-full min-h-[400px]" id="contenedor-mapa-salida">
                    <div class="flex items-center justify-center h-full text-gray-400">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            <p class="text-gray-500">Cargando mapa...</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- LISTA DE PAQUETES (Derecha) --}}
            <div class="w-full lg:w-96 border-t lg:border-t-0 lg:border-l bg-gray-50 flex flex-col">
                <div class="p-4 border-b bg-white">
                    <h3 class="font-semibold text-gray-800">Paquetes de la Salida</h3>
                    <p class="text-xs text-gray-500 mt-1">Haz clic para ver ubicación en el mapa</p>
                </div>
                <div class="flex-1 overflow-y-auto p-4 space-y-2" id="lista-paquetes-salida">
                    {{-- Los paquetes se cargarán aquí dinámicamente --}}
                </div>
            </div>
        </div>

        {{-- Footer con botones --}}
        <div class="border-t p-4 bg-gray-50 flex flex-col sm:flex-row justify-between gap-3">
            <div class="text-sm text-gray-600">
                <p id="mensaje-validacion" class="hidden"></p>
            </div>
            <div class="flex gap-3">
                <button onclick="cerrarModalEjecutarSalida()"
                    class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg font-medium">
                    Cancelar
                </button>
                <button onclick="completarSalida()" id="btn-completar-salida"
                    class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    Completar Salida
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Scripts para modal de ejecutar salida --}}
<script>
    let salidaData = null;
    let paquetesSalida = [];
    let etiquetasEscaneadas = new Set();
    let paquetesCompletados = new Set();
    let mapaEjecutarSalidaApi = null;
    let navesSalida = [];
    let naveSeleccionadaId = null;

    function abrirModalEjecutarSalida(movimientoId, salidaId) {
        console.log('=== Abriendo modal ejecutar salida ===');
        console.log('Movimiento ID:', movimientoId);
        console.log('Salida ID:', salidaId);

        const modal = document.getElementById('modal-ejecutar-salida');
        if (!modal) {
            console.error('No se encontró el modal modal-ejecutar-salida');
            return;
        }

        modal.classList.remove('hidden');

        // Resetear estado
        etiquetasEscaneadas.clear();
        paquetesCompletados.clear();
        paquetesSalida = [];
        salidaData = {
            movimientoId: movimientoId,
            salidaId: salidaId
        };
        navesSalida = [];
        naveSeleccionadaId = null;

        // Cargar datos de la salida
        if (!salidaId) {
            console.error('SalidaId es null o undefined');
            alert('Error: No se encontró el ID de la salida');
            return;
        }

        cargarDatosSalida(salidaId);

        // Configurar event listener para el input de escaneo
        const inputEscaneo = document.getElementById('codigo_etiqueta_salida');
        if (inputEscaneo) {
            // Remover listener previo si existe
            inputEscaneo.replaceWith(inputEscaneo.cloneNode(true));
            const newInput = document.getElementById('codigo_etiqueta_salida');

            newInput.addEventListener('keypress', async function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const codigo = this.value.trim();
                    if (codigo) {
                        await validarYRegistrarEtiqueta(codigo);
                        this.value = '';
                    }
                }
            });

            // Focus en input de escaneo
            setTimeout(() => {
                newInput.focus();
            }, 300);
        }
    }

    function cerrarModalEjecutarSalida() {
        document.getElementById('modal-ejecutar-salida').classList.add('hidden');
        resetearModalEjecutarSalida();
    }

    function resetearModalEjecutarSalida() {
        const inputCodigo = document.getElementById('codigo_etiqueta_salida');
        const listaPaquetes = document.getElementById('lista-paquetes-salida');
        const contenedorMapa = document.getElementById('contenedor-mapa-salida');

        if (inputCodigo) inputCodigo.value = '';
        if (listaPaquetes) listaPaquetes.innerHTML = '';
        if (contenedorMapa) {
            contenedorMapa.innerHTML = `
                <div class="flex items-center justify-center h-full text-gray-400">
                    <div class="text-center">
                        <svg class="w-16 h-16 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        <p class="text-gray-500">Cargando mapa...</p>
                    </div>
                </div>
            `;
        }

        etiquetasEscaneadas.clear();
        paquetesCompletados.clear();
        paquetesSalida = [];
        salidaData = null;
        navesSalida = [];
        naveSeleccionadaId = null;
        actualizarContadores();
    }

    async function cargarDatosSalida(salidaId) {
        try {
            console.log('Cargando datos de salida:', salidaId);
            const response = await fetch(`/salidas/${salidaId}/paquetes`);
            const data = await response.json();

            console.log('Datos recibidos:', data);

            if (!data.success) {
                throw new Error(data.message || 'Error al cargar datos de la salida');
            }

            salidaData = {
                ...salidaData,
                ...data.salida
            };
            paquetesSalida = data.paquetes || [];

            // Agrupar paquetes por nave (usando el backend si lo envía, o calculándolo aquí)
            const navesDesdeApi = Array.isArray(data.paquetesPorNave) ? data.paquetesPorNave : [];
            const navesDesdePaquetes = agruparNavesDesdePaquetes(paquetesSalida);
            navesSalida = navesDesdeApi.length ? navesDesdeApi : navesDesdePaquetes;

            console.log('Paquetes cargados:', paquetesSalida.length);

            // Actualizar header
            const headerElement = document.getElementById('salida-codigo-header');
            if (headerElement) {
                headerElement.textContent = `Salida: ${data.salida.codigo_salida}`;
            }

            // Renderizar lista de paquetes
            renderizarListaPaquetes();

            // Inicializar mapa en función de las naves detectadas
            inicializarMapaSalida();

            // Actualizar contadores
            actualizarContadores();

        } catch (error) {
            console.error('Error al cargar datos de salida:', error);
            mostrarMensajeEscaneo('Error al cargar la salida: ' + error.message, 'error');
        }
    }

    function renderizarListaPaquetes() {
        const lista = document.getElementById('lista-paquetes-salida');
        if (!lista) {
            console.error('No se encontró el elemento lista-paquetes-salida');
            return;
        }

        lista.innerHTML = '';

        paquetesSalida.forEach(paquete => {
            const isCompletado = paquetesCompletados.has(paquete.id);
            const etiquetasEscaneadasPaquete = paquete.etiquetas.filter(e =>
                etiquetasEscaneadas.has(e.codigo)
            ).length;

            const div = document.createElement('div');
            div.className = `p-3 rounded-lg border-2 cursor-pointer transition-all ${
                isCompletado
                    ? 'bg-green-50 border-green-500'
                    : 'bg-white border-gray-200 hover:border-purple-300'
            }`;
            div.onclick = () => togglePaqueteEnMapa(paquete.id);

            div.innerHTML = `
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            ${isCompletado ?
                                '<span class="text-green-600">✓</span>' :
                                '<span class="text-gray-400">○</span>'
                            }
                            <p class="font-semibold text-gray-800 truncate">${paquete.codigo}</p>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">${paquete.obra}</p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs px-2 py-0.5 rounded ${
                                paquete.tipo === 'barras' ? 'bg-blue-100 text-blue-700' :
                                paquete.tipo === 'estribos' ? 'bg-orange-100 text-orange-700' :
                                'bg-purple-100 text-purple-700'
                            }">${paquete.tipo}</span>
                            <span class="text-xs text-gray-500">${paquete.peso} kg</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-medium ${isCompletado ? 'text-green-600' : 'text-gray-500'}">
                            ${etiquetasEscaneadasPaquete}/${paquete.num_etiquetas}
                        </p>
                        <p class="text-xs text-gray-400">etiquetas</p>
                    </div>
                </div>
            `;

            lista.appendChild(div);
        });
    }

    function agruparNavesDesdePaquetes(paquetes) {
        const mapa = new Map();

        paquetes.forEach(paquete => {
            const naveId = paquete.nave_id;
            if (!naveId) return;

            if (!mapa.has(naveId)) {
                mapa.set(naveId, {
                    nave_id: naveId,
                    nave_nombre: paquete.obra || `Nave ${naveId}`,
                });
            }
        });

        return Array.from(mapa.values());
    }

    function inicializarMapaSalida() {
        const contenedor = document.getElementById('contenedor-mapa-salida');
        if (!contenedor) return;

        if (!navesSalida || navesSalida.length === 0) {
            contenedor.innerHTML = `
                <div class="flex items-center justify-center h-full text-gray-400">
                    <div class="text-center">
                        <svg class="w-10 h-10 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        <p class="text-gray-500">Los paquetes de esta salida no tienen nave con mapa asignado.</p>
                    </div>
                </div>
            `;
            return;
        }

        naveSeleccionadaId = navesSalida[0]?.nave_id || null;

        const wrapper = document.createElement('div');
        wrapper.className = 'h-full flex flex-col';

        if (navesSalida.length > 1) {
            const barra = document.createElement('div');
            barra.className = 'border-b bg-gray-50 px-3 py-2 flex items-center gap-2';
            barra.innerHTML = `
                <label for="select-nave-salida" class="text-xs font-medium text-gray-600">Nave</label>
                <select id="select-nave-salida"
                    class="border border-gray-300 rounded px-2 py-1 text-sm focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
                    ${navesSalida.map(n => `
                        <option value="${n.nave_id}">
                            ${n.nave_nombre || `Nave ${n.nave_id}`}
                        </option>
                    `).join('')}
                </select>
            `;
            wrapper.appendChild(barra);
        } else {
            const unica = navesSalida[0];
            const barra = document.createElement('div');
            barra.className = 'border-b bg-gray-50 px-3 py-2 text-sm text-gray-700';
            barra.textContent = `Nave: ${unica.nave_nombre || `Nave ${unica.nave_id}`}`;
            wrapper.appendChild(barra);
        }

        const cuerpo = document.createElement('div');
        cuerpo.id = 'mapa-salida-body';
        cuerpo.className = 'flex-1';
        cuerpo.innerHTML = `
            <div class="flex items-center justify-center h-full text-gray-400">
                <div class="text-center">
                    <svg class="w-10 h-10 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    <p class="text-gray-500">Cargando mapa...</p>
                </div>
            </div>
        `;
        wrapper.appendChild(cuerpo);

        contenedor.innerHTML = '';
        contenedor.appendChild(wrapper);

        const select = document.getElementById('select-nave-salida');
        if (select) {
            select.value = naveSeleccionadaId;
            select.addEventListener('change', (e) => {
                const value = parseInt(e.target.value, 10);
                naveSeleccionadaId = Number.isFinite(value) ? value : null;
                cargarMapaSalida();
            });
        }

        cargarMapaSalida();
    }

    async function cargarMapaSalida() {
        const contenedor = document.getElementById('contenedor-mapa-salida');
        const cuerpo = document.getElementById('mapa-salida-body');
        if (!contenedor || !cuerpo) return;

        if (!naveSeleccionadaId || !salidaData?.salidaId) {
            cuerpo.innerHTML = `
                <div class="flex items-center justify-center h-full text-gray-400">
                    <p class="text-gray-500 text-sm">No hay nave seleccionada para esta salida.</p>
                </div>
            `;
            return;
        }

        cuerpo.innerHTML = `
            <div class="flex items-center justify-center h-full text-gray-400">
                <div class="text-center">
                    <svg class="w-10 h-10 mx-auto mb-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke-width="4" class="opacity-25"></circle>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4"
                            d="M4 12a8 8 0 018-8" class="opacity-75"></path>
                    </svg>
                    <p class="text-gray-500">Cargando mapa de la nave...</p>
                </div>
            </div>
        `;

        try {
            const response = await fetch(`/salidas/${salidaData.salidaId}/mapa/${naveSeleccionadaId}`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Error al cargar el mapa');
            }

            cuerpo.innerHTML = data.html || `
                <div class="flex items-center justify-center h-full text-gray-400">
                    <p class="text-gray-500 text-sm">Mapa vacío para esta nave.</p>
                </div>
            `;

            // Asegurarnos de que en el mapa solo se vean los paquetes de la salida actual
            filtrarPaquetesEnMapaActual();
        } catch (error) {
            console.error('Error al cargar mapa de nave:', error);
            cuerpo.innerHTML = `
                <div class="flex items-center justify-center h-full text-red-500 text-sm">
                    Error al cargar el mapa de la nave.
                </div>
            `;
        }
    }

    function filtrarPaquetesEnMapaActual() {
        const contenedor = document.getElementById('contenedor-mapa-salida');
        if (!contenedor || !Array.isArray(paquetesSalida)) return;

        const idsVisibles = new Set(
            paquetesSalida
                .filter(p => p.nave_id === naveSeleccionadaId)
                .map(p => p.id)
        );

        contenedor.querySelectorAll('.loc-paquete').forEach(el => {
            const idAttr = el.dataset.paqueteId || el.dataset.id;
            const id = parseInt(idAttr || '0', 10);
            if (idsVisibles.has(id)) {
                el.style.display = '';
            } else {
                el.style.display = 'none';
            }
        });
    }

    async function togglePaqueteEnMapa(paqueteId) {
        const paquete = paquetesSalida.find(p => p.id === paqueteId);
        if (!paquete) return;

        const naveId = paquete.nave_id;

        // Si el paquete está en otra nave, cambiar la nave seleccionada y recargar mapa
        if (naveId && naveId !== naveSeleccionadaId) {
            naveSeleccionadaId = naveId;
            const select = document.getElementById('select-nave-salida');
            if (select && select.value !== String(naveId)) {
                select.value = String(naveId);
            }
            await cargarMapaSalida();
        }

        const contenedor = document.getElementById('contenedor-mapa-salida');
        if (!contenedor) return;

        // Quitar highlight anterior
        contenedor.querySelectorAll('.loc-paquete--highlight').forEach(el => {
            el.classList.remove('loc-paquete--highlight');
        });

        const target = contenedor.querySelector(`.loc-paquete[data-paquete-id="${paqueteId}"]`);
        if (!target) {
            console.warn('No se encontró el paquete en el mapa para resaltar:', paqueteId);
            return;
        }

        target.classList.add('loc-paquete--highlight');

        const escenario = contenedor.querySelector('[data-mapa-canvas]');
        if (!escenario) return;

        // Centrar scroll del mapa alrededor del paquete
        const rect = target.getBoundingClientRect();
        const escRect = escenario.getBoundingClientRect();

        const offsetX = rect.left - escRect.left - escRect.width / 2 + rect.width / 2;
        const offsetY = rect.top - escRect.top - escRect.height / 2 + rect.height / 2;

        escenario.scrollBy({
            left: offsetX,
            top: offsetY,
            behavior: 'smooth',
        });
    }

    async function validarYRegistrarEtiqueta(codigo) {
        // Verificar si ya fue escaneada
        if (etiquetasEscaneadas.has(codigo)) {
            mostrarMensajeEscaneo('⚠️ Esta etiqueta ya fue escaneada', 'warning');
            return;
        }

        try {
            const response = await fetch('/salidas/validar-subetiqueta', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    codigo: codigo,
                    salida_id: salidaData.salidaId
                })
            });

            const data = await response.json();

            if (data.success) {
                // Registrar etiqueta escaneada
                etiquetasEscaneadas.add(codigo);

                // Verificar si el paquete está completo
                const paquete = paquetesSalida.find(p => p.id === data.paquete.id);
                if (paquete) {
                    const etiquetasEscaneadasPaquete = paquete.etiquetas.filter(e =>
                        etiquetasEscaneadas.has(e.codigo)
                    ).length;

                    if (etiquetasEscaneadasPaquete === paquete.num_etiquetas) {
                        paquetesCompletados.add(paquete.id);
                        mostrarMensajeEscaneo(`✓ Paquete ${paquete.codigo} completado`, 'success');
                    } else {
                        mostrarMensajeEscaneo(`✓ Etiqueta válida - ${data.paquete.codigo}`, 'success');
                    }
                }

                // Actualizar UI
                renderizarListaPaquetes();
                actualizarContadores();
                verificarSalidaCompleta();
            } else {
                mostrarMensajeEscaneo(`❌ ${data.message}`, 'error');
            }
        } catch (error) {
            console.error('Error al validar etiqueta:', error);
            mostrarMensajeEscaneo('❌ Error al validar la etiqueta', 'error');
        }
    }

    function mostrarMensajeEscaneo(mensaje, tipo) {
        const div = document.getElementById('mensaje-escaneo');
        if (!div) return;

        div.textContent = mensaje;
        div.className = `mt-2 p-2 rounded-lg text-sm ${
            tipo === 'success' ? 'bg-green-100 text-green-800' :
            tipo === 'warning' ? 'bg-yellow-100 text-yellow-800' :
            tipo === 'error' ? 'bg-red-100 text-red-800' :
            'bg-gray-100 text-gray-800'
        }`;
        div.classList.remove('hidden');

        setTimeout(() => {
            if (div) div.classList.add('hidden');
        }, 3000);
    }

    function actualizarContadores() {
        const totalEtiquetas = paquetesSalida.reduce((sum, p) => sum + p.num_etiquetas, 0);
        const contadorEscaneadas = document.getElementById('contador-escaneadas');
        const contadorTotal = document.getElementById('contador-total');

        if (contadorEscaneadas) contadorEscaneadas.textContent = etiquetasEscaneadas.size;
        if (contadorTotal) contadorTotal.textContent = totalEtiquetas;
    }

    function verificarSalidaCompleta() {
        const totalPaquetes = paquetesSalida.length;
        const completados = paquetesCompletados.size;
        const btnCompletar = document.getElementById('btn-completar-salida');
        const mensajeValidacion = document.getElementById('mensaje-validacion');

        if (!btnCompletar || !mensajeValidacion) return;

        if (completados === totalPaquetes && totalPaquetes > 0) {
            btnCompletar.disabled = false;
            mensajeValidacion.textContent = '✓ Todos los paquetes han sido escaneados correctamente';
            mensajeValidacion.className = 'text-sm text-green-600 font-medium';
            mensajeValidacion.classList.remove('hidden');
        } else {
            btnCompletar.disabled = true;
            mensajeValidacion.textContent = `Faltan ${totalPaquetes - completados} paquete(s) por escanear`;
            mensajeValidacion.className = 'text-sm text-gray-600';
            mensajeValidacion.classList.remove('hidden');
        }
    }

    async function completarSalida() {
        if (paquetesCompletados.size !== paquetesSalida.length) {
            alert('Debes escanear todas las etiquetas de todos los paquetes antes de completar la salida.');
            return;
        }

        if (!confirm('¿Confirmar que todos los paquetes han sido cargados y completar la salida?')) {
            return;
        }

        try {
            const response = await fetch(`/salidas/completar-desde-movimiento/${salidaData.movimientoId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (data.success) {
                alert('✓ Salida completada con éxito');
                cerrarModalEjecutarSalida();
                location.reload();
            } else {
                alert('Error al completar la salida: ' + data.message);
            }
        } catch (error) {
            console.error('Error al completar salida:', error);
            alert('Error al completar la salida');
        }
    }
</script>

{{-- ETQ2511012.01 --}}
