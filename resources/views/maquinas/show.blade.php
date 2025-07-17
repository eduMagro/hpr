<x-app-layout>
    <x-slot name="title">{{ $maquina->nombre }} - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-2 sm:mb-0">
                <strong>{{ $maquina->nombre }}</strong>,
                {{ $usuario1->name }}
                @if ($usuario2)
                    y {{ $usuario2->name }}
                @endif
            </h2>

            @if ($turnoHoy)
                <form method="POST" action="{{ route('turno.cambiarMaquina') }}" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="asignacion_id" value="{{ $turnoHoy->id }}">

                    <select name="nueva_maquina_id" class="border rounded px-2 py-1 text-sm">
                        @foreach ($maquinas as $m)
                            <option value="{{ $m->id }}" {{ $m->id == $turnoHoy->maquina_id ? 'selected' : '' }}>
                                {{ $m->nombre }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">
                        Cambiar máquina
                    </button>
                </form>
            @endif
        </div>
    </x-slot>
    <div class="w-full sm:px-4 py-6">
        <!-- Grid principal -->
        <div class="grid grid-cols-1 sm:grid-cols-8 gap-6">
            @if ($maquina->tipo === 'grua')
                {{-- <x-maquinas.tipo.tipo-grua :movimientosPendientes="$movimientosPendientes" :ubicaciones="$ubicaciones" :paquetes="$paquetes" /> --}}
                <x-maquinas.tipo.tipo-grua :movimientosPendientes="$movimientosPendientes" :movimientosCompletados="$movimientosCompletados" :ubicacionesDisponiblesPorProductoBase="$ubicacionesDisponiblesPorProductoBase" />

                @include('components.maquinas.modales.grua.modales-grua')
            @elseif ($maquina->tipo === 'cortadora_manual')
                <x-maquinas.tipo.tipo-cortadora-manual :maquina="$maquina" :materiaPrima="$materiaPrima" />
            @else
                <x-maquinas.tipo.tipo-normal :maquina="$maquina" :maquinas="$maquinas" :elementosAgrupados="$elementosAgrupados" :productosBaseCompatibles="$productosBaseCompatibles" />

                @include('components.maquinas.modales.normal.modales-normal')
            @endif

        </div>

        <!-- SCRIPT PARA IMPRIMIR QR -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script src="{{ asset('js/maquinaJS/trabajoEtiqueta.js') }}"></script>
        <script src="{{ asset('js/imprimirQrS.js') }}"></script>
        <script>
            window.elementosAgrupadosScript = @json($elementosAgrupadosScript ?? null);
        </script>

        <script src="{{ asset('js/maquinaJS/canvasMaquina.js') }}"></script>
        <script src="{{ asset('js/maquinaJS/canvasMaquinaSinBoton.js') }}" defer></script>

        <script src="{{ asset('js/maquinaJS/crearPaquetes.js') }}" defer></script>
        {{-- Al final del archivo Blade --}}

        <script>
            function imprimirEtiqueta(etiquetaSubId) {
                const canvas = document.getElementById(`canvas-imprimir-etiqueta-${etiquetaSubId}`);
                if (!canvas) {
                    alert("No se encontró el canvas de impresión limpio.");
                    return;
                }

                // Aumentamos tamaño del canvas para impresión (doble escala)
                const scaleFactor = 2;
                const tempCanvas = document.createElement("canvas");
                tempCanvas.width = canvas.width * scaleFactor;
                tempCanvas.height = canvas.height * scaleFactor;
                const ctx = tempCanvas.getContext("2d");
                ctx.scale(scaleFactor, scaleFactor);
                ctx.drawImage(canvas, 0, 0);

                const canvasImg = tempCanvas.toDataURL("image/png");

                // Clonar contenedor de etiqueta
                const contenedor = document.getElementById(`etiqueta-${etiquetaSubId}`);
                const clone = contenedor.cloneNode(true);
                clone.classList.add("etiqueta-print");

                // Quitar botones u otros elementos no imprimibles
                clone.querySelectorAll(".no-print").forEach(el => el.remove());

                // Reemplazar canvas por imagen generada
                const img = new Image();
                img.src = canvasImg;
                img.style.width = "100%";
                img.style.height = "auto";
                const canvasContainer = clone.querySelector("canvas").parentNode;
                canvasContainer.innerHTML = "";
                canvasContainer.appendChild(img);

                // Crear QR en div temporal
                const tempQR = document.createElement("div");
                document.body.appendChild(tempQR);
                const qrSize = 100;

                new QRCode(tempQR, {
                    text: etiquetaSubId.toString(),
                    width: qrSize,
                    height: qrSize
                });

                setTimeout(() => {
                    const qrImg = tempQR.querySelector("img");
                    if (qrImg) {
                        const qrWrapper = document.createElement("div");
                        qrWrapper.className = "qr-box";
                        qrWrapper.appendChild(qrImg);
                        clone.insertBefore(qrWrapper, clone.firstChild);
                    }

                    const style = `
<style>
    @media print {
        body {
            margin: 0;
            padding: 0;
        }
    }

    .etiqueta-print {
        width: 16cm;
        margin: 2cm auto;
        padding: 1.5cm;
        border: 2px solid #000;
        font-family: Arial, sans-serif;
        font-size: 15px;
        color: #000;
        box-sizing: border-box;
    }

    .etiqueta-print > * {
        padding: 6px;
        box-sizing: border-box;
    }

    .etiqueta-print h2 {
        font-size: 22px;
        margin-bottom: 8px;
    }

    .etiqueta-print h3 {
        font-size: 18px;
        margin-bottom: 6px;
    }

    .etiqueta-print p,
    .etiqueta-print span,
    .etiqueta-print strong {
        font-size: 15px;
    }

    .etiqueta-print img:not(.qr-print) {
        width: 100%;
        height: auto;
        display: block;
        margin-top: 14px;
    }

    .qr-box {
        float: right;
        margin-left: 14px;
        margin-bottom: 14px;
        border: 2px solid #000;
        padding: 6px;
    }

    .qr-box img {
        width: 100px;
        height: 100px;
    }

    .proceso {
        box-shadow: none;
        border: none;
        padding: 0;
    }

    .no-print {
        display: none !important;
    }
</style>
`;

                    const printWindow = window.open("", "_blank");
                    printWindow.document.open();
                    printWindow.document.write(`
<html>
<head>
    <title>Etiqueta ${etiquetaSubId}</title>
    ${style}
</head>
<body>
    ${clone.outerHTML}
  <script>
    window.onload = () => {
        const images = document.images;
        let loadedImages = 0;
        const totalImages = images.length;

        if (totalImages === 0) {
            window.print();
            setTimeout(() => window.close(), 1000);
            return;
        }

        for (const img of images) {
            if (img.complete) {
                loadedImages++;
            } else {
                img.onload = img.onerror = () => {
                    loadedImages++;
                    if (loadedImages === totalImages) {
                        window.print();
                        setTimeout(() => window.close(), 1000);
                    }
                };
            }
        }

        if (loadedImages === totalImages) {
            window.print();
            setTimeout(() => window.close(), 1000);
        }
    };
<\/script>


</body>
</html>
`);
                    printWindow.document.close();
                    tempQR.remove();
                }, 300);
            }

            async function imprimirEtiquetasLote(etiquetaIds) {
                await new Promise(resolve => {
                    // simular proceso de carga real:
                    setTimeout(resolve, 5000); // simula 5 segundos
                });
                const etiquetas = [];

                for (const id of etiquetaIds) {
                    const canvas = document.getElementById(`canvas-imprimir-etiqueta-${id}`);
                    const contenedor = document.getElementById(`etiqueta-${id}`);

                    if (!canvas || !contenedor) continue;

                    const scaleFactor = 2;
                    const tempCanvas = document.createElement("canvas");
                    tempCanvas.width = canvas.width * scaleFactor;
                    tempCanvas.height = canvas.height * scaleFactor;
                    const ctx = tempCanvas.getContext("2d");
                    ctx.scale(scaleFactor, scaleFactor);
                    ctx.drawImage(canvas, 0, 0);
                    const canvasImg = tempCanvas.toDataURL("image/png");

                    const clone = contenedor.cloneNode(true);
                    clone.classList.add("etiqueta-print");
                    clone.querySelectorAll(".no-print").forEach(el => el.remove());

                    const img = new Image();
                    img.src = canvasImg;
                    img.style.width = "100%";
                    img.style.height = "auto";
                    const canvasContainer = clone.querySelector("canvas").parentNode;
                    canvasContainer.innerHTML = "";
                    canvasContainer.appendChild(img);

                    const tempQR = document.createElement("div");
                    document.body.appendChild(tempQR);

                    await new Promise(resolve => {
                        new QRCode(tempQR, {
                            text: id.toString(),
                            width: 100,
                            height: 100
                        });

                        setTimeout(() => {
                            const qrImg = tempQR.querySelector("img");
                            if (qrImg) {
                                const qrWrapper = document.createElement("div");
                                qrWrapper.className = "qr-box";
                                qrWrapper.appendChild(qrImg);
                                clone.insertBefore(qrWrapper, clone.firstChild);
                            }

                            etiquetas.push(clone.outerHTML);
                            tempQR.remove();
                            resolve();
                        }, 300);
                    });
                }

                const style = `
        <style>
            @media print {
                body {
                    margin: 0;
                    padding: 0;
                }
            }

          .etiqueta-print {
            width: 16cm;
            margin: 1cm auto;
            padding: 1.5cm;
            border: 2px solid #000;
            font-family: Arial, sans-serif;
            font-size: 15px;
            color: #000;
            box-sizing: border-box;
            /* 🔴 Esta línea la tienes que eliminar: */
            /* page-break-after: always; */
             break-inside: avoid; /* 👈 Esto evita que se parta entre páginas */
        }

        .etiqueta-print + .etiqueta-print {
            margin-top: 1cm;
        }
            .etiqueta-print > * {
                padding: 6px;
                box-sizing: border-box;
            }

            .etiqueta-print h2 {
                font-size: 22px;
                margin-bottom: 8px;
            }

            .etiqueta-print h3 {
                font-size: 18px;
                margin-bottom: 6px;
            }

            .etiqueta-print p,
            .etiqueta-print span,
            .etiqueta-print strong {
                font-size: 15px;
            }

            .etiqueta-print img:not(.qr-print) {
                width: 100%;
                height: auto;
                display: block;
                margin-top: 14px;
            }

            .qr-box {
                float: right;
                margin-left: 14px;
                margin-bottom: 14px;
                border: 2px solid #000;
                padding: 6px;
            }

            .qr-box img {
                width: 140px;
                height: 140px;
            }

            .proceso {
                box-shadow: none;
                border: none;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }
        </style>`;

                const printWindow = window.open("", "_blank");
                printWindow.document.open();
                printWindow.document.write(`
<html>
<head>
    <title>Etiquetas</title>
    ${style}
</head>
<body>
    ${etiquetas.join('')}
   <script>
    window.onload = () => {
        const images = document.images;
        let loaded = 0;
        const total = images.length;

        if (total === 0) {
            window.print();
            setTimeout(() => window.close(), 1000);
            return;
        }

        for (const img of images) {
            if (img.complete) {
                loaded++;
            } else {
                img.onload = img.onerror = () => {
                    loaded++;
                    if (loaded === total) {
                        window.print();
                        setTimeout(() => window.close(), 1000);
                    }
                };
            }
        }

        if (loaded === total) {
            window.print();
            setTimeout(() => window.close(), 1000);
        }
    };
<\/script>

</body>
</html>`);
                printWindow.document.close();
            }
        </script>


</x-app-layout>
