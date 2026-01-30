/**
 * Función unificada para imprimir etiquetas
 * Se usa en etiquetas/index y maquinas/show (componente etiqueta)
 *
 * Soporta:
 * - Impresión individual o múltiple
 * - Modos A4 (grid 2x5) y A6 (landscape individual)
 * - Conversión SVG a imagen HD para impresión
 * - QR con código de subetiqueta
 * - Fetch de etiquetas desde servidor si no están en DOM
 */
// Utilidad para sanitizar IDs en selectores DOM
if (!window.domSafe) {
    window.domSafe = (v) => String(v).replace(/[^A-Za-z0-9_-]/g, '-');
}

/**
 * Convierte un SVG a imagen PNG en alta resolución
 * @param {SVGElement} svg - Elemento SVG a convertir
 * @returns {Promise<string|null>} - Data URL de la imagen o null si falla
 */
async function convertirSVGaImagen(svg) {
    if (!svg) return null;

    try {
        const svgClone = svg.cloneNode(true);
        const bbox = svg.getBoundingClientRect();
        const width = bbox.width || svg.getAttribute('width') || 600;
        const height = bbox.height || svg.getAttribute('height') || 150;

        svgClone.setAttribute('width', width);
        svgClone.setAttribute('height', height);
        if (!svgClone.getAttribute('viewBox')) {
            svgClone.setAttribute('viewBox', `0 0 ${width} ${height}`);
        }

        // Fondo blanco
        svgClone.style.background = '#ffffff';
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

        return await new Promise((resolve) => {
            const img = new Image();
            img.onload = () => {
                const scale = 4;
                const canvas = document.createElement('canvas');
                canvas.width = width * scale;
                canvas.height = height * scale;
                const ctx = canvas.getContext('2d');
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
        return null;
    }
}

/**
 * Genera QR y devuelve data URL de la imagen
 * @param {string} texto - Texto para el QR
 * @param {number} size - Tamaño del QR en px
 * @returns {Promise<string>} - Data URL de la imagen QR
 */
async function generarQRDataUrl(texto, size = 60) {
    return new Promise((resolve) => {
        const tempQR = document.createElement('div');
        tempQR.style.position = 'absolute';
        tempQR.style.left = '-9999px';
        document.body.appendChild(tempQR);

        new QRCode(tempQR, {
            text: String(texto),
            width: size,
            height: size
        });

        setTimeout(() => {
            const qrImg = tempQR.querySelector('img');
            const qrCanvas = tempQR.querySelector('canvas');

            let dataUrl = '';
            if (qrImg && qrImg.src) {
                dataUrl = qrImg.src;
            } else if (qrCanvas) {
                dataUrl = qrCanvas.toDataURL();
            }

            tempQR.remove();
            resolve(dataUrl);
        }, 150);
    });
}

/**
 * Obtiene los estilos CSS para impresión según el modo
 * @param {string} modo - 'a4' o 'a6'
 * @returns {string} - Estilos CSS
 */
function getEstilosImpresion(modo) {
    if (modo === 'a4') {
        return `<style>
@page { size: A4 portrait; margin: 10mm; }
body { margin: 0; padding: 0; background: #fff; }
.sheet-grid {
    display: grid;
    grid-template-columns: 105mm 105mm;
    grid-template-rows: repeat(5, 59.4mm);
    width: 210mm;
    height: 297mm;
}
.etiqueta-print {
    position: relative;
    width: 105mm;
    height: 59.4mm;
    box-sizing: border-box;
    border: 0.2mm solid #000;
    overflow: hidden;
    padding: 3mm;
    background: #fff;
    page-break-inside: avoid;
}
.etiqueta-print h2 { font-size: 10pt; margin: 0; }
.etiqueta-print h3 { font-size: 9pt; margin: 0; }
.qr-box { position: absolute; top: 3mm; right: 3mm; border: 0.2mm solid #000; padding: 1mm; background: #fff; text-align: center; }
.qr-box img { width: 16mm; height: 16mm; display: block; }
.qr-label { font-size: 6pt; font-weight: bold; margin-top: 0.5mm; word-break: break-all; max-width: 16mm; }
.no-print { display: none !important; }
</style>`;
    }

    // Default: A6 landscape
    return `<style>
@page { size: A6 landscape; margin: 0; }
html, body { margin: 0; padding: 0; background: #fff; }
.sheet-grid { margin: 0; padding: 0; }
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
}
.etiqueta-print h2 { font-size: 11pt; margin: 0 0 2mm 0; line-height: 1.3; }
.etiqueta-print h3 { font-size: 10pt; margin: 0 0 2mm 0; }
.qr-box { position: absolute; top: 4mm; right: 4mm; border: 0.2mm solid #000; padding: 1mm; background: #fff; text-align: center; }
.qr-box img { width: 20mm; height: 20mm; display: block; }
.qr-label { font-size: 7pt; font-weight: bold; margin-top: 0.5mm; word-break: break-all; max-width: 20mm; }
.no-print { display: none !important; }
</style>`;
}

/**
 * Obtiene etiquetas del servidor que no están en el DOM
 * @param {string[]} ids - IDs de etiquetas a obtener
 * @returns {Promise<Object>} - Mapa de id -> datos de etiqueta
 */
async function fetchEtiquetasServidor(ids) {
    if (!ids || ids.length === 0) return {};

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
                etiqueta_sub_ids: ids,
                maquina_tipo: window.MAQUINA_TIPO || 'barra',
            }),
        });

        if (resp.ok) {
            const data = await resp.json();
            if (data.success && data.etiquetas) {
                const result = {};
                data.etiquetas.forEach(et => {
                    if (et.found) {
                        result[et.etiqueta_sub_id] = et;
                    }
                });
                return result;
            }
        }
    } catch (e) {
        console.warn('Error al obtener etiquetas del servidor:', e);
    }
    return {};
}

/**
 * Función principal para imprimir etiquetas
 * @param {string|string[]} ids - ID(s) de etiquetas a imprimir
 * @param {string} modo - 'a4' o 'a6' (default: 'a6')
 */
async function imprimirEtiquetas(ids, modo = 'a6') {
    if (!Array.isArray(ids)) ids = [ids];
    const etiquetasHtml = [];

    // Identificar etiquetas que no están en el DOM
    const idsNoEncontrados = [];
    for (const rawId of ids) {
        const safeId = window.domSafe(rawId);
        const contenedor = document.getElementById(`etiqueta-${safeId}`) ||
                          document.getElementById(`etiqueta-${rawId}`) ||
                          document.getElementById('modalContent');
        if (!contenedor) {
            idsNoEncontrados.push(rawId);
        }
    }

    // Obtener etiquetas del servidor si es necesario
    let etiquetasDelServidor = {};
    if (idsNoEncontrados.length > 0) {
        etiquetasDelServidor = await fetchEtiquetasServidor(idsNoEncontrados);
    }

    for (const rawId of ids) {
        const safeId = window.domSafe(rawId);

        // Buscar contenedor de la etiqueta
        let contenedor = document.getElementById(`etiqueta-${safeId}`) ||
                         document.getElementById(`etiqueta-${rawId}`) ||
                         document.getElementById('modalContent');

        // Si no está en DOM, usar datos del servidor
        let contenedorTemporal = null;
        if (!contenedor && etiquetasDelServidor[rawId]) {
            const etData = etiquetasDelServidor[rawId];
            contenedorTemporal = document.createElement('div');
            contenedorTemporal.innerHTML = etData.html;
            contenedorTemporal.style.position = 'absolute';
            contenedorTemporal.style.left = '-9999px';
            document.body.appendChild(contenedorTemporal);
            contenedor = contenedorTemporal.querySelector('.etiqueta-card') ||
                         contenedorTemporal.querySelector('.proceso');
        }

        if (!contenedor) {
            console.warn(`Contenedor no encontrado para etiqueta: ${rawId}`);
            continue;
        }

        // Convertir SVG a imagen
        const svg = contenedor.querySelector('svg');
        const figuraImg = await convertirSVGaImagen(svg);

        // Extraer textos del contenedor original
        const h2El = contenedor.querySelector('h2');
        const h3El = contenedor.querySelector('h3');
        const h2Text = h2El ? h2El.textContent : '';
        const h3Text = h3El ? h3El.textContent : '';

        // Generar QR
        const qrSize = modo === 'a4' ? 50 : 60;
        const qrDataUrl = await generarQRDataUrl(rawId, qrSize);

        // Construir HTML con posicionamiento absoluto para la figura en la parte inferior
        const altura = modo === 'a4' ? '59.4mm' : '105mm';
        const ancho = modo === 'a4' ? '105mm' : '148mm';
        const qrImgSize = modo === 'a4' ? '16mm' : '20mm';
        const padding = modo === 'a4' ? '3mm' : '4mm';
        const figuraAltura = modo === 'a4' ? '42mm' : '75mm'; // Altura reservada para la figura

        let html = `
            <div class="etiqueta-print" style="position:relative; width:${ancho}; height:${altura}; border:0.2mm solid #000; background:#fff; box-sizing:border-box; overflow:hidden;">
                <!-- QR en esquina superior derecha -->
                <div style="position:absolute; top:${padding}; right:${padding}; border:0.2mm solid #000; padding:1mm; background:#fff; text-align:center; z-index:10;">
                    <img src="${qrDataUrl}" style="width:${qrImgSize}; height:${qrImgSize}; display:block;">
                    <div style="font-size:6pt; font-weight:bold; margin-top:1mm;">${rawId}</div>
                </div>

                <!-- Textos en la parte superior -->
                <div style="position:absolute; top:${padding}; left:${padding}; right:calc(${qrImgSize} + ${padding} + 8mm);">
                    <h2 style="font-size:${modo === 'a4' ? '10pt' : '11pt'}; margin:0; line-height:1.3;">${h2Text}</h2>
                    <h3 style="font-size:${modo === 'a4' ? '9pt' : '10pt'}; margin:2mm 0 0 0;">${h3Text}</h3>
                </div>

                <!-- FIGURA EN LA PARTE INFERIOR - posición absoluta -->
                ${figuraImg ? `
                <div style="position:absolute; bottom:${padding}; left:${padding}; right:${padding}; height:${figuraAltura}; overflow:visible;">
                    <img src="${figuraImg}" style="width:100%; height:100%; object-fit:contain; object-position:center bottom; display:block;">
                </div>
                ` : ''}
            </div>
        `;

        etiquetasHtml.push(html);

        // Limpiar contenedor temporal
        if (contenedorTemporal) {
            contenedorTemporal.remove();
        }
    }

    if (etiquetasHtml.length === 0) {
        alert('No se encontraron etiquetas para imprimir');
        return;
    }

    // Abrir ventana de impresión
    const w = window.open('', '_blank');
    if (!w) {
        alert('No se pudo abrir la ventana de impresión. Desactiva el bloqueador de popups.');
        return;
    }

    const css = getEstilosImpresion(modo);

    w.document.open();
    w.document.write(`
<html>
<head><title>Impresión Etiquetas</title>${css}</head>
<body>
    <div class="sheet-grid">${etiquetasHtml.join('')}</div>
    <script>
        window.onload = () => {
            const imgs = document.images;
            let loaded = 0, total = imgs.length;
            if (total === 0) { window.print(); setTimeout(() => window.close(), 500); return; }
            for (const img of imgs) {
                if (img.complete) {
                    loaded++;
                    if (loaded === total) { window.print(); setTimeout(() => window.close(), 500); }
                } else {
                    img.onload = img.onerror = () => {
                        loaded++;
                        if (loaded === total) { window.print(); setTimeout(() => window.close(), 500); }
                    };
                }
            }
        };
    <\/script>
</body>
</html>`);
    w.document.close();
}

// Exportar a window para uso global
window.imprimirEtiquetas = imprimirEtiquetas;
window.convertirSVGaImagen = convertirSVGaImagen;
window.generarQRDataUrl = generarQRDataUrl;
