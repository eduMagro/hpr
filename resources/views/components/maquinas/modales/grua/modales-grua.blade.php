{{-- CSS para el componente de mapa (necesario para el modal de mover paquete) --}}
@once
    <link rel="stylesheet" href="{{ asset('css/localizaciones/styleLocIndex.css') }}">
@endonce

@php
    $mapaData = $mapaData ?? [];
    // Obtener el nave_id (obra_id) de la máquina si está disponible
    $naveIdMapa = $maquina->obra_id ?? 1;
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


            <!-- Ubicación destino (select de sector + ubicación) -->
            <div class="mt-4" id="ubicacion-destino-container">
                <!-- Select de Sector -->
                <label for="sector_destino" class="block text-sm font-medium text-gray-700 mb-1">
                    Sector <span class="text-red-500">*</span>
                </label>
                <select name="sector_destino" id="sector_destino"
                    class="w-full border border-gray-300 rounded text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 mb-3"
                    style="height:2cm; padding:0.75rem 1rem; font-size:1.5rem;">
                    @foreach ($sectores ?? [] as $sector)
                        <option value="{{ $sector }}" {{ $sector === ($sectorPorDefecto ?? '') ? 'selected' : '' }}>
                            {{ $sector }}
                        </option>
                    @endforeach
                </select>

                <!-- Select de Ubicación -->
                <label for="ubicacion_destino_select" class="block text-sm font-medium text-gray-700 mb-1">
                    Ubicación <span class="text-red-500">*</span>
                </label>
                <select name="ubicacion_destino" id="ubicacion_destino_select"
                    class="w-full border border-gray-300 rounded text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 mb-3"
                    style="height:2cm; padding:0.75rem 1rem; font-size:1.5rem;">
                    @php
                        $sectorInicial = $sectorPorDefecto ?? (count($sectores ?? []) > 0 ? $sectores[0] : null);
                        $ubicacionesIniciales = $sectorInicial && isset($ubicacionesPorSector[$sectorInicial])
                            ? $ubicacionesPorSector[$sectorInicial]
                            : collect();
                    @endphp
                    @foreach ($ubicacionesIniciales as $ubicacion)
                        <option value="{{ $ubicacion->id }}">{{ $ubicacion->nombre_sin_prefijo }}</option>
                    @endforeach
                </select>

                <!-- Checkbox para escanear ubicación -->
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" id="scan_ubicacion_checkbox" class="w-5 h-5 cursor-pointer">
                    <span>Escanear ubicación en su lugar</span>
                </label>

                <!-- Input de escaneo (oculto por defecto) -->
                <div id="scan_ubicacion_wrapper" class="hidden mt-2">
                    <x-tabla.input-movil name="ubicacion_destino_scan"
                        id="ubicacion_destino_scan"
                        label=""
                        placeholder="Escanea el código de ubicación"
                        autocomplete="off" />
                </div>
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
    // Datos de ubicaciones por sector para el modal de movimiento libre
    // Usar window para evitar errores de re-declaración con Livewire prefetch
    window.ubicacionesPorSectorGrua = @json($ubicacionesPorSector ?? []);

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

        // ===== Lógica para cambio de sector =====
        const sectorSelect = document.getElementById('sector_destino');
        const ubicacionSelect = document.getElementById('ubicacion_destino_select');

        if (sectorSelect && ubicacionSelect) {
            sectorSelect.addEventListener('change', function() {
                const sectorSeleccionado = this.value;
                const ubicacionesDelSector = window.ubicacionesPorSectorGrua[sectorSeleccionado] || [];

                // Limpiar y reconstruir opciones de ubicaciones
                ubicacionSelect.innerHTML = '';

                ubicacionesDelSector.forEach(ubicacion => {
                    const option = document.createElement('option');
                    option.value = ubicacion.id;
                    option.textContent = ubicacion.nombre_sin_prefijo;
                    ubicacionSelect.appendChild(option);
                });
            });
        }

        // ===== Lógica para checkbox de escanear ubicación =====
        const scanCheckbox = document.getElementById('scan_ubicacion_checkbox');
        const scanWrapper = document.getElementById('scan_ubicacion_wrapper');
        const scanInput = document.getElementById('ubicacion_destino_scan');

        if (scanCheckbox && scanWrapper && sectorSelect && ubicacionSelect) {
            scanCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    // Ocultar selects, mostrar input de escaneo
                    sectorSelect.style.display = 'none';
                    sectorSelect.previousElementSibling.style.display = 'none'; // label
                    ubicacionSelect.style.display = 'none';
                    ubicacionSelect.previousElementSibling.style.display = 'none'; // label
                    ubicacionSelect.removeAttribute('name'); // quitar name del select
                    scanWrapper.classList.remove('hidden');
                    if (scanInput) {
                        scanInput.setAttribute('name', 'ubicacion_destino');
                        scanInput.focus();
                    }
                } else {
                    // Mostrar selects, ocultar input de escaneo
                    sectorSelect.style.display = 'block';
                    sectorSelect.previousElementSibling.style.display = 'block';
                    ubicacionSelect.style.display = 'block';
                    ubicacionSelect.previousElementSibling.style.display = 'block';
                    ubicacionSelect.setAttribute('name', 'ubicacion_destino');
                    scanWrapper.classList.add('hidden');
                    if (scanInput) {
                        scanInput.removeAttribute('name');
                        scanInput.value = '';
                    }
                }
            });
        }
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

        const inputQR = document.getElementById("codigo_general_general");

        if (codigo) {
            const code = String(codigo).trim().toUpperCase();
            // Reintentar hasta que la función esté disponible (máximo 2 segundos)
            let intentos = 0;
            const maxIntentos = 20;
            const intervalo = setInterval(() => {
                intentos++;
                if (typeof window.agregarQRMovimientoLibre === 'function') {
                    clearInterval(intervalo);
                    window.agregarQRMovimientoLibre(code);
                    if (inputQR) inputQR.focus();
                } else if (intentos >= maxIntentos) {
                    clearInterval(intervalo);
                    console.warn('[abrirModalMovimientoLibre] Función agregarQRMovimientoLibre no disponible después de', maxIntentos * 100, 'ms');
                    // Fallback: poner el valor en el input para que el usuario presione Enter
                    if (inputQR) {
                        inputQR.value = code;
                        inputQR.focus();
                    }
                }
            }, 100);
        } else {
            setTimeout(() => {
                if (inputQR) inputQR.focus();
            }, 100);
        }
    }

    function cerrarModalMovimientoLibre() {
        const modal = document.getElementById('modalMovimientoLibre');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Mostrar/ocultar campos según tipo
    document.addEventListener('DOMContentLoaded', function() {
        const tipoSelect = document.getElementById('tipo');
        const productoSection = document.getElementById('producto-section');
        const paqueteSection = document.getElementById('paquete-section');

        if (tipoSelect && productoSection && paqueteSection) {
            tipoSelect.addEventListener('change', function() {
                if (this.value === 'producto') {
                    productoSection.classList.remove('hidden');
                    paqueteSection.classList.add('hidden');
                } else if (this.value === 'paquete') {
                    productoSection.classList.add('hidden');
                    paqueteSection.classList.remove('hidden');
                }
            });
        }
    });

    // Usar window para evitar errores de re-declaración con Livewire prefetch
    if (typeof window.paqueteEsperadoId === 'undefined') window.paqueteEsperadoId = null;

    function abrirModalBajadaPaquete(data) {
        document.getElementById('movimiento_id').value = data.id;
        document.getElementById('paquete_id').value = data.paquete_id;
        document.getElementById('ubicacion_origen').value = data
            .ubicacion_origen;
        document.getElementById('descripcion_paquete').innerText = data
            .descripcion;

        window.paqueteEsperadoId = data.paquete_id;
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

<script src="{{ asset('js/movimientos/movimientosgrua.js') }}?v={{ time() }}"></script>

{{-- 📦 MODAL MOVER PAQUETE (3 pasos: escanear, validar, ubicar en mapa) --}}
<div id="modal-mover-paquete"
    class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div
        class="bg-white rounded-t-2xl sm:rounded-2xl shadow-xl w-full max-w-6xl max-h-[85vh] sm:max-h-[90vh] overflow-hidden flex flex-col"
        data-modal-ajustable-grid="true">
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
            <div id="paso-escanear-paquete" class="flex-1 overflow-y-auto p-4 space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-800 mb-2">Paso 1:
                        Escanear Código</h3>
                    <p class="text-sm text-blue-600 mb-3">Escanea o introduce
                        el código de una etiqueta o directamente el código del paquete.</p>

                    <div class="flex flex-col gap-2">
                        <div class="flex-1">
                            <x-tabla.input-movil type="text"
                                id="codigo_paquete_mover" label="Código de Etiqueta o Paquete"
                                placeholder="Escanea código de etiqueta (ETQ...) o paquete (PAQ...)"
                                autocomplete="off" inputmode="text" />
                        </div>
                        <button onclick="buscarPaqueteParaMover()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow mb-[2px]">
                            Buscar
                        </button>
                    </div>

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
                    {{-- Mensaje de advertencia si no tiene localización --}}
                    <div id="warning-sin-localizacion" class="hidden mt-3 p-3 bg-blue-50 border border-blue-300 rounded-lg">
                        <p class="text-blue-800 text-sm font-medium">📍 Este paquete no tiene ubicación asignada en el mapa.</p>
                        <p class="text-blue-700 text-xs mt-1">Se mostrará un ghost para que puedas arrastrarlo a la ubicación deseada.</p>
                    </div>
                    <button onclick="mostrarPasoMapa()"
                        class="mt-4 w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg shadow">
                        Seleccionar Ubicación en Mapa
                    </button>
                </div>
            </div>

            {{-- PASO 2: Seleccionar ubicación en mapa --}}
            <div id="paso-mapa-paquete" class="hidden space-y-2 h-full">
                {{-- Información del paquete seleccionado --}}
                <div class="bg-green-50 border border-green-300 rounded-lg p-3 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">📦</span>
                        <div>
                            <p class="font-bold text-green-800 text-lg" id="paquete-codigo-mapa"></p>
                            <p class="text-sm text-green-600" id="paquete-info-mapa"></p>
                        </div>
                    </div>
                    <button onclick="volverPasoEscaneo()"
                        class="text-sm px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded-lg flex items-center gap-1">
                        ← Buscar otro
                    </button>
                </div>

                {{-- Componente de mapa simplificado --}}
                <div class="bg-white p-2 rounded-lg border flex-1 overflow-hidden relative" style="height: calc(100% - 70px);">
                    <x-mapa-simple :nave-id="$naveIdMapa" :modo-edicion="true" class="h-full w-full" />
                </div>
            </div>
        </div>

        {{-- Footer con botones --}}
        <div class="border-t p-4 bg-gray-50 flex justify-end gap-3">
            <button onclick="cerrarModalMoverPaquete()"
                class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded-lg font-medium">
                Cerrar
            </button>
        </div>
    </div>
</div>

{{-- Scripts para el modal de mover paquete --}}
<script>
    // Usar window para evitar errores de re-declaración con Livewire prefetch
    if (typeof window.paqueteMoverData === 'undefined') window.paqueteMoverData = null;
    function ajustarModalSegunGrid(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        const cont = modal.querySelector('[data-modal-ajustable-grid]');
        if (!cont) return;
        const grid = modal.querySelector('.cuadricula-mapa');
        if (!grid) return;

        let reintentos = 0;
        const ajustar = () => {
            const gridWidth = grid.scrollWidth || grid.offsetWidth;
            if (!gridWidth && reintentos < 10) {
                reintentos += 1;
                requestAnimationFrame(ajustar);
                return;
            }
            if (!gridWidth) return;
            const margen = 64;
            const anchoDeseado = Math.min(gridWidth + margen, window.innerWidth - 32);
            cont.style.width = `${Math.max(320, anchoDeseado)}px`;
        };

        requestAnimationFrame(ajustar);
    }

    function abrirModalMoverPaquete() {
        const modal = document.getElementById('modal-mover-paquete');
        modal.classList.remove('hidden');
        ajustarModalSegunGrid('modal-mover-paquete');

        setTimeout(() => {
            document.getElementById('codigo_paquete_mover')?.focus();
        }, 100);
    }

    function cerrarModalMoverPaquete() {
        document.getElementById('modal-mover-paquete').classList.add('hidden');
        const cont = document.getElementById('modal-mover-paquete')?.querySelector('[data-modal-ajustable-grid]');
        if (cont) cont.style.width = '';
        resetearModalMoverPaquete();
    }

    function resetearModalMoverPaquete() {
        const inputCodigo = document.getElementById('codigo_paquete_mover');
        if (inputCodigo) inputCodigo.value = '';

        document.getElementById('info-paquete-validado')?.classList.add('hidden');
        document.getElementById('paso-mapa-paquete')?.classList.add('hidden');
        document.getElementById('paso-escanear-paquete')?.classList.remove('hidden');
        document.getElementById('error-paquete-mover')?.classList.add('hidden');
        document.getElementById('loading-paquete-mover')?.classList.add('hidden');
        document.getElementById('warning-sin-localizacion')?.classList.add('hidden');

        // Cancelar ghost si existe
        const modal = document.getElementById('modal-mover-paquete');
        if (modal) {
            const mapa = modal.querySelector('[data-mapa-simple]');
            if (mapa && typeof mapa.cancelarGhost === 'function') {
                mapa.cancelarGhost();
            }
        }

        window.paqueteMoverData = null;
    }

    function mostrarPasoMapa() {
        document.getElementById('paso-escanear-paquete').classList.add('hidden');
        document.getElementById('paso-mapa-paquete').classList.remove('hidden');

        const codigoPak = (window.paqueteMoverData?.codigo || '').toString().trim();
        const etiquetasCount = window.paqueteMoverData?.etiquetas_count || 0;
        const elementosCount = window.paqueteMoverData?.elementos_count || 0;
        const tieneLocalizacion = window.paqueteMoverData?.tiene_localizacion;

        document.getElementById('paquete-codigo-mapa').textContent = codigoPak;
        document.getElementById('paquete-info-mapa').textContent =
            `${etiquetasCount} etiquetas · ${elementosCount} elementos` +
            (tieneLocalizacion ? '' : ' · Sin ubicación asignada');

        if (codigoPak && tieneLocalizacion) {
            // Paquete con localización: mostrar en el mapa
            mostrarPaqueteEnMapaModal('modal-mover-paquete', codigoPak);
        } else if (codigoPak && !tieneLocalizacion) {
            // Paquete SIN localización: crear ghost para asignar ubicación
            crearGhostEnMapaModal('modal-mover-paquete', window.paqueteMoverData);
        }
    }

    function volverPasoEscaneo() {
        document.getElementById('paso-mapa-paquete').classList.add('hidden');
        document.getElementById('paso-escanear-paquete').classList.remove('hidden');

        // Limpiar input y dar focus
        const input = document.getElementById('codigo_paquete_mover');
        if (input) {
            input.value = '';
            setTimeout(() => input.focus(), 100);
        }

        // Cancelar ghost si existe
        const modal = document.getElementById('modal-mover-paquete');
        if (modal) {
            const mapa = modal.querySelector('[data-mapa-simple]');
            if (mapa && typeof mapa.cancelarGhost === 'function') {
                mapa.cancelarGhost();
            }
        }

        window.paqueteMoverData = null;
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
            mostrarErrorPaquete('Debes introducir un código de paquete o etiqueta.');
            return;
        }

        document.getElementById('loading-paquete-mover').classList.remove(
            'hidden');
        document.getElementById('error-paquete-mover').classList.add(
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

            window.paqueteMoverData = data;

            // Ir directamente al mapa
            document.getElementById('loading-paquete-mover').classList.add('hidden');
            mostrarPasoMapa();

        } catch (error) {
            console.error('Error al buscar paquete:', error);
            mostrarErrorPaquete(error.message ||
                'No se encontró el paquete. Verifica el código.');
            document.getElementById('loading-paquete-mover').classList.add(
                'hidden');
        }
    }

    function mostrarErrorPaquete(mensaje) {
        const errorDiv = document.getElementById('error-paquete-mover');
        errorDiv.textContent = mensaje;
        errorDiv.classList.remove('hidden');
    }

    // Mostrar paquete en el mapa-simple que está dentro de un modal concreto
    function mostrarPaqueteEnMapaModal(modalId, codigoPaquete) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.warn('Modal no encontrado:', modalId);
            return;
        }
        const mapa = modal.querySelector('[data-mapa-simple]');
        if (!mapa) {
            console.warn('Componente mapa-simple no encontrado en modal:', modalId);
            return;
        }

        let intentos = 0;
        const maxIntentos = 40;
        const intentar = () => {
            intentos += 1;
            const fnMostrar = mapa.mostrarPaquete;
            const fnAutoclick = mapa.autoclickEditarPaquete;
            if (typeof fnMostrar === 'function') {
                try {
                    const resultado = fnMostrar(codigoPaquete);
                    if (resultado === false) {
                        // El paquete no existe en el DOM del mapa
                        console.warn('Paquete no encontrado en el mapa:', codigoPaquete);
                        mostrarMensajeEnMapa(modal, 'El paquete no tiene ubicación registrada en este mapa. Puedes asignarle una nueva ubicación.', 'warning');
                    } else {
                        if (typeof fnAutoclick === 'function') {
                            // Asegurar flujo de clic tras mostrar y centrar
                            setTimeout(() => fnAutoclick(codigoPaquete), 200);
                        }
                    }
                } catch (e) {
                    console.warn('No se pudo mostrar paquete en el mapa:', e);
                }
                return;
            }
            if (intentos < maxIntentos) {
                setTimeout(intentar, 200);
            } else {
                console.warn('Timeout esperando funciones del mapa');
            }
        };
        intentar();
    }

    // Mostrar mensaje flotante en el área del mapa
    function mostrarMensajeEnMapa(modal, mensaje, tipo = 'info') {
        const contenedorMapa = modal.querySelector('#paso-mapa-paquete .bg-white');
        if (!contenedorMapa) return;

        // Remover mensaje anterior si existe
        const msgAnterior = contenedorMapa.querySelector('.mensaje-mapa-flotante');
        if (msgAnterior) msgAnterior.remove();

        const colores = {
            'info': 'bg-blue-100 text-blue-800 border-blue-300',
            'warning': 'bg-yellow-100 text-yellow-800 border-yellow-300',
            'error': 'bg-red-100 text-red-800 border-red-300',
            'success': 'bg-green-100 text-green-800 border-green-300'
        };

        const div = document.createElement('div');
        div.className = `mensaje-mapa-flotante absolute top-2 left-2 right-2 z-50 p-3 rounded-lg border text-sm ${colores[tipo] || colores.info}`;
        div.innerHTML = `<p>${mensaje}</p>`;
        contenedorMapa.appendChild(div);

        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            if (div.parentNode) div.remove();
        }, 5000);
    }

    // Crear ghost en el mapa-simple para paquetes sin localización
    function crearGhostEnMapaModal(modalId, paqueteData) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.warn('[crearGhostEnMapaModal] Modal no encontrado:', modalId);
            return;
        }
        const mapa = modal.querySelector('[data-mapa-simple]');
        if (!mapa) {
            console.warn('[crearGhostEnMapaModal] Componente mapa-simple no encontrado en modal:', modalId);
            return;
        }

        console.log('[crearGhostEnMapaModal] Intentando crear ghost para paquete:', paqueteData.codigo);

        let intentos = 0;
        const maxIntentos = 50; // 50 * 200ms = 10 segundos máximo
        const intentar = () => {
            intentos += 1;

            // Verificar si el ghost está listo (el mapa terminó de cargar)
            const isGhostReady = typeof mapa.isGhostReady === 'function' && mapa.isGhostReady();
            const fnCrearGhost = mapa.crearGhostPaquete;

            if (isGhostReady && typeof fnCrearGhost === 'function') {
                console.log('[crearGhostEnMapaModal] Ghost listo, creando... (intento', intentos, ')');
                try {
                    const resultado = fnCrearGhost(paqueteData);
                    if (resultado) {
                        console.log('[crearGhostEnMapaModal] Ghost creado exitosamente');
                    } else {
                        console.warn('[crearGhostEnMapaModal] No se pudo crear el ghost (resultado false)');
                    }
                } catch (e) {
                    console.error('[crearGhostEnMapaModal] Error al crear ghost:', e);
                }
                return;
            }

            if (intentos < maxIntentos) {
                if (intentos % 10 === 0) {
                    console.log('[crearGhostEnMapaModal] Esperando ghost... intento', intentos,
                        '- isGhostReady:', isGhostReady,
                        '- fnCrearGhost:', typeof fnCrearGhost);
                }
                setTimeout(intentar, 200);
            } else {
                console.warn('[crearGhostEnMapaModal] Timeout esperando ghost después de', maxIntentos, 'intentos');
            }
        };
        intentar();
    }
</script>

{{-- 🚛 MODAL EJECUTAR SALIDA (con mapa y escaneo de paquetes) --}}
<div id="modal-ejecutar-salida"
    class="fixed inset-x-0 top-14 bottom-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white sm:rounded-2xl shadow-xl w-full h-full sm:w-[90vw] sm:max-h-[calc(100vh-5rem)] overflow-hidden flex flex-col">
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
                        <p class="text-gray-600">Paquetes validados: <span id="contador-escaneadas" class="font-bold text-purple-600">0</span></p>
                        <p class="text-gray-600">Total paquetes: <span id="contador-total" class="font-bold">0</span></p>
                    </div>
                </div>
            </div>
            <div id="mensaje-escaneo" class="hidden mt-2 p-2 rounded-lg text-sm"></div>
        </div>

        {{-- Contenido principal: Mapa + Lista --}}
        <div class="flex-1 overflow-hidden flex flex-col lg:flex-row relative">
            {{-- MAPA (Izquierda) --}}
            <div id="contenedor-mapa-ejecutar-salida" class="hidden lg:block w-full lg:flex-1 h-full p-4 overflow-hidden bg-white absolute inset-0 lg:static z-20 lg:z-auto">
                <button onclick="ocultarMapaMovil()" 
                    class="lg:hidden absolute top-5 right-5 bg-white text-gray-800 px-4 py-2 rounded-full shadow-lg border z-50 font-bold flex items-center gap-2 hover:bg-gray-50">
                    <span>✕</span> Volver
                </button>
                <x-mapa-simple :nave-id="1" :modo-edicion="false" class="h-full w-full" />
            </div>

            {{-- LISTA DE PAQUETES (Derecha) --}}
            <div id="contenedor-lista-paquetes" class="w-full lg:w-96 border-t lg:border-t-0 lg:border-l bg-gray-50 flex flex-col h-full overflow-hidden">
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
    // Usar window para evitar errores de re-declaración con Livewire prefetch
    if (typeof window.salidaData === 'undefined') window.salidaData = null;
    let paquetesSalida = [];
    let etiquetasEscaneadas = new Set();
    let paquetesLocalizados = new Set();
    let paqueteSeleccionadoId = null;

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
        paquetesLocalizados.clear();
        paqueteSeleccionadoId = null;
        paquetesSalida = [];
        window.salidaData = {
            movimientoId: movimientoId,
            salidaId: salidaId
        };

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

        if (inputCodigo) inputCodigo.value = '';
        if (listaPaquetes) listaPaquetes.innerHTML = '';

        etiquetasEscaneadas.clear();
        paquetesLocalizados.clear();
        paqueteSeleccionadoId = null;
        paquetesSalida = [];
        window.salidaData = null;
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

            window.salidaData = {
                ...window.salidaData,
                ...data.salida
            };
            paquetesSalida = data.paquetes || [];

            console.log('Paquetes cargados:', paquetesSalida.length);

            // Actualizar header
            const headerElement = document.getElementById('salida-codigo-header');
            if (headerElement) {
                headerElement.textContent = `Salida: ${data.salida.codigo_salida}`;
            }

            // Renderizar lista de paquetes
            renderizarListaPaquetes();

            // Actualizar contadores
            actualizarContadores();

            // Recargar mapa con el salidaId actual
            const contenedorMapa = document.getElementById('contenedor-mapa-ejecutar-salida');
            if (contenedorMapa) {
                const mapaComponent = contenedorMapa.querySelector('[data-mapa-simple]');
                if (mapaComponent && typeof mapaComponent.recargarMapa === 'function') {
                    mapaComponent.recargarMapa(salidaId);
                    console.log('Mapa recargado con salida:', salidaId);
                }
            }

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
            const isLocalizado = paquetesLocalizados.has(paquete.id);

            const div = document.createElement('div');
            const isSeleccionado = paqueteSeleccionadoId === paquete.id;
            let claseEstado = 'bg-white border-gray-200 hover:border-purple-300';
            if (isLocalizado) claseEstado = 'bg-purple-50 border-purple-500 shadow-[0_0_10px_rgba(126,34,206,0.2)]';
            if (isSeleccionado) claseEstado = 'bg-purple-50 border-purple-500 shadow-[0_0_10px_rgba(126,34,206,0.2)]';

            div.className = `p-3 rounded-lg border-2 cursor-pointer transition-all ${claseEstado}`;
            // Click en paquete → mostrar/centrar y ocultar otros en el mapa, marcar selección
            div.addEventListener('click', () => {
                const mapa = document.querySelector('#contenedor-mapa-ejecutar-salida [data-mapa-simple]');
                if (mapa) {
                    const grid = mapa.querySelector('.cuadricula-mapa');
                    if (grid) grid.querySelectorAll('.loc-paquete').forEach(p => p.style.display = 'none');
                    if (typeof mapa.mostrarPaquete === 'function') {
                        mapa.mostrarPaquete(paquete.codigo, false);
                    }
                }
                paqueteSeleccionadoId = paquete.id;
                renderizarListaPaquetes();
                mostrarMapaMovil();
            });

            div.innerHTML = `
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 inline-block rounded-full ${isLocalizado ? 'bg-purple-500 shadow-[0_0_10px_rgba(126,34,206,0.45)]' : 'bg-gray-300'}"></span>
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
                        <p class="text-xs font-medium ${isLocalizado ? 'text-purple-600' : 'text-gray-500'}">
                            ${paquete.num_etiquetas} etiquetas
                        </p>
                    </div>
                </div>
            `;

            lista.appendChild(div);
        });
    }

    // Funciones de manipulación del mapa eliminadas - el componente es auto-contenido

    async function validarYRegistrarEtiqueta(codigo) {
        // Verificar si ya fue escaneada
        if (etiquetasEscaneadas.has(codigo)) {
            mostrarMensajeEscaneo('⚠️ Esta etiqueta ya fue escaneada', 'warning');
            return;
        }

        const norm = (v) => (v ?? '').toString().trim().toLowerCase();
        const codigoNorm = norm(codigo);

        // Buscar si la subetiqueta pertenece a algún paquete de la salida usando etiqueta_sub_id como clave principal
        console.log('🔍 Buscando subetiqueta:', codigoNorm);
        const paquete = paquetesSalida.find(p => p.etiquetas.some(e => {
            const sub = norm(e.etiqueta_sub_id);
            // console.log('   Comparando con:', sub);
            return sub && sub === codigoNorm;
        }));

        if (!paquete) {
            mostrarMensajeEscaneo('❌ Esta subetiqueta no pertenece a ningún paquete de la salida', 'error');
            return;
        }

        // Registrar escaneo y marcar paquete localizado
        etiquetasEscaneadas.add(codigo);
        paquetesLocalizados.add(paquete.id);
        paqueteSeleccionadoId = paquete.id;
        mostrarMensajeEscaneo(`✓ Paquete ${paquete.codigo} validado`, 'success');

        // Mostrar paquete en el mapa y ocultar el resto
        const mapa = document.querySelector('#contenedor-mapa-ejecutar-salida [data-mapa-simple]');
        if (mapa) {
            const grid = mapa.querySelector('.cuadricula-mapa');
            if (grid) grid.querySelectorAll('.loc-paquete').forEach(p => p.style.display = 'none');
            if (typeof mapa.mostrarPaquete === 'function') {
                mapa.mostrarPaquete(paquete.codigo, false);
            }
        }

        renderizarListaPaquetes();
        actualizarContadores();
        verificarSalidaCompleta();
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
        const totalPaquetes = paquetesSalida.length;
        const contadorEscaneadas = document.getElementById('contador-escaneadas');
        const contadorTotal = document.getElementById('contador-total');

        if (contadorEscaneadas) contadorEscaneadas.textContent = paquetesLocalizados.size;
        if (contadorTotal) contadorTotal.textContent = totalPaquetes;
    }

    function verificarSalidaCompleta() {
        const totalPaquetes = paquetesSalida.length;
        const completados = paquetesLocalizados.size;
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
        if (paquetesLocalizados.size !== paquetesSalida.length) {
            alert('Debes escanear al menos una subetiqueta de cada paquete antes de completar la salida.');
            return;
        }

        try {
            const response = await fetch(`/salidas/completar-desde-movimiento/${window.salidaData.movimientoId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (data.success) {
                mostrarToastSwal('Salida completada con éxito', 'success');
                cerrarModalEjecutarSalida();
            } else {
                mostrarToastSwal('Error al completar la salida: ' + (data.message || 'Respuesta no válida'), 'error');
            }
        } catch (error) {
            console.error('Error al completar salida:', error);
            mostrarToastSwal('Error al completar la salida', 'error');
        }
    }

    function mostrarToastSwal(mensaje, icono = 'info') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                timer: 3000,
                timerProgressBar: true,
                icon: icono,
                title: mensaje,
                showConfirmButton: false,
            });
        } else {
            alert(mensaje);
        }
    }
    function mostrarMapaMovil() {
        const mapa = document.getElementById('contenedor-mapa-ejecutar-salida');
        if (mapa) {
            mapa.classList.remove('hidden');
            // Disparar evento de resize para que el mapa se ajuste si estaba oculto
            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));
            }, 100);
        }
    }

    function ocultarMapaMovil() {
        const mapa = document.getElementById('contenedor-mapa-ejecutar-salida');
        if (mapa) {
            mapa.classList.add('hidden');
        }
    }
</script>

{{-- ETQ2511012.01 --}}
