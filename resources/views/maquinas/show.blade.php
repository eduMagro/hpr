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
                <x-maquinas.tipo.tipo-cortadora-manual :maquina="$maquina" :maquinas="$maquinas" :elementosAgrupados="$elementosAgrupados"
                    :productosBaseCompatibles="$productosBaseCompatibles" />
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
            window.rutaDividirElemento = "{{ route('elementos.dividir') }}";
        </script>

        <script src="{{ asset('js/maquinaJS/canvasMaquina.js') }}"></script>
        {{-- <script src="{{ asset('js/maquinaJS/canvasMaquinaSinBoton.js') }}" defer></script> --}}

        <script src="{{ asset('js/maquinaJS/crearPaquetes.js') }}" defer></script>
        {{-- Al final del archivo Blade --}}

        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script>
            async function imprimirEtiquetas(ids) {
                if (!Array.isArray(ids)) ids = [ids];

                const etiquetasHtml = [];

                for (const id of ids) {
                    const canvas = document.getElementById(`canvas-imprimir-etiqueta-${id}`);
                    const contenedor = document.getElementById(`etiqueta-${id}`);
                    if (!canvas || !contenedor) continue;

                    // Escalar canvas
                    const scale = 2;
                    const tmp = document.createElement('canvas');
                    tmp.width = canvas.width * scale;
                    tmp.height = canvas.height * scale;
                    const ctx = tmp.getContext('2d');
                    ctx.scale(scale, scale);
                    ctx.drawImage(canvas, 0, 0);
                    const canvasImg = tmp.toDataURL('image/png');

                    // Clonar
                    const clone = contenedor.cloneNode(true);
                    clone.classList.add('etiqueta-print');
                    clone.querySelectorAll('.no-print').forEach(el => el.remove());

                    // Reemplazar canvas
                    const canvasContainer = clone.querySelector('canvas')?.parentNode;
                    if (canvasContainer) {
                        canvasContainer.innerHTML = '';
                        const img = new Image();
                        img.src = canvasImg;
                        img.style.width = '100%';
                        img.style.height = 'auto';
                        canvasContainer.appendChild(img);
                    }

                    // Generar QR
                    const tempQR = document.createElement('div');
                    document.body.appendChild(tempQR);
                    await new Promise(res => {
                        new QRCode(tempQR, {
                            text: id.toString(),
                            width: 50,
                            height: 50
                        });
                        setTimeout(() => {
                            const qrImg = tempQR.querySelector('img');
                            if (qrImg) {
                                const qrBox = document.createElement('div');
                                qrBox.className = 'qr-box';
                                qrBox.appendChild(qrImg);
                                clone.insertBefore(qrBox, clone.firstChild);
                            }
                            tempQR.remove();
                            res();
                        }, 200);
                    });

                    etiquetasHtml.push(clone.outerHTML);
                }

                const css = `
    <style>
      @page{size:A4 portrait;margin:10;}
      body{margin:0;padding:0;background:#fff;}
      .sheet-grid{display:grid;grid-template-columns:105mm 105mm;grid-template-rows:repeat(5,59.4mm);width:210mm;height:297mm;}
      .etiqueta-print{position:relative;width:105mm;height:59.4mm;box-sizing:border-box;border:0.2mm solid #000;overflow:hidden;padding:3mm;background:#fff;page-break-inside:avoid;}
      .etiqueta-print h2{font-size:10pt;margin:0;}
      .etiqueta-print h3{font-size:9pt;margin:0;}
      .etiqueta-print img:not(.qr-print){width:100%;height:auto;margin-top:2mm;}
      .qr-box{position:absolute;top:3mm;right:3mm;border:0.2mm solid #000;padding:1mm;background:#fff;}
      .qr-box img{width:20mm;height:20mm;}
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
              if(img.complete){loaded++;if(loaded===total){window.print();setTimeout(()=>window.close(),500);}}
              else{img.onload=img.onerror=()=>{loaded++;if(loaded===total){window.print();setTimeout(()=>window.close(),500);}};}
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
        </style>

</x-app-layout>
