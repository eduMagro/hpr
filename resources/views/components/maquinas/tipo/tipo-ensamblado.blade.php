<style>
    [x-cloak] {
        display: none !important
    }
</style>

@props(['maquina', 'planillasActivas', 'ordenesEnsamblaje' => collect(), 'entidadesActivas' => collect(), 'elementosPorDiametro' => collect(), 'etiquetasPorEntidad' => collect()])

<div x-data="{
    showLeft: JSON.parse(localStorage.getItem('showLeftEns') ?? 'true'),
    showRight: JSON.parse(localStorage.getItem('showRightEns') ?? 'true'),

    toggleLeft() {
        this.showLeft = !this.showLeft;
        localStorage.setItem('showLeftEns', JSON.stringify(this.showLeft));
    },

    toggleRight() {
        this.showRight = !this.showRight;
        localStorage.setItem('showRightEns', JSON.stringify(this.showRight));
    }
}" class="w-full">

    <!-- GRID PRINCIPAL (3 COLUMNAS) -->
    <div class="max-w-screen-2xl mx-auto px-4">
        <div id="grid-ensamblado" class="grid grid-cols-12 gap-3">

            <!-- COLUMNA IZQUIERDA - ELEMENTOS FABRICADOS -->
            <div x-show="showLeft" x-cloak
                class="col-span-12 lg:col-span-2 bg-white border border-gray-200 shadow-lg rounded-lg self-start lg:sticky lg:top-2 overflow-hidden">

                <div class="p-3 bg-gradient-to-r from-amber-500 to-amber-600 text-white">
                    <h3 class="font-bold text-sm flex items-center gap-2">
                        <span>🔧</span> Elementos Fabricados
                    </h3>
                    <p class="text-xs text-amber-100 mt-1">Listos para ensamblar</p>
                </div>

                <div class="p-2 overflow-y-auto" style="max-height: calc(100vh - 150px);">
                    @if($elementosPorDiametro->isEmpty())
                        <div class="text-center text-gray-500 py-4">
                            <div class="text-3xl mb-2">📭</div>
                            <p class="text-xs">No hay elementos fabricados</p>
                        </div>
                    @else
                        @foreach ($elementosPorDiametro as $diametro => $elementos)
                            <div class="mb-2 p-2 bg-gray-50 rounded border border-gray-200">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="bg-green-600 text-white px-2 py-0.5 rounded text-xs font-bold">
                                        Ø{{ $diametro }}
                                    </span>
                                    <span class="text-xs text-gray-600 font-medium">
                                        {{ $elementos->count() }} uds.
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    Peso: {{ number_format($elementos->sum('peso'), 1, ',', '.') }} kg
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="p-3 border-t bg-gray-50">
                    <a href="{{ route('ensamblaje.planificacion', ['maquina_id' => $maquina->id]) }}"
                       class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition flex items-center justify-center gap-2">
                        📋 Planificación
                    </a>
                </div>
            </div>

            <!-- COLUMNA CENTRAL - ETIQUETAS DE ENSAMBLAJE -->
            <div class="bg-white border border-gray-200 shadow-lg rounded-lg overflow-hidden"
                :class="{
                    'col-span-12 lg:col-span-8': showLeft && showRight,
                    'col-span-12 lg:col-span-10': (showLeft && !showRight) || (!showLeft && showRight),
                    'col-span-12': !showLeft && !showRight
                }">

                @php
                    // Recopilar todas las etiquetas de ensamblaje de las órdenes
                    $todasEtiquetas = collect();
                    foreach ($ordenesEnsamblaje as $orden) {
                        if ($orden->entidad && $orden->entidad->etiquetasEnsamblaje) {
                            foreach ($orden->entidad->etiquetasEnsamblaje as $etiqueta) {
                                // Solo pendientes y en proceso
                                if (in_array($etiqueta->estado, ['pendiente', 'en_proceso'])) {
                                    // Cargar elementos si no están cargados
                                    if (!$etiqueta->relationLoaded('elementos')) {
                                        $etiqueta->load('elementos');
                                    }
                                    $todasEtiquetas->push($etiqueta);
                                }
                            }
                        }
                    }
                    $totalEtiquetas = $todasEtiquetas->count();
                    $pendientes = $todasEtiquetas->where('estado', 'pendiente')->count();
                    $enProceso = $todasEtiquetas->where('estado', 'en_proceso')->count();
                @endphp

                <div class="p-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-base flex items-center gap-2">
                            <span>🏗️</span> Cola de Ensamblaje
                        </h3>
                        <p class="text-xs text-blue-100 mt-1">Etiquetas listas para ensamblar</p>
                    </div>
                    <div class="flex gap-2 text-xs">
                        <span class="bg-blue-500 px-2 py-1 rounded">{{ $totalEtiquetas }} etiquetas</span>
                        <span class="bg-gray-200 text-gray-800 px-2 py-1 rounded">{{ $pendientes }} pendientes</span>
                        <span class="bg-yellow-200 text-yellow-800 px-2 py-1 rounded">{{ $enProceso }} en proceso</span>
                    </div>
                </div>

                <div class="p-4 overflow-y-auto" style="min-height: calc(100vh - 200px); max-height: calc(100vh - 150px);">
                    @if($todasEtiquetas->isEmpty())
                        <div class="text-center py-12 text-gray-500">
                            <div class="text-6xl mb-4">📭</div>
                            <h4 class="text-lg font-bold text-gray-700 mb-2">Cola vacía</h4>
                            <p class="text-sm mb-4">No hay etiquetas en la cola de ensamblaje</p>
                            <a href="{{ route('ensamblaje.planificacion', ['maquina_id' => $maquina->id]) }}"
                               class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                                📋 Ir a Planificación
                            </a>
                        </div>
                    @else
                        {{-- Lista de etiquetas usando el mismo componente que planillas show --}}
                        <div class="space-y-4">
                            @foreach($todasEtiquetas as $etiqueta)
                                @php
                                    $planilla = $etiqueta->planilla ?? $etiqueta->entidad?->planilla;
                                @endphp
                                @if($planilla)
                                    <x-entidad.ensamblaje :etiqueta="$etiqueta" :planilla="$planilla" :maquinaId="$maquina->id" />
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- COLUMNA DERECHA - COMPLETADAS -->
            <div x-show="showRight" x-cloak
                class="col-span-12 lg:col-span-2 bg-white border border-gray-200 shadow-lg rounded-lg self-start lg:sticky lg:top-2 overflow-hidden">

                <div class="p-3 bg-gradient-to-r from-green-500 to-green-600 text-white">
                    <h3 class="font-bold text-sm flex items-center gap-2">
                        <span>✅</span> Completadas Hoy
                    </h3>
                    <p class="text-xs text-green-100 mt-1">Ensamblajes finalizados</p>
                </div>

                <div class="p-2 overflow-y-auto" style="max-height: calc(100vh - 150px);">
                    @php
                        // Obtener IDs de entidades de órdenes completadas hoy en esta máquina
                        $ordenesCompletadasHoy = \App\Models\OrdenPlanillaEnsamblaje::where('maquina_id', $maquina->id)
                            ->where('estado', 'completada')
                            ->whereDate('fecha_fin', today())
                            ->pluck('planilla_entidad_id');

                        $completadasHoy = \App\Models\EtiquetaEnsamblaje::with(['entidad.elementos', 'planilla', 'elementos'])
                            ->whereIn('planilla_entidad_id', $ordenesCompletadasHoy)
                            ->where('estado', 'completada')
                            ->orderByDesc('fecha_fin')
                            ->limit(20)
                            ->get();
                    @endphp

                    @if($completadasHoy->isEmpty())
                        <div class="text-center text-gray-500 py-4">
                            <div class="text-3xl mb-2">📦</div>
                            <p class="text-xs">Sin ensamblajes completados hoy</p>
                        </div>
                    @else
                        @foreach($completadasHoy as $etiquetaComp)
                            @php
                                // Obtener elementos de la etiqueta completada
                                $elementosComp = $etiquetaComp->elementos ?? collect();
                                if ($elementosComp->isEmpty() && $etiquetaComp->entidad) {
                                    $elementosComp = $etiquetaComp->entidad->elementos ?? collect();
                                }
                            @endphp
                            <div class="mb-2 p-2 bg-green-50 rounded border border-green-200" x-data="{ expanded: false }">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="font-bold text-green-800 text-sm truncate">
                                            {{ $etiquetaComp->marca ?? $etiquetaComp->entidad->marca ?? 'N/A' }}
                                        </div>
                                        <div class="text-xs text-green-600">
                                            {{ $etiquetaComp->numero_unidad }}/{{ $etiquetaComp->total_unidades }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ $etiquetaComp->fecha_fin?->format('H:i') }}
                                        </div>
                                    </div>
                                    @if($elementosComp->isNotEmpty())
                                        <button @click="expanded = !expanded" class="text-green-600 hover:text-green-800 p-1">
                                            <svg class="w-4 h-4 transform transition-transform" :class="{ 'rotate-180': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>

                                {{-- Lista de elementos --}}
                                @if($elementosComp->isNotEmpty())
                                    <div x-show="expanded" x-collapse class="mt-2 pt-2 border-t border-green-200">
                                        <div class="text-xs text-green-700 font-medium mb-1">
                                            Elementos ({{ $elementosComp->count() }}):
                                        </div>
                                        <div class="space-y-1 max-h-32 overflow-y-auto">
                                            @foreach($elementosComp as $elem)
                                                <div class="text-xs bg-white p-1 rounded border border-green-100">
                                                    <span class="font-medium">{{ $elem->etiqueta_sub_id ?? $elem->id }}</span>
                                                    <span class="text-gray-500">
                                                        Ø{{ $elem->diametro ?? '-' }} | {{ number_format($elem->peso ?? 0, 2) }}kg
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function seleccionarEtiqueta(etiquetaId) {
        console.log('Etiqueta seleccionada:', etiquetaId);
    }

    function imprimirEtiquetasEntidad(entidadId) {
        window.open('/planilla-entidades/' + entidadId + '/imprimir-etiquetas', '_blank');
    }

    /**
     * Imprime una etiqueta de ensamblaje individual
     */
    function imprimirEntidad(safeId) {
        const wrapper = document.getElementById(safeId);
        if (!wrapper) {
            console.error('No se encontró el elemento:', safeId);
            return;
        }

        const card = wrapper.querySelector('.entidad-card');
        if (!card) {
            console.error('No se encontró la tarjeta de etiqueta');
            return;
        }

        // Clonar el contenido para impresión
        const contenido = card.cloneNode(true);

        // Eliminar botones (clase no-print)
        contenido.querySelectorAll('.no-print').forEach(el => el.remove());

        // Crear ventana de impresión
        const ventana = window.open('', '_blank', 'width=800,height=600');

        ventana.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Imprimir Etiqueta Ensamblaje</title>
                <style>
                    @page {
                        size: A6 landscape;
                        margin: 0;
                    }
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }
                    body {
                        font-family: Arial, sans-serif;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        min-height: 100vh;
                        background: #fff;
                    }
                    .entidad-card {
                        width: 148mm;
                        height: 105mm;
                        border: 0.5mm solid #000;
                        padding: 3mm;
                        background: #fff !important;
                        display: flex;
                        flex-direction: column;
                        position: relative;
                    }
                    .qr-box {
                        position: absolute;
                        top: 3mm;
                        right: 3mm;
                        border: 0.2mm solid #000;
                        padding: 1mm;
                        background: #fff;
                        width: 20mm;
                    }
                    .qr-box img, .qr-box canvas {
                        width: 18mm !important;
                        height: 18mm !important;
                    }
                    .qr-label {
                        font-size: 7pt;
                        text-align: center;
                        font-weight: bold;
                    }
                    svg {
                        background: #fff !important;
                    }
                    h2, h3 {
                        margin: 0;
                        padding: 0;
                    }
                    h2 { font-size: 11pt; }
                    h3 { font-size: 10pt; }
                    @media print {
                        body {
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }
                    }
                </style>
            </head>
            <body>
                ${contenido.outerHTML}
            </body>
            </html>
        `);

        ventana.document.close();

        // Esperar a que cargue y luego imprimir
        ventana.onload = function() {
            setTimeout(() => {
                ventana.print();
            }, 250);
        };
    }

    /**
     * Función principal para ensamblar una etiqueta.
     * Flujo: pendiente -> en_proceso -> completada
     */
    async function ensamblarEtiqueta(etiquetaId, maquinaId) {
        const btn = document.getElementById('btn-ensamblar-' + etiquetaId);
        if (!btn) return;

        const estadoActual = btn.dataset.estado;

        // Si ya está completada, no hacer nada
        if (estadoActual === 'completada') {
            return;
        }

        // Mensaje de confirmación según el estado
        let mensaje = estadoActual === 'pendiente'
            ? '¿Iniciar el ensamblaje de esta etiqueta?'
            : '¿Marcar como completado el ensamblaje?';

        if (!confirm(mensaje)) return;

        // Deshabilitar botón mientras procesa
        btn.disabled = true;
        const textoOriginal = btn.querySelector('span').textContent;
        btn.querySelector('span').textContent = 'Procesando...';

        try {
            const response = await fetch('/etiquetas-ensamblaje/' + etiquetaId + '/estado', {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    maquina_id: maquinaId
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Actualizar el botón según el nuevo estado
                const nuevoEstado = data.estado;
                btn.dataset.estado = nuevoEstado;

                // Obtener la tarjeta para cambiar su color
                const wrapper = document.getElementById('etiqueta-ens-' + etiquetaId);
                const card = wrapper ? wrapper.querySelector('.entidad-card') : null;

                if (nuevoEstado === 'en_proceso') {
                    btn.querySelector('span').textContent = 'COMPLETAR';
                    btn.classList.remove('bg-green-600', 'hover:bg-green-700');
                    btn.classList.add('bg-yellow-500', 'hover:bg-yellow-600');
                    btn.disabled = false;

                    // Cambiar color de la tarjeta a amarillo (ensamblando) - igual que etiquetas normales
                    if (card) {
                        // Limpiar clases de estado anteriores
                        card.className = card.className
                            .split(' ')
                            .filter(c => !c.startsWith('estado-'))
                            .join(' ');
                        card.classList.add('estado-ensamblando');
                        card.dataset.estado = 'ensamblando';

                        // Actualizar color del SVG
                        actualizarColorSvg(wrapper, card);
                    }

                    // Mostrar notificación
                    mostrarNotificacion('Ensamblaje iniciado', 'success');

                } else if (nuevoEstado === 'completada') {
                    btn.querySelector('span').textContent = 'COMPLETADA';
                    btn.classList.remove('bg-yellow-500', 'hover:bg-yellow-600', 'bg-green-600', 'hover:bg-green-700');
                    btn.classList.add('bg-gray-400', 'cursor-not-allowed');
                    btn.disabled = true;

                    // Cambiar color de la tarjeta a verde (ensamblada) - igual que etiquetas normales
                    if (card) {
                        // Limpiar clases de estado anteriores
                        card.className = card.className
                            .split(' ')
                            .filter(c => !c.startsWith('estado-'))
                            .join(' ');
                        card.classList.add('estado-ensamblada');
                        card.dataset.estado = 'ensamblada';

                        // Actualizar color del SVG
                        actualizarColorSvg(wrapper, card);
                    }

                    // Mostrar notificación
                    mostrarNotificacion('¡Ensamblaje completado!', 'success');

                    // Actualizar badge de estado en la etiqueta
                    actualizarBadgeEstado(etiquetaId, 'ensamblada');

                    // Esperar un momento y recargar para actualizar contadores
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }
            } else {
                btn.querySelector('span').textContent = textoOriginal;
                btn.disabled = false;
                mostrarNotificacion(data.message || 'Error al actualizar', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            btn.querySelector('span').textContent = textoOriginal;
            btn.disabled = false;
            mostrarNotificacion('Error de conexión', 'error');
        }
    }

    /**
     * Actualiza el color del SVG según el estado de la tarjeta (igual que etiquetas normales)
     */
    function actualizarColorSvg(wrapper, card) {
        if (!wrapper || !card) return;

        const contenedorSvg = wrapper.querySelector('[id^="contenedor-svg-"]');
        if (!contenedorSvg) return;

        const svg = contenedorSvg.querySelector('svg');
        if (svg) {
            const bgColor = getComputedStyle(card).getPropertyValue('--bg-estado').trim();
            svg.style.background = bgColor || '#fff';
        }
    }

    /**
     * Muestra una notificación temporal
     */
    function mostrarNotificacion(mensaje, tipo = 'info') {
        const colores = {
            success: 'bg-green-600',
            error: 'bg-red-600',
            info: 'bg-blue-600'
        };

        const notif = document.createElement('div');
        notif.className = `fixed top-4 right-4 ${colores[tipo]} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300 translate-x-full`;
        notif.textContent = mensaje;
        document.body.appendChild(notif);

        // Animar entrada
        setTimeout(() => {
            notif.classList.remove('translate-x-full');
        }, 10);

        // Animar salida y eliminar
        setTimeout(() => {
            notif.classList.add('translate-x-full');
            setTimeout(() => notif.remove(), 300);
        }, 3000);
    }

    /**
     * Actualiza el badge de estado en la tarjeta de etiqueta
     */
    function actualizarBadgeEstado(etiquetaId, estado) {
        const wrapper = document.getElementById('etiqueta-ens-' + etiquetaId);
        if (!wrapper) return;

        // Buscar o crear el badge de estado
        const headerH3 = wrapper.querySelector('h3.text-sm');
        if (!headerH3) return;

        let badge = headerH3.querySelector('.estado-badge');
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'estado-badge px-2 py-0.5 rounded text-xs ml-1';
            headerH3.appendChild(badge);
        }

        if (estado === 'completada') {
            badge.className = 'estado-badge px-2 py-0.5 rounded text-xs ml-1 bg-green-600 text-white';
            badge.textContent = 'COMPLETADA';
        } else if (estado === 'en_proceso') {
            badge.className = 'estado-badge px-2 py-0.5 rounded text-xs ml-1 bg-yellow-500 text-black';
            badge.textContent = 'EN_PROCESO';
        }
    }

    // Funciones legacy (mantener por compatibilidad)
    async function iniciarEnsamblaje(ordenId) {
        if (!confirm('¿Iniciar el ensamblaje de esta entidad?')) return;

        try {
            const response = await fetch('/api/ensamblaje/iniciar/' + ordenId, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            });

            if (response.ok) {
                location.reload();
            } else {
                alert('Error al iniciar ensamblaje');
            }
        } catch (error) {
            console.error(error);
            alert('Error de conexión');
        }
    }

    async function completarEnsamblaje(ordenId) {
        if (!confirm('¿Marcar como completado el ensamblaje?')) return;

        try {
            const response = await fetch('/api/ensamblaje/completar/' + ordenId, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            });

            if (response.ok) {
                location.reload();
            } else {
                alert('Error al completar ensamblaje');
            }
        } catch (error) {
            console.error(error);
            alert('Error de conexión');
        }
    }

    /**
     * Dibuja las figuras de los elementos cuando se expande la sección
     */
    function dibujarFigurasElementos() {
        // Buscar todos los contenedores de figuras que no han sido dibujados
        document.querySelectorAll('[id^="figura-elem-"]:not([data-dibujado])').forEach(contenedor => {
            const dimensiones = contenedor.dataset.dimensiones;
            const peso = contenedor.dataset.peso;
            const diametro = contenedor.dataset.diametro;
            const barras = contenedor.dataset.barras;

            if (dimensiones && typeof window.dibujarFiguraElemento === 'function') {
                contenedor.dataset.dibujado = 'true';
                contenedor.innerHTML = ''; // Limpiar el texto de "Cargando..."
                window.dibujarFiguraElemento(contenedor.id, dimensiones, peso, diametro, barras);
            }
        });
    }

    // Observar cambios en el DOM para dibujar figuras cuando se expanden las secciones
    document.addEventListener('DOMContentLoaded', function() {
        // Usar MutationObserver para detectar cuando se muestran las secciones de elementos
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    // Cuando cambia el estilo (x-show), intentar dibujar las figuras
                    setTimeout(dibujarFigurasElementos, 100);
                }
            });
        });

        // Observar todos los contenedores de elementos
        document.querySelectorAll('[x-show="showElementos"]').forEach(el => {
            observer.observe(el, { attributes: true });
        });

        // También intentar dibujar al cargar por si ya hay secciones expandidas
        setTimeout(dibujarFigurasElementos, 500);
    });
</script>

{{-- Script para dibujar figuras de elementos --}}
<script src="{{ asset('js/elementosJs/figuraElemento.js') }}"></script>
