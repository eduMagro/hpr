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
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
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
                    const targetCanvas = clone.querySelector('canvas');
                    const host = targetCanvas ? targetCanvas.parentNode : clone;
                    if (host) {
                        if (targetCanvas) targetCanvas.remove();
                        const img = new Image();
                        img.src = canvasImg;
                        img.style.width = '100%';
                        img.style.height = 'auto';
                        host.appendChild(img);
                    }
                }

                const tempQR = document.createElement('div');
                document.body.appendChild(tempQR);
                await new Promise(res => {
                    new QRCode(tempQR, {
                        text: String(rawId),
                        width: 50,
                        height: 50
                    });
                    setTimeout(() => {
                        const qrImg = tempQR.querySelector('img');
                        const qrCanvas = tempQR.querySelector('canvas');
                        const qrNode = qrImg || (qrCanvas ? (() => {
                            const img = new Image();
                            img.src = qrCanvas.toDataURL();
                            return img;
                        })() : null);

                        if (qrNode) {
                            qrNode.classList.add('qr-print');
                            const qrBox = document.createElement('div');
                            qrBox.className = 'qr-box';
                            qrBox.appendChild(qrNode);
                            clone.insertBefore(qrBox, clone.firstChild);
                        }
                        tempQR.remove();
                        res();
                    }, 150);
                });

                etiquetasHtml.push(clone.outerHTML);
            }

            const css = `
            <style>
                @page{size:A4 portrait;margin:10;}
                body{margin:0;padding:0;background:#fff;}
                .sheet-grid{
                  display:grid;
                  grid-template-columns:105mm 105mm;
                  grid-template-rows:repeat(5,59.4mm);
                  width:210mm;height:297mm;
                }
                .etiqueta-print{
                  position:relative;width:105mm;height:59.4mm;
                  box-sizing:border-box;border:0.2mm solid #000;
                  overflow:hidden;padding:3mm;background:#fff;
                  page-break-inside:avoid;
                }
                .etiqueta-print h2{font-size:10pt;margin:0;}
                .etiqueta-print h3{font-size:9pt;margin:0;}
                .etiqueta-print img:not(.qr-print){width:100%;height:auto;margin-top:2mm;}
                .qr-box{
                  position:absolute;top:3mm;right:3mm;
                  border:0.2mm solid #000;padding:1mm;background:#fff;
                }
                .qr-box img{width:16mm;height:16mm;}
                .no-print{display:none!important;}
            </style>`;

            const w = window.open('', '_blank');
            w.document.open();
            w.document.write(`
              <html>
                <head><title>Impresión</title>${css}</head>
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


</x-app-layout>
