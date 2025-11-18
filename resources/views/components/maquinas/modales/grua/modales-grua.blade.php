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
                                :ruta-guardar="route('localizaciones.storePaquete')" :modo-modal="false"
                                height="100%" class="w-full h-[420px]" />
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

        resetearModalMoverPaquete();

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

    // ocultar aquellos paquetes que no sean el actual
    // agregar opción de modificar sus coordenadas
    function resaltarPaqueteSeleccionado(paqueteActual) {
        console.log("ocultando paquetes")
        const paquetes = document.querySelectorAll('.loc-paquete');
        paquetes.forEach(paquete => {
            if (paquete.dataset.codigo != paqueteActual) {
                paquete.classList.add('loc-paquete', 'loc-existente');
            } else {
                paquete.classList.remove('loc-paquete',
                'loc-existente');
            }
        });
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
        resaltarPaqueteSeleccionado(idPaquete);
        await mostrarGhostParaPaquete();
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
            mostrarGhostParaPaquete();

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

    function esperarInstanciaMapaComponent(mapaId, timeout = 5000) {
        return new Promise((resolve, reject) => {
            const inicio = Date.now();

            function revisar() {
                const registro = window.mapaComponentInstances || {};
                const instancia = registro[mapaId];
                if (instancia) {
                    resolve(instancia);
                    return;
                }

                if (Date.now() - inicio >= timeout) {
                    reject(new Error(
                        'Instancia del mapa no disponible'));
                    return;
                }

                setTimeout(revisar, 120);
            }

            revisar();
        });
    }

    async function inicializarMapaModal() {
        if (!MAPA_MODAL_ID) return null;
        if (mapaModalApi) return mapaModalApi;

        try {
            const api = await esperarInstanciaMapaComponent(MAPA_MODAL_ID);
            mapaModalApi = api;

            if (mapaModalUnsubscribe) {
                mapaModalUnsubscribe();
            }

            mapaModalUnsubscribe = api.onGhostMove(coords => {
                actualizarCoordenadas(coords.x1, coords.y1, coords
                    .x2, coords.y2);
                document.getElementById('coordenadas-seleccionadas')
                    .classList.remove('hidden');
            });
            api.hideAllPaquetes?.();

            return api;
        } catch (error) {
            console.error('No se pudo inicializar el mapa del modal:',
                error);
            return null;
        }
    }

    async function mostrarGhostParaPaquete() {
        if (!paqueteMoverData || !MAPA_MODAL_ID) return;

        const api = await inicializarMapaModal();
        if (!api) return;

        api.triggerGhost({
            codigo: paqueteMoverData.codigo,
            paquete_id: paqueteMoverData.paquete_id,
            longitud: paqueteMoverData.longitud || 0,
            ancho: paqueteMoverData.ancho || 1,
        });
        api.hideAllPaquetes?.();
        if (paqueteMoverData.paquete_id) {
            api.showPaquete?.(paqueteMoverData.paquete_id);
        }
    }

    function actualizarCoordenadas(x1, y1, x2, y2) {
        coordenadasPaquete = {
            x1,
            y1,
            x2,
            y2
        };
        document.getElementById('coord-x1').value = x1;
        document.getElementById('coord-y1').value = y1;
        document.getElementById('coord-x2').value = x2;
        document.getElementById('coord-y2').value = y2;
    }

    // Las acciones de guardar/cancelar ahora se manejan con los botones del ghost en mapa-component
</script>
