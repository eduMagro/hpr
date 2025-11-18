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

<style>
    /* === Contenedor general === */
    .etiqueta-wrapper {
        display: block;
        margin: 0.5rem 0;
    }

    /* === Prevenir FOUC (Flash of Unstyled Content) === */
    .proceso {
        opacity: 0;
        transition: opacity 0.15s ease-in;
    }

    /* === Etiqueta base (pantalla e impresión) === */
    /* Tamaño real para impresión */
    .etiqueta-card {
        position: relative;
        width: 126mm;
        height: 71mm;
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

    /* QR Box */
    .qr-box {
        position: absolute;
        top: 3mm;
        right: 3mm;
        border: 0.2mm solid #000;
        padding: 1mm;
        background: #fff;
        width: 18mm;
        /* 16mm del QR + 2mm de padding */
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .qr-box img {
        width: 16mm;
        height: 16mm;
        display: block;
    }

    .qr-label {
        width: 16mm;
        font-size: 8pt;
        color: #000;
        text-align: center;
        margin-top: 0.5mm;
        font-weight: bold;
        line-height: 1;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    /* Asegurar que el label del QR se imprima */
    @media print {
        .qr-label {
            display: block !important;
            font-size: 8pt !important;
            font-weight: bold !important;
            line-height: 1 !important;
        }
    }

    /* === Ajustes de pantalla === */
    /* Pantalla: escala mayor sin romper proporción */
    @media screen {
        .etiqueta-card {
            width: 630px;
            /* ancho grande en pantalla (~5 veces más que 126mm) */
            height: 355px;
            /* alto proporcional */
            margin: 0 1rem 1rem 1rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            /* Transición más rápida y eficiente - solo transform para mejor rendimiento */
            transform-origin: top left;
            will-change: transform;
        }

        .qr-label {
            font-size: 6px !important;
        }

        /* Los estilos responsivos están en resources/css/etiquetas-responsive.css */
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
    <div class="etiqueta-card proceso estado-{{ $estado }}" id="etiqueta-{{ $safeSubId }}"
        data-estado="{{ $estado }}">

        <!-- Botones -->
        <div class="absolute top-2 right-2 flex items-center gap-2 no-print z-10">
            <!-- Selector de modo de impresión -->
            <select id="modo-impresion-{{ $etiqueta->id }}"
                class="border border-gray-300 rounded px-2 py-1 text-sm bg-white shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="a6">A6</option>
                <option value="a4">A4</option>
            </select>

            <!-- Botón Imprimir -->
            <button type="button"
                class="bg-blue-600 text-white px-3 py-1 rounded shadow-sm hover:bg-blue-700 hover:shadow-md transition-all duration-200 flex items-center gap-1"
                onclick="const modo = document.getElementById('modo-impresion-{{ $etiqueta->id }}').value; imprimirEtiquetas(['{{ $etiqueta->etiqueta_sub_id }}'], modo)"
                title="Imprimir etiqueta">
                <span class="text-lg">🖨️</span>
            </button>

            <!-- Botón Añadir al carro -->
            <button type="button"
                class="btn-agregar-carro bg-green-600 text-white px-3 py-1 rounded shadow-sm hover:bg-green-700 hover:shadow-md transition-all duration-200 flex items-center gap-1"
                data-etiqueta-id="{{ $etiqueta->etiqueta_sub_id }}" title="Añadir al carro">
                <span class="text-lg">🛒</span>
            </button>

            <!-- Botón Fabricar -->
            <button type="button"
                class="btn-fabricar bg-purple-600 text-white px-3 py-1 rounded shadow-sm hover:bg-purple-700 hover:shadow-md transition-all duration-200 flex items-center gap-1"
                data-etiqueta-id="{{ $etiqueta->etiqueta_sub_id }}" title="Fabricar esta etiqueta">
                <span class="text-lg">⚙️</span>
            </button>
        </div>

        <!-- Contenido -->
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                {{ $planilla->obra->obra }} - {{ $planilla->cliente->empresa }}<br>
                {{ $planilla->codigo_limpio }} - S:{{ $planilla->seccion }}
            </h2>
            <h3 class="text-lg font-semibold text-gray-900">
                {{ $etiqueta->etiqueta_sub_id }} - {{ $etiqueta->nombre ?? 'Sin nombre' }} - Cal:B500SD -
                {{ $etiqueta->peso_kg ?? 'N/A' }}
            </h3>
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
    // Declarar domSafe solo una vez globalmente
    if (!window.domSafe) {
        window.domSafe = (v) => String(v).replace(/[^A-Za-z0-9_-]/g, '-');
    }

    async function imprimirEtiquetas(ids, modo = 'a4') {

        if (!Array.isArray(ids)) ids = [ids];
        const etiquetasHtml = [];

        for (const rawId of ids) {
            const safeId = window.domSafe(rawId);
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

                        // Crear box del QR
                        const qrBox = document.createElement('div');
                        qrBox.className = 'qr-box';
                        qrBox.appendChild(qrNode);

                        // Crear label debajo del QR (dentro del box)
                        const qrLabel = document.createElement('div');
                        qrLabel.className = 'qr-label';
                        qrLabel.textContent = String(rawId);
                        qrLabel.style.fontSize = '8pt';
                        qrLabel.style.marginTop = '0.5mm';
                        qrLabel.style.lineHeight = '1';
                        qrLabel.style.textAlign = 'center';
                        qrLabel.style.fontWeight = 'bold';
                        qrBox.appendChild(qrLabel);

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
