@props([
    'etiqueta', // Objeto de la etiqueta
    'planilla', // Objeto de la planilla
    'maquinaTipo', // Tipo de la máquina ("ensambladora" u otro)
])

@php
    $safeSubId = str_replace('.', '-', $etiqueta->etiqueta_sub_id);
    $estado = strtolower($etiqueta->estado ?? 'pendiente');

@endphp
<style>
    /* Mapa de colores centralizado */
    .proceso {
        --bg-estado: #e5e7eb;
    }

    /* default */

    .proceso.estado-pendiente {
        --bg-estado: #ffffff;
    }

    /* blanco */
    .proceso.estado-fabricando,
    .proceso.estado-ensamblando,
    .proceso.estado-soldando {
        --bg-estado: #facc15;
    }

    /* amarillo */

    .proceso.estado-fabricada,
    .proceso.estado-completada,
    .proceso.estado-ensamblada,
    .proceso.estado-soldada {
        --bg-estado: #22c55e;
    }

    /* verde */
</style>



<div class="proceso border shadow-xl mt-4 estado-{{ $estado }}" id="etiqueta-{{ $safeSubId }}"
    data-estado="{{ $estado }}" style="background-color: var(--bg-estado); border:1px solid black; width:100%;">

    <div class="relative"><!-- Botón de impresión -->
        <!-- Botón para fabricar la etiqueta actual -->

        <button onclick="imprimirEtiquetas(['{{ $etiqueta->etiqueta_sub_id }}'])"
            class="absolute top-2 right-2 text-blue-800 hover:text-blue-900 no-print" title="Imprimir esta etiqueta">
            🖨️
        </button>
        <button type="button"
            class="absolute top-2 right-12 bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 no-print btn-fabricar"
            data-etiqueta-id="{{ $etiqueta->etiqueta_sub_id }}" title="Fabricar esta etiqueta">
            ⚙️
        </button>
    </div>

    <!-- Contenido principal -->
    <div class="p-2">
        <h2 class="text-lg font-semibold text-gray-900">
            <span>{{ $planilla->obra->obra }}</span> -
            <span>{{ $planilla->cliente->empresa }}</span><br>
            <span>{{ $planilla->codigo_limpio }}</span> - S:{{ $planilla->seccion }}
        </h2>

        <h3 class="text-lg font-semibold text-gray-900">
            <span class="text-blue-700">{{ $etiqueta->etiqueta_sub_id }}</span>
            {{ $etiqueta->nombre ?? 'Sin nombre' }} -
            <span>Cal:B500SD</span> -
            {{ $etiqueta->peso_kg ?? 'N/A' }}
        </h3>

        <!-- QR oculto -->
        <div id="qrContainer-{{ $etiqueta->id }}" style="display: none;"></div>

        <!-- Datos de estado y fechas -->
        <div class="p-2 no-print">
            <p>
                <strong>Estado:</strong>
                <span id="estado-{{ str_replace('.', '-', $etiqueta->etiqueta_sub_id) }}">
                    {{ $etiqueta->estado ?? 'N/A' }}
                </span>
                <strong>Fecha Inicio:</strong>
                <span id="inicio-{{ str_replace('.', '-', $etiqueta->etiqueta_sub_id) }}">
                    {{ $maquinaTipo === 'ensambladora'
                        ? $etiqueta->fecha_inicio_ensamblado ?? 'No asignada'
                        : $etiqueta->fecha_inicio ?? 'No asignada' }}
                </span>
                <strong>Fecha Finalización:</strong>
                <span id="final-{{ str_replace('.', '-', $etiqueta->etiqueta_sub_id) }}">
                    {{ $maquinaTipo === 'ensambladora'
                        ? $etiqueta->fecha_finalizacion_ensamblado ?? 'No asignada'
                        : $etiqueta->fecha_finalizacion ?? 'No asignada' }}
                </span>
            </p>
        </div>
    </div>

    <!-- Canvas -->
    <div>
        <div id="contenedor-svg-{{ $etiqueta->id }}" class="w-full h-full"></div>
        <div style="width:100%;border-top:1px solid black;visibility:hidden;height:0;">
            <canvas id="canvas-imprimir-etiqueta-{{ $etiqueta->etiqueta_sub_id }}"></canvas>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    // Convierte cualquier id (con puntos, etc.) en un id seguro para el DOM
    const domSafe = (v) => String(v).replace(/[^A-Za-z0-9_-]/g, '-');

    async function imprimirEtiquetas(ids) {
        if (!Array.isArray(ids)) ids = [ids];

        const etiquetasHtml = [];

        for (const rawId of ids) {
            const safeId = domSafe(rawId);

            // 1) Ubicar contenedor (acepta ambas variantes)
            let contenedor =
                document.getElementById(`etiqueta-${safeId}`) ||
                document.getElementById(`etiqueta-${rawId}`);
            if (!contenedor) continue;

            // 2) Localizar el canvas de la figura (ambas variantes + fallback dentro del contenedor)
            let canvas =
                document.getElementById(`canvas-imprimir-etiqueta-${safeId}`) ||
                document.getElementById(`canvas-imprimir-etiqueta-${rawId}`) ||
                contenedor.querySelector('canvas');

            // 3) Renderizar canvas a imagen (si hay)
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

            // 4) Clonar tarjeta y limpiar elementos no imprimibles
            const clone = contenedor.cloneNode(true);
            clone.classList.add('etiqueta-print');
            clone.querySelectorAll('.no-print').forEach(el => el.remove());

            // 5) Reemplazar canvas por imagen rasterizada (si lo teníamos)
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

            // 6) Generar QR con el **id original** (rawId)
            const tempQR = document.createElement('div');
            document.body.appendChild(tempQR);
            await new Promise(res => {
                new QRCode(tempQR, {
                    text: String(rawId),
                    width: 50,
                    height: 50
                });
                setTimeout(() => {
                    // soporta librerías que crean <img> o <canvas>
                    const qrImg = tempQR.querySelector('img');
                    const qrCanvas = tempQR.querySelector('canvas');
                    const qrNode = qrImg || (qrCanvas ? (function() {
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

        // 7) CSS e impresión en ventana nueva
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
      </style>
    `;

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
</script>


<style>
    @page {
        size: A4 portrait;
        margin: 10 mm;
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
        /* dos columnas iguales */
        grid-template-rows: repeat(5, 1fr);
        /* cinco filas iguales */
        width: 100vw;
        /* usar ancho total del viewport de impresión */
        height: 100vh;
        /* usar alto total del viewport de impresión */
    }

    .etiqueta-print {
        position: relative;
        width: 105mm;
        height: 59.4mm;
        box-sizing: border-box;
        /* 🔥 padding incluido en el tamaño fijo */
        border: 0.2mm solid #000;
        overflow: hidden;
        background: #fff;
        page-break-inside: avoid;

        /* 👇 Aquí está tu padding interno */
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
        /* mantenemos tamaño físico del QR */
        height: 20mm;
    }

    @media print {
        .no-print {
            display: none !important;
        }
    }

    /* Desactiva menú/selección por long-press dentro de la tarjeta/etiqueta */
    .proceso,
    .proceso * {
        -webkit-touch-callout: none;
        /* iOS Safari: sin menú */
        -webkit-user-select: none;
        /* iOS */
        user-select: none;
        /* resto */
        -webkit-tap-highlight-color: transparent;
    }
</style>
