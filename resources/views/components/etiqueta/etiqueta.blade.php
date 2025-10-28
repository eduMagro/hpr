@props([
    'etiqueta', // Objeto de la etiqueta
    'planilla', // Objeto de la planilla
    'maquinaTipo', // Tipo de la máquina ("ensambladora" u otro)
])

@php
    $safeSubId = str_replace('.', '-', $etiqueta->etiqueta_sub_id);
    $estado = strtolower($etiqueta->estado ?? 'pendiente');

    if (in_array($estado, ['fabricada', 'completada', 'ensamblada', 'soldada']) && $etiqueta->paquete_id) {
        $estado = 'en-paquete';
    }

@endphp
@php
    $elemento = $etiqueta->elementos->first();
    $productos = [];

    if ($elemento?->producto) {
        $productos[] = $elemento->producto->n_colada ?? 'Producto 1';
    }
    if ($elemento?->producto2) {
        $productos[] = $elemento->producto2->n_colada ?? 'Producto 2';
    }
    if ($elemento?->producto3) {
        $productos[] = $elemento->producto3->n_colada ?? 'Producto 3';
    }
@endphp

<style>
    /* === Contenedor general === */
    .etiqueta-wrapper {
        display: block;
        margin: 0.5rem 0;
    }

    .etiqueta-id-web-only {
        text-align: left;
        margin-bottom: 2px;
        font-size: 0.75rem;
        color: #4b5563;
    }

    /* === Etiqueta base (pantalla e impresión) === */
    /* Tamaño real para impresión */
    .etiqueta-card {
        position: relative;
        width: 105mm;
        height: 59.4mm;
        box-sizing: border-box;
        border: 0.2mm solid #000;
        overflow: hidden;
        background: var(--bg-estado, #fff);
        padding: 3mm;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        transform-origin: top left;
    }

    .etiqueta-card svg {
        flex: 1 1 auto;
        width: 100%;
        height: 100%;

    }

    /* QR */
    .qr-box {
        position: absolute;
        top: 3mm;
        right: 3mm;
        border: 0.2mm solid #000;
        padding: 1mm;
        background: #fff;
    }

    .qr-box img {
        width: 16mm;
        height: 16mm;
    }

    /* === Ajustes de pantalla === */
    /* Pantalla: escala mayor sin romper proporción */
    @media screen {
        .etiqueta-card {
            width: 525px;
            /* ancho grande en pantalla (~5 veces más que 105mm) */
            height: 297px;
            /* alto proporcional */
            margin: 1rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }
    }

    /* Impresión: usa medidas exactas en mm */
    @media print {
        .etiqueta-card {
            width: 105mm !important;
            height: 59.4mm !important;
            margin: 0;
            box-shadow: none;
        }

        .no-print {
            display: none !important;
        }

        .etiqueta-id-web-only {
            display: none !important;
        }
    }

    /* Bloquea selección accidental en móviles */
    .proceso,
    .proceso * {
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
    }
</style>

<div class="etiqueta-wrapper">
    <div class="etiqueta-id-web-only">
        {{ $etiqueta->etiqueta_sub_id }}
    </div>

    <div class="etiqueta-card proceso estado-{{ $estado }}" id="etiqueta-{{ $safeSubId }}"
        data-estado="{{ $estado }}">

        <!-- Botones -->
        <div class="relative">
            <div class="absolute top-2 right-20 flex items-center gap-2 no-print">
                <select id="modo-impresion-{{ $etiqueta->id }}" class="border border-gray-300 rounded px-2 py-1 text-sm">
                    <option value="a6">A6</option>
                    <option value="a4">A4</option>
                </select>

                <button type="button" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700"
                    onclick="const modo = document.getElementById('modo-impresion-{{ $etiqueta->id }}').value;
                 imprimirEtiquetas(['{{ $etiqueta->etiqueta_sub_id }}'], modo)">
                    🖨️
                </button>
            </div>


            <button type="button"
                class="absolute top-2 right-7 no-print btn-fabricar bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700"
                data-etiqueta-id="{{ $etiqueta->etiqueta_sub_id }}" title="Fabricar esta etiqueta">⚙️</button>
        </div>

        <!-- Contenido -->
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                {{ $planilla->obra->obra }} - {{ $planilla->cliente->empresa }}<br>
                {{ $planilla->codigo_limpio }} - S:{{ $planilla->seccion }}
            </h2>
            <h3 class="text-lg font-semibold text-gray-900">
                {{ $etiqueta->nombre ?? 'Sin nombre' }} - Cal:B500SD - {{ $etiqueta->peso_kg ?? 'N/A' }}
            </h3>
            <p class="text-[8pt] text-gray-800 leading-tight">
                Coladas: <span class="font-semibold">{{ implode(', ', $productos) }}</span>
            </p>

        </div>

        <!-- SVG -->
        <div id="contenedor-svg-{{ $etiqueta->id }}" class="w-full h-full"></div>

        <!-- Canvas oculto para impresión -->
        <div style="width:100%;border-top:1px solid black;visibility:hidden;height:0;">
            <canvas id="canvas-imprimir-etiqueta-{{ $etiqueta->etiqueta_sub_id }}"></canvas>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    const domSafe = (v) => String(v).replace(/[^A-Za-z0-9_-]/g, '-');

    async function imprimirEtiquetas(ids, modo = 'a4') {

        if (!Array.isArray(ids)) ids = [ids];
        const etiquetasHtml = [];

        for (const rawId of ids) {
            const safeId = domSafe(rawId);
            let contenedor = document.getElementById(`etiqueta-${safeId}`) ||
                document.getElementById(`etiqueta-${rawId}`);
            if (!contenedor) continue;

            // Buscar canvas
            let canvas = document.getElementById(`canvas-imprimir-etiqueta-${safeId}`) ||
                document.getElementById(`canvas-imprimir-etiqueta-${rawId}`) ||
                contenedor.querySelector('canvas');

            // Renderizar a imagen
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

            // Clonar y limpiar
            const clone = contenedor.cloneNode(true);
            clone.classList.add('etiqueta-print');
            clone.querySelectorAll('.no-print').forEach(el => el.remove());

            // Reemplazar canvas
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

            // Generar QR
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

        // CSS e impresión
        let css = '';
        if (modo === 'a4') {
            css = `<style>
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
        .qr-box{position:absolute;top:3mm;right:3mm;border:0.2mm solid #000;padding:1mm;background:#fff;}
        .qr-box img{width:16mm;height:16mm;}
        .no-print{display:none!important;}
    </style>`;
        } else if (modo === 'a6') {
            css = `<style>
  @page { size: A6 landscape; margin: 0; }

  html, body {
    margin: 0;
    padding: 0;
    background: #fff;
    width: 148mm;
    height: 105mm;
  }

  body {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .etiqueta-print {
    width: 140mm;
    height: 100mm;
    padding: 4mm;
    box-sizing: border-box;
    border: 0.2mm solid #000;
    background: #fff;
    overflow: hidden;
    position: relative;
    page-break-after: always;
  }

  .etiqueta-print h2 {
    font-size: 11pt;
    margin: 0 0 2mm 0;
    line-height: 1.3;
  }

  .etiqueta-print h3 {
    font-size: 10pt;
    margin: 0 0 2mm 0;
  }

  .etiqueta-print img:not(.qr-print) {
    width: 100%;
    height: auto;
    margin-top: 3mm;
  }

  .qr-box {
    position: absolute;
    top: 4mm;
    right: 4mm;
    border: 0.2mm solid #000;
    padding: 1mm;
    background: #fff;
  }

  .qr-box img {
    width: 20mm;
    height: 20mm;
  }

  .no-print {
    display: none !important;
  }
</style>`;

        }


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
          </html>`);
        w.document.close();
    }
</script>
