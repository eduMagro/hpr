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
        // Alinear a la izquierda, no centrar
        svgClone.setAttribute('preserveAspectRatio', 'xMinYMin meet');

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
 * Genera QR con etiqueta de texto debajo
 * @param {string} texto - Texto para el QR y la etiqueta
 * @param {number} size - Tamaño del QR en px
 * @returns {Promise<HTMLElement>} - Elemento contenedor del QR
 */
async function generarQRConLabel(texto, size = 60) {
    return new Promise((resolve) => {
        const tempQR = document.createElement('div');
        document.body.appendChild(tempQR);

        new QRCode(tempQR, {
            text: String(texto),
            width: size,
            height: size
        });

        setTimeout(() => {
            const qrImg = tempQR.querySelector('img');
            const qrCanvas = tempQR.querySelector('canvas');

            const qrBox = document.createElement('div');
            qrBox.className = 'qr-box';

            if (qrImg) {
                qrImg.classList.add('qr-print');
                qrBox.appendChild(qrImg.cloneNode(true));
            } else if (qrCanvas) {
                const img = new Image();
                img.src = qrCanvas.toDataURL();
                img.classList.add('qr-print');
                qrBox.appendChild(img);
            }

            // Label debajo del QR
            const qrLabel = document.createElement('div');
            qrLabel.className = 'qr-label';
            qrLabel.textContent = String(texto);
            qrBox.appendChild(qrLabel);

            tempQR.remove();
            resolve(qrBox);
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
    display: flex;
    flex-direction: column;
}
.etiqueta-print h2 { font-size: 10pt; margin: 0; }
.etiqueta-print h3 { font-size: 9pt; margin: 0; }
.etiqueta-print img:not(.qr-print) { width: 100%; height: auto; display: block; }
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
    display: flex;
    flex-direction: column;
}
.etiqueta-print h2 { font-size: 11pt; margin: 0 0 2mm 0; line-height: 1.3; }
.etiqueta-print h3 { font-size: 10pt; margin: 0 0 2mm 0; }
.etiqueta-print img:not(.qr-print) { width: 100%; height: auto; display: block; }
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

        // Clonar y limpiar contenido
        const clone = contenedor.cloneNode(true);
        clone.classList.add('etiqueta-print');
        clone.querySelectorAll('.no-print').forEach(el => el.remove());

        // Reemplazar SVG con imagen
        if (figuraImg) {
            const targetSvg = clone.querySelector('svg');
            const targetCanvas = clone.querySelector('canvas');
            const svgContainer = clone.querySelector('[id^="contenedor-svg-"]') ||
                                 clone.querySelector('div[style*="min-height"]');
            const host = svgContainer || (targetSvg ? targetSvg.parentNode : clone);

            if (targetSvg) targetSvg.remove();
            if (targetCanvas) targetCanvas.remove();

            const img = new Image();
            img.src = figuraImg;
            img.style.width = '100%';
            img.style.height = 'auto';
            img.style.display = 'block';
            img.className = 'figura-print';

            if (host) {
                host.innerHTML = '';
                host.appendChild(img);
                // Posicionar en la parte baja
                host.style.marginTop = 'auto';
            }
        }

        // Generar y añadir QR
        const qrBox = await generarQRConLabel(rawId, modo === 'a4' ? 50 : 60);
        clone.insertBefore(qrBox, clone.firstChild);

        etiquetasHtml.push(clone.outerHTML);

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
window.generarQRConLabel = generarQRConLabel;
