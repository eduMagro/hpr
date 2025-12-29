<x-app-layout>
    <x-slot name="title">{{ $planilla->codigo_limpio }} - {{ config('app.name') }}</x-slot>

    <x-slot name="header">
        {{-- CABECERA SHOW --}}
        <div x-data="{ planilla: @js($planilla) }" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Planilla <strong>{{ $planilla->codigo_limpio }}</strong>
                </h2>
                <p class="text-sm text-gray-600">
                    Obra: <strong>{{ $planilla->obra->obra ?? '—' }}</strong> ·
                    Cliente: <strong>{{ $planilla->cliente->empresa ?? '—' }}</strong> ·
                    Sección: <strong>{{ $planilla->seccion ?? '—' }}</strong>
                </p>
            </div>

            <div class="flex items-center gap-3 sm:w-auto">
                {{-- Barra de progreso --}}
                <div>
                    <div class="w-32 sm:w-64 bg-gray-200 rounded-full h-3 overflow-hidden">
                        <div class="bg-blue-500 h-3 rounded-full" style="width: {{ $progreso }}%"></div>
                    </div>
                    <p class="text-right text-xs text-gray-500 mt-1">{{ $progreso }}%</p>
                </div>

                {{-- Botón Revisada (igual que en index) --}}
                <button @click="
        planilla.revisada = !planilla.revisada;
        guardarCambios(planilla);
    "
                    class="w-7 h-7 rounded flex items-center justify-center transition
           hover:bg-indigo-200"
                    :class="planilla.revisada ? 'bg-green-100 text-green-700' : 'bg-indigo-100 text-indigo-700'"
                    :title="planilla.revisada ? 'Quitar revisión' : 'Marcar como revisada'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 12l2 2 4-4-1.5-1.5L11 11l-1.5-1.5L8 11l1 1z" />
                    </svg>
                </button>


                <div class="flex items-center gap-2">
                    {{-- Flecha anterior --}}
                    <button type="button"
                        class="w-9 h-9 rounded-md border bg-white hover:bg-gray-50 flex items-center justify-center"
                        title="Planilla anterior (←)" aria-label="Planilla anterior" onclick="navegarPlanilla(-1)">
                        {{-- Heroicon chevron-left --}}
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </button>

                    {{-- Flecha siguiente --}}
                    <button type="button"
                        class="w-9 h-9 rounded-md border bg-white hover:bg-gray-50 flex items-center justify-center"
                        title="Planilla siguiente (→)" aria-label="Planilla siguiente" onclick="navegarPlanilla(1)">
                        {{-- Heroicon chevron-right --}}
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M8.25 4.5L15.75 12l-7.5 7.5" />
                        </svg>
                    </button>
                </div>


            </div>
        </div>

        {{-- Si NO tienes la función global en el layout, añade este script en la show --}}
        <script>
            if (typeof guardarCambios !== 'function') {
                function guardarCambios(planilla) {
                    fetch(`{{ route('planillas.update', '') }}/${planilla.id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                ...planilla,
                                _method: 'PUT'
                            })
                        })
                        .then(async (response) => {
                            const ct = response.headers.get('content-type') || '';
                            const data = ct.includes('application/json') ? await response.json() : {};
                            if (!response.ok || !data.success) {
                                throw new Error(data.message || 'No se pudo actualizar la planilla');
                            }
                            // Si quieres refrescar:
                            window.location.reload();
                        })
                        .catch(err => {
                            Swal?.fire?.({
                                icon: "error",
                                title: "Error al actualizar",
                                text: err.message || "Error de conexión",
                                confirmButtonText: "OK"
                            }) || alert(err.message || 'Error al actualizar');
                        });
                }
            }
        </script>


    </x-slot>

    <div class="w-full sm:px-4 py-6">
        <div class="space-y-6">
            {{-- ========================================= --}}
            {{-- SECCIÓN DE ENTIDADES/ENSAMBLAJES --}}
            {{-- ========================================= --}}
            @if ($planilla->entidades->isNotEmpty())
                <section class="bg-white border shadow rounded-lg">
                    <header class="p-3 border-b flex items-center justify-between bg-amber-50">
                        <h3 class="font-semibold text-lg text-amber-800">
                            <svg xmlns="http://www.w3.org/2000/svg" class="inline-block w-5 h-5 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                            Ensamblajes ({{ $planilla->entidades->count() }})
                        </h3>
                        <span class="text-sm text-amber-600">
                            {{ $planilla->entidades->sum('total_barras') }} barras |
                            {{ $planilla->entidades->sum('total_estribos') }} estribos
                        </span>
                    </header>

                    <div class="p-3">
                        {{-- Grid de tarjetas de entidades con visualizacion grafica --}}
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                            @foreach ($planilla->entidades as $entidad)
                                <x-entidad.ensamblaje :entidad="$entidad" :planilla="$planilla" />
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            @foreach ($maquinas as $clave => $maquina)
                @php
                    $bloque = $etiquetasPorMaquina->get($maquina->id ?? 'sin', collect());
                    $maquinaTipo = strtolower(trim($maquina->tipo ?? 'normal'));
                @endphp

                <section class="bg-white border shadow rounded-lg">
                    <header class="p-3 border-b flex items-center justify-between">
                        <h3 class="font-semibold text-lg">{{ $maquina->nombre ?? 'Sin máquina' }}</h3>
                    </header>

                    <div class="p-3">
                        @if ($bloque->isEmpty())
                            <p class="text-sm text-gray-500">No hay etiquetas en esta máquina.</p>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                @foreach ($bloque as $item)
                                    @php $etiqueta = $item['etiqueta'] ?? null; @endphp
                                    @continue(!$etiqueta || !$etiqueta->id) {{-- si no hay id, saltamos --}}

                                    <x-etiqueta.etiqueta :etiqueta="$etiqueta" :planilla="$planilla" :maquina-tipo="$maquinaTipo" />
                                @endforeach

                            </div>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    </div>
    {{-- === Variables que LEE canvasMaquina.js === --}}
    <script>
        // 👇 ESTE nombre es el que usa canvasMaquina.js
        window.elementosAgrupadosScript = @json($elementosAgrupadosScript);
        // otros datasets opcionales que tu script pudiera consultar:
        window.pesosElementos = @json($pesosElementos ?? []);
        window.SUGERENCIAS = window.SUGERENCIAS || {};
    </script>

    {{-- Solo los JS necesarios. Ojo al orden: datasets -> canvasMaquina --}}
    <script src="{{ asset('js/maquinaJS/canvasMaquina.js') }}"></script>
    <script src="{{ asset('js/imprimirQrS.js') }}"></script>

    {{-- Helper de impresión (mismo flujo que en la vista de máquina) --}}
    <script>
        const domSafe = (v) => String(v).replace(/[^A-Za-z0-9_-]/g, '-');

        async function imprimirEtiquetas(ids) {
            if (!Array.isArray(ids)) ids = [ids];

            const etiquetasHtml = [];
            for (const rawId of ids) {
                const safeId = domSafe(rawId);

                let contenedor =
                    document.getElementById(`etiqueta-${safeId}`) ||
                    document.getElementById(`etiqueta-${rawId}`);
                if (!contenedor) continue;

                let canvas =
                    document.getElementById(`canvas-imprimir-etiqueta-${safeId}`) ||
                    document.getElementById(`canvas-imprimir-etiqueta-${rawId}`) ||
                    contenedor.querySelector('canvas');

                let canvasImg = null;
                if (canvas && (canvas.width || canvas.height)) {
                    const scale = 2;
                    const tmp = document.createElement('canvas');
                    const w = canvas.width || canvas.getBoundingClientRect().width || 600;
                    const h = canvas.height || canvas.getBoundingClientRect().height || 200;
                    tmp.width = Math.max(1, Math.round(w * scale));
                    tmp.height = Math.max(1, Math.round(h * scale));
                    const ctx = tmp.getContext('2d');
                    ctx.scale(scale, scale);
                    ctx.drawImage(canvas, 0, 0);
                    canvasImg = tmp.toDataURL('image/png');
                }

                const clone = contenedor.cloneNode(true);
                clone.classList.add('etiqueta-print');
                clone.querySelectorAll('.no-print').forEach(el => el.remove());

                if (canvasImg) {
                    let elCanvas = clone.querySelector('canvas');
                    if (elCanvas) {
                        const imgTag = document.createElement('img');
                        imgTag.src = canvasImg;
                        imgTag.style.width = '100%';
                        imgTag.style.height = 'auto';
                        elCanvas.replaceWith(imgTag);
                    }
                }

                const existingImgs = clone.querySelectorAll('img');
                existingImgs.forEach(img => {
                    img.style.maxHeight = '200px';
                    img.style.width = 'auto';
                    img.style.height = 'auto';
                });

                etiquetasHtml.push(clone.outerHTML);
            }

            if (!etiquetasHtml.length) {
                alert('No hay etiquetas para imprimir.');
                return;
            }

            const css = `
        @page { size: A4 portrait; margin: 10mm; }
        html, body {
          margin:0; padding:0; width:100%; height:100%; background:#fff;
          font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .sheet-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          grid-template-rows: repeat(5, 1fr);
          width: 100vw; height: 100vh;
        }
        .etiqueta-print {
          position: relative; width:105mm; height:59.4mm;
          box-sizing: border-box; border:0.2mm solid #000;
          overflow:hidden; background:#fff;
          page-break-inside: avoid;
          padding:4mm;
          display:flex; flex-direction:column; justify-content:flex-start;
        }
        .qr-box {
          position:absolute; top:2%; right:2%;
          border:0.2mm solid #000; padding:2px; background:#fff;
        }
        .qr-box img { width:20mm; height:20mm; }
        @media print {
          .no-print { display:none !important; }
        }
      `;

            const w = window.open('', '_blank');
            w.document.write(`
        <!DOCTYPE html>
        <html>
          <head><title>Impresión</title><style>${css}</style></head>
          <body>
            <div class="sheet-grid">${etiquetasHtml.join('')}</div>
            <script>
              window.onload = () => {
                const imgs = document.images;
                let loaded = 0, total = imgs.length;
                if(total===0){window.print();setTimeout(()=>window.close(),500);return;}
                for(const img of imgs){
                  if(img.complete){
                    loaded++; if(loaded===total){window.print();setTimeout(()=>window.close(),500);}
                  }else{
                    img.onload = img.onerror = () => { loaded++; if(loaded===total){window.print();setTimeout(()=>window.close(),500);} };
                  }
                }
              };
            <\/script>
          </body>
        </html>
      `);
            w.document.close();
        }

        // Bloquear menú contextual sólo en tarjetas de etiqueta (igual que en máquina)
        document.addEventListener('contextmenu', (e) => {
            if (e.target.closest('.proceso')) e.preventDefault();
        }, {
            capture: true
        });
    </script>

    {{-- Estilos de impresión en línea (coherentes con máquina) --}}
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background: #fff;
        }

        .sheet-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: repeat(5, 1fr);
            width: 100vw;
            height: 100vh;
        }

        .etiqueta-print {
            position: relative;
            width: 105mm;
            height: 59.4mm;
            box-sizing: border-box;
            border: 0.2mm solid #000;
            overflow: hidden;
            background: #fff;
            page-break-inside: avoid;
            padding: 4mm;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .qr-box {
            position: absolute;
            top: 2%;
            right: 2%;
            border: 0.2mm solid #000;
            padding: 2px;
            background: #fff;
        }

        .qr-box img {
            width: 20mm;
            height: 20mm;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }

        .proceso,
        .proceso * {
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }
    </style>

    {{-- ========================================= --}}
    {{-- SISTEMA DE NAVEGACIÓN CON FLECHAS --}}
    {{-- ========================================= --}}
    <script>
        /**
         * Extrae el ID numérico de la URL de la planilla.
         * Asume que la URL tiene formato: /planillas/{id} o similar
         * Retorna: { value: número, index: posición, parts: array }
         */
        function extraerIdNumericoDelPath(pathname) {
            const parts = pathname.split('/').filter(p => p);

            // Buscar el índice que contiene "planillas" y el siguiente debería ser el ID
            const planillasIdx = parts.findIndex(p => p.toLowerCase() === 'planillas');

            if (planillasIdx !== -1 && planillasIdx + 1 < parts.length) {
                const idStr = parts[planillasIdx + 1];
                const idNum = parseInt(idStr, 10);

                if (!isNaN(idNum)) {
                    return {
                        value: idNum,
                        index: planillasIdx + 1,
                        parts: parts
                    };
                }
            }

            // Fallback: buscar cualquier número en el path
            for (let i = 0; i < parts.length; i++) {
                const num = parseInt(parts[i], 10);
                if (!isNaN(num) && num > 0) {
                    return {
                        value: num,
                        index: i,
                        parts: parts
                    };
                }
            }

            return null;
        }

        /**
         * Navega a la planilla anterior (-1) o siguiente (+1)
         */
        async function navegarPlanilla(delta) {
            const base = new URL(window.location.href);
            const info = extraerIdNumericoDelPath(base.pathname);

            if (!info) {
                console.error('No se pudo extraer el ID de la planilla de la URL');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo determinar el ID de la planilla actual.',
                    confirmButtonText: 'OK'
                });
                return;
            }

            const maxSaltos = 25;
            let candidato = info.value;

            // Mostrar modal de carga
            Swal.fire({
                title: delta > 0 ? 'Buscando planilla siguiente…' : 'Buscando planilla anterior…',
                html: '<div id="swal-loading-msg" class="text-sm">Comprobando…</div>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                for (let i = 0; i < maxSaltos; i++) {
                    candidato += delta;
                    if (candidato < 1) break;

                    const parts = [...info.parts];
                    parts[info.index] = String(candidato);

                    const urlCandidata = new URL(base.toString());
                    urlCandidata.pathname = '/' + parts.join('/');

                    const msgEl = document.getElementById('swal-loading-msg');
                    if (msgEl) msgEl.textContent = `Comprobando ID ${candidato}…`;

                    const resultado = await existePlanilla(urlCandidata.toString());

                    if (resultado.existe) {
                        // Actualizar mensaje con el código encontrado
                        if (msgEl && resultado.codigo) {
                            msgEl.textContent = `Encontrada: ${resultado.codigo}`;
                        }

                        // Pequeña pausa para que se vea el mensaje
                        await new Promise(resolve => setTimeout(resolve, 300));

                        Swal.close();
                        window.location.assign(urlCandidata.toString());
                        return;
                    }
                }

                // No se encontró nada
                Swal.close();
                Swal.fire({
                    icon: 'info',
                    title: 'Sin más planillas',
                    text: 'No hay más planillas en esa dirección.',
                    confirmButtonText: 'Entendido'
                });
            } catch (err) {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error al buscar',
                    text: err?.message || 'Ha ocurrido un error inesperado.'
                });
            }
        }

        /**
         * Verifica si existe una planilla en la URL dada y extrae su codigo_limpio
         * @returns {Object} { existe: boolean, codigo: string }
         */
        async function existePlanilla(urlStr) {
            const timeoutMs = 5000;
            const ac = new AbortController();
            const t = setTimeout(() => ac.abort(), timeoutMs);

            try {
                // Siempre usar GET para poder extraer el código
                const resp = await fetch(urlStr, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'fetch'
                    },
                    credentials: 'same-origin',
                    cache: 'no-store',
                    signal: ac.signal,
                });

                if (!resp.ok) {
                    return {
                        existe: false,
                        codigo: null
                    };
                }

                // Extraer el codigo_limpio del HTML
                const html = await resp.text();

                // Buscar el patrón: Planilla <strong>CODIGO</strong>
                const match = html.match(/Planilla\s*<strong>([^<]+)<\/strong>/i);
                const codigo = match ? match[1].trim() : null;

                return {
                    existe: true,
                    codigo
                };
            } catch {
                return {
                    existe: false,
                    codigo: null
                };
            } finally {
                clearTimeout(t);
            }
        }

        /**
         * Event listener para las teclas de flecha
         */
        document.addEventListener('keydown', function(e) {
            // Ignorar si el usuario está escribiendo en un input/textarea
            const tagName = e.target.tagName.toLowerCase();
            const isEditable = e.target.isContentEditable;

            if (tagName === 'input' || tagName === 'textarea' || tagName === 'select' || isEditable) {
                return;
            }

            // Ignorar si hay algún modal de SweetAlert abierto
            if (document.querySelector('.swal2-container')) {
                return;
            }

            // Flecha izquierda (←) - Planilla anterior
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                navegarPlanilla(-1);
            }

            // Flecha derecha (→) - Planilla siguiente
            if (e.key === 'ArrowRight') {
                e.preventDefault();
                navegarPlanilla(1);
            }
        });

        // Mensaje de consola para ayudar con debugging
        console.log('Sistema de navegación con flechas cargado. Usa ← y → para navegar entre planillas.');

        /**
         * Imprime una tarjeta de entidad/ensamblaje
         */
        function imprimirEntidad(entidadId) {
            const contenedor = document.getElementById(entidadId);
            if (!contenedor) {
                alert('No se encontró la entidad para imprimir');
                return;
            }

            // Clonar y limpiar
            const clone = contenedor.cloneNode(true);
            clone.querySelectorAll('.no-print').forEach(el => el.remove());

            // Expandir el contenido si está colapsado
            const contenidoExpandible = clone.querySelector('[x-show]');
            if (contenidoExpandible) {
                contenidoExpandible.style.display = 'block';
                contenidoExpandible.removeAttribute('x-show');
                contenidoExpandible.removeAttribute('x-collapse');
            }

            const css = `
                @page { size: A4 landscape; margin: 10mm; }
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; }
                .entidad-ensamblaje-card { border: 1px solid #ccc; border-radius: 8px; overflow: hidden; }
                svg { max-width: 100%; }
            `;

            const w = window.open('', '_blank');
            w.document.write(`
                <!DOCTYPE html>
                <html>
                <head><title>Imprimir Ensamblaje</title><style>${css}</style></head>
                <body>${clone.outerHTML}</body>
                </html>
            `);
            w.document.close();
            w.onload = () => {
                w.print();
                setTimeout(() => w.close(), 500);
            };
        }
    </script>

</x-app-layout>
