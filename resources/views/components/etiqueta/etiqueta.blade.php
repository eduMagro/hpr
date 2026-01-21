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

<div class="etiqueta-wrapper" data-etiqueta-sub-id="{{ $etiqueta->etiqueta_sub_id }}" data-paquete-id="{{ $etiqueta->paquete_id ?? '' }}">
    <div class="etiqueta-card proceso estado-{{ $estado }}" id="etiqueta-{{ $safeSubId }}"
        data-estado="{{ $estado }}"
        data-en-paquete="{{ $etiqueta->paquete_id ? 'true' : 'false' }}"
        data-planilla-codigo="{{ $planilla->codigo_limpio ?? '' }}"
        data-planilla-id="{{ $planilla->id ?? '' }}">

        <!-- Botones -->
        <div class="absolute top-2 right-2 flex items-center gap-2 no-print z-10">
            <!-- Botón Deshacer (UNDO) - Manejado por historialEtiquetas.js -->
            <button type="button"
                class="btn-deshacer bg-amber-500 text-white px-3 py-1 rounded shadow-sm hover:bg-amber-600 hover:shadow-md transition-all duration-200 flex items-center gap-1"
                data-etiqueta-id="{{ $etiqueta->etiqueta_sub_id }}"
                title="Deshacer último cambio (Ctrl+Z)">
                <span class="text-lg">↩️</span>
            </button>

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
                {{ $planilla->obra->obra ?? 'Sin obra' }} - {{ $planilla->cliente->empresa ?? 'Sin cliente' }}<br>
                {{ $planilla->codigo_limpio }} - S:{{ $planilla->seccion }}
            </h2>
            <h3 class="text-lg font-semibold text-gray-900">
                <span class="etiqueta-codigo">{{ $etiqueta->etiqueta_sub_id }}</span>
                @if($etiqueta->paquete)
                    <span class="paquete-codigo text-purple-600 font-bold">({{ $etiqueta->paquete->codigo }})</span>
                @else
                    <span class="paquete-codigo text-purple-600 font-bold" style="display: none;"></span>
                @endif
                - {{ $etiqueta->nombre ?? 'Sin nombre' }} - Cal:B500SD -
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

        // Paso 1: Identificar etiquetas que no están en el DOM
        const idsNoEncontrados = [];
        const idsEncontrados = [];

        for (const rawId of ids) {
            const safeId = window.domSafe(rawId);
            const contenedor = document.getElementById(`etiqueta-${safeId}`) ||
                document.getElementById(`etiqueta-${rawId}`);
            if (contenedor) {
                idsEncontrados.push(rawId);
            } else {
                idsNoEncontrados.push(rawId);
            }
        }

        // Paso 2: Si hay etiquetas no encontradas, obtenerlas del servidor
        let etiquetasDelServidor = {};
        if (idsNoEncontrados.length > 0) {
            console.log(`🖨️ Obteniendo ${idsNoEncontrados.length} etiquetas del servidor para impresión...`);
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const resp = await fetch('/etiquetas/render-multiple', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        etiqueta_sub_ids: idsNoEncontrados,
                        maquina_tipo: window.MAQUINA_TIPO || 'barra',
                    }),
                });

                if (resp.ok) {
                    const data = await resp.json();
                    if (data.success && data.etiquetas) {
                        data.etiquetas.forEach(et => {
                            if (et.found) {
                                etiquetasDelServidor[et.etiqueta_sub_id] = et;
                            }
                        });
                        console.log(`✅ Obtenidas ${Object.keys(etiquetasDelServidor).length} etiquetas del servidor`);
                    }
                }
            } catch (e) {
                console.warn('Error al obtener etiquetas del servidor:', e);
            }
        }

        for (const rawId of ids) {
            const safeId = window.domSafe(rawId);
            let contenedor = document.getElementById(`etiqueta-${safeId}`) ||
                document.getElementById(`etiqueta-${rawId}`);

            // Si no está en DOM, intentar usar datos del servidor
            let contenedorTemporal = null;
            if (!contenedor && etiquetasDelServidor[rawId]) {
                const etData = etiquetasDelServidor[rawId];
                // Crear contenedor temporal con el HTML del servidor
                contenedorTemporal = document.createElement('div');
                contenedorTemporal.innerHTML = etData.html;
                contenedorTemporal.style.position = 'absolute';
                contenedorTemporal.style.left = '-9999px';
                contenedorTemporal.style.top = '-9999px';
                document.body.appendChild(contenedorTemporal);

                // Buscar el contenedor de la etiqueta dentro del HTML renderizado
                contenedor = contenedorTemporal.querySelector('.etiqueta-card') ||
                             contenedorTemporal.querySelector('.proceso');

                // Renderizar SVG si hay datos de elementos
                if (contenedor && etData.elementos && etData.elementos.length > 0) {
                    const svgContainer = contenedor.querySelector('[id^="contenedor-svg-"]');

                    if (svgContainer && typeof window.renderizarGrupoSVG === 'function') {
                        // Usar la función completa de renderizado si está disponible
                        // Extraer el ID del contenedor (ej: "contenedor-svg-123" -> 123)
                        const containerId = svgContainer.id.replace('contenedor-svg-', '');

                        // Construir objeto grupo en el formato esperado por renderizarGrupoSVG
                        const grupoData = {
                            id: parseInt(containerId) || Date.now(),
                            etiqueta: {
                                id: parseInt(containerId) || Date.now(),
                                etiqueta_sub_id: rawId,
                            },
                            elementos: etData.elementos,
                            colada_etiqueta: etData.elementos[0]?.coladas?.colada1 || null,
                            colada_etiqueta_2: etData.elementos[0]?.coladas?.colada2 || null,
                        };

                        try {
                            window.renderizarGrupoSVG(grupoData, containerId);
                            console.log(`✅ SVG renderizado con renderizarGrupoSVG para ${rawId}`);
                        } catch (e) {
                            console.warn(`Error renderizando SVG con función completa:`, e);
                            // Fallback al SVG básico
                            const svg = crearSVGBasicoParaImpresion(etData.elementos, etData.data);
                            svgContainer.innerHTML = '';
                            svgContainer.appendChild(svg);
                        }
                    } else if (svgContainer) {
                        // Fallback: crear SVG básico con datos de elementos
                        const svg = crearSVGBasicoParaImpresion(etData.elementos, etData.data);
                        svgContainer.innerHTML = '';
                        svgContainer.appendChild(svg);
                    }
                }
            }

            if (!contenedor) continue;

            // Buscar SVG primero (sistema actual), luego canvas como fallback
            let svgElement = contenedor.querySelector('svg');
            let figuraImg = null;

            if (svgElement) {
                // Convertir SVG a imagen
                try {
                    // Clonar SVG para no modificar el original
                    const svgClone = svgElement.cloneNode(true);

                    // Obtener dimensiones reales
                    const bbox = svgElement.getBoundingClientRect();
                    const width = bbox.width || svgElement.getAttribute('width') || 600;
                    const height = bbox.height || svgElement.getAttribute('height') || 150;

                    // Establecer dimensiones explícitas en el SVG clonado
                    svgClone.setAttribute('width', width);
                    svgClone.setAttribute('height', height);
                    if (!svgClone.getAttribute('viewBox')) {
                        svgClone.setAttribute('viewBox', `0 0 ${width} ${height}`);
                    }

                    // FORZAR fondo blanco: eliminar cualquier estilo de fondo existente
                    svgClone.style.background = '#ffffff';
                    svgClone.style.backgroundColor = '#ffffff';
                    svgClone.removeAttribute('style');

                    // Añadir rectángulo blanco al principio para cubrir cualquier fondo
                    const bgRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                    bgRect.setAttribute('x', '0');
                    bgRect.setAttribute('y', '0');
                    bgRect.setAttribute('width', width);
                    bgRect.setAttribute('height', height);
                    bgRect.setAttribute('fill', '#ffffff');
                    svgClone.insertBefore(bgRect, svgClone.firstChild);

                    const svgData = new XMLSerializer().serializeToString(svgClone);
                    const svgBlob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
                    const svgUrl = URL.createObjectURL(svgBlob);

                    // Crear imagen del SVG
                    figuraImg = await new Promise((resolve) => {
                        const img = new Image();
                        img.onload = () => {
                            // Renderizar a canvas para mejor calidad de impresión
                            // Scale 4 para alta resolución en impresión
                            const scale = 4;
                            const canvas = document.createElement('canvas');
                            canvas.width = width * scale;
                            canvas.height = height * scale;
                            const ctx = canvas.getContext('2d');
                            // Mejorar calidad de renderizado
                            ctx.imageSmoothingEnabled = true;
                            ctx.imageSmoothingQuality = 'high';
                            ctx.scale(scale, scale);
                            ctx.fillStyle = '#ffffff';
                            ctx.fillRect(0, 0, width, height);
                            ctx.drawImage(img, 0, 0, width, height);
                            URL.revokeObjectURL(svgUrl);
                            resolve(canvas.toDataURL('image/png', 1.0));
                        };
                        img.onerror = () => {
                            URL.revokeObjectURL(svgUrl);
                            resolve(null);
                        };
                        img.src = svgUrl;
                    });
                } catch (e) {
                    console.warn('Error al convertir SVG:', e);
                }
            }

            // Fallback: buscar canvas
            if (!figuraImg) {
                let canvas = document.getElementById(`canvas-imprimir-etiqueta-${safeId}`) ||
                    document.getElementById(`canvas-imprimir-etiqueta-${rawId}`) ||
                    contenedor.querySelector('canvas');

                if (canvas && (canvas.width || canvas.height)) {
                    const scale = 4;
                    const tmp = document.createElement('canvas');
                    const w = canvas.width || canvas.getBoundingClientRect().width || 600;
                    const h = canvas.height || canvas.getBoundingClientRect().height || 200;
                    tmp.width = Math.max(1, Math.round(w * scale));
                    tmp.height = Math.max(1, Math.round(h * scale));
                    const ctx = tmp.getContext('2d');
                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';
                    ctx.scale(scale, scale);
                    ctx.drawImage(canvas, 0, 0);
                    figuraImg = tmp.toDataURL('image/png', 1.0);
                }
            }

            // Clonar y limpiar
            const clone = contenedor.cloneNode(true);
            clone.classList.add('etiqueta-print');
            clone.querySelectorAll('.no-print').forEach(el => el.remove());

            // Reemplazar SVG/canvas con imagen
            if (figuraImg) {
                // Remover SVG y canvas del clon
                const targetSvg = clone.querySelector('svg');
                const targetCanvas = clone.querySelector('canvas');
                const svgContainer = clone.querySelector('[id^="contenedor-svg-"]');

                const host = svgContainer || (targetSvg ? targetSvg.parentNode : (targetCanvas ? targetCanvas.parentNode : clone));

                if (targetSvg) targetSvg.remove();
                if (targetCanvas) targetCanvas.remove();

                // Añadir imagen
                const img = new Image();
                img.src = figuraImg;
                img.style.width = '100%';
                img.style.height = 'auto';
                img.className = 'figura-print';
                if (host) {
                    host.innerHTML = '';
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

            // Limpiar contenedor temporal si se usó
            if (contenedorTemporal) {
                contenedorTemporal.remove();
            }
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
  }

  .sheet-grid {
    margin: 0;
    padding: 0;
  }

  .etiqueta-print {
    width: 148mm;
    height: 105mm;
    padding: 4mm;
    box-sizing: border-box;
    border: 0.2mm solid #000;
    background: #fff;
    overflow: hidden;
    position: relative;
    page-break-after: always;
    display: flex;
    flex-direction: column;
  }

  .etiqueta-print:first-child {
    margin-top: 0;
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
    flex: 1;
    object-fit: contain;
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
        if (!w) {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo abrir la ventana de impresión',
                text: 'Por favor, desactiva el bloqueador de ventanas emergentes para este sitio e inténtalo de nuevo.',
            });
            return;
        }
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

    /**
     * Crea un SVG básico para impresión cuando las funciones de renderizado completas no están disponibles.
     * Se usa cuando la etiqueta se obtiene del servidor y no está en el DOM.
     */
    function crearSVGBasicoParaImpresion(elementos, datosEtiqueta) {
        const ancho = 600;
        const alto = 150;

        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', `0 0 ${ancho} ${alto}`);
        svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
        svg.style.width = '100%';
        svg.style.height = '100%';
        svg.style.background = '#ffffff';

        // Fondo blanco
        const bgRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        bgRect.setAttribute('x', '0');
        bgRect.setAttribute('y', '0');
        bgRect.setAttribute('width', ancho);
        bgRect.setAttribute('height', alto);
        bgRect.setAttribute('fill', '#ffffff');
        svg.appendChild(bgRect);

        // Crear leyenda con información de elementos
        const legendY = alto - 15;
        let xPos = 10;
        const letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        elementos.forEach((elem, idx) => {
            const letra = letras[idx] || `(${idx + 1})`;
            const diametro = elem.diametro || '?';
            const barras = elem.barras || 0;

            // Construir texto de coladas
            const coladas = [];
            if (elem.coladas?.colada1) coladas.push(elem.coladas.colada1);
            if (elem.coladas?.colada2) coladas.push(elem.coladas.colada2);
            if (elem.coladas?.colada3) coladas.push(elem.coladas.colada3);
            const coladaTexto = coladas.length > 0 ? ` (${coladas.join(', ')})` : '';

            const texto = `${letra}: Ø${diametro} x${barras}${coladaTexto}`;

            const textEl = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            textEl.setAttribute('x', xPos);
            textEl.setAttribute('y', legendY);
            textEl.setAttribute('font-size', '10');
            textEl.setAttribute('fill', '#333');
            textEl.setAttribute('font-family', 'Arial, sans-serif');
            textEl.textContent = texto;
            svg.appendChild(textEl);

            xPos += texto.length * 6 + 15; // Aproximar ancho del texto
        });

        // Mensaje indicando que es una etiqueta renderizada desde servidor
        const infoText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        infoText.setAttribute('x', ancho / 2);
        infoText.setAttribute('y', alto / 2);
        infoText.setAttribute('text-anchor', 'middle');
        infoText.setAttribute('font-size', '12');
        infoText.setAttribute('fill', '#666');
        infoText.textContent = `${datosEtiqueta?.nombre || 'Etiqueta'} - ${elementos.length} elemento(s)`;
        svg.appendChild(infoText);

        return svg;
    }

    // Exponer la función globalmente para uso desde otros scripts
    window.imprimirEtiquetas = imprimirEtiquetas;

</script>
