<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('pedidos.index') }}" class="text-blue-600">
                {{ __('Pedidos de Compra') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Recepción del ') }}{{ $pedido->codigo }}
        </h2>
    </x-slot>

    <div class="py-6">
        @php
            $producto = $productoBase;
            $productoActivo = old('producto_id') == $producto->id;

            $defecto = $ultimos[$producto->id] ?? null;
            $coladaPorDefecto = $defecto?->n_colada ?? null;
            $ubicacionPorDefecto = $defecto?->ubicacion_id ?? null;

            $entradaAbierta = $pedido->entradas()->where('estado', 'abierto')->with('productos')->latest()->first();

            $productosDeEstaEntrada = \App\Models\Producto::where('entrada_id', $entradaAbierta?->id)
                ->where('producto_base_id', $producto->id)
                ->with('productoBase')
                ->get();
        @endphp

        @if ($entradaAbierta && $productosDeEstaEntrada->isNotEmpty())
            <div class="bg-white border rounded shadow p-4 mb-6 max-w-4xl mx-auto">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-base font-semibold text-gray-800">
                        Albarán abierto: <span class="text-blue-600">{{ $entradaAbierta->albaran }}</span>
                    </h3>
                    <form id="cerrar-albaran-form" method="POST"
                        action="{{ route('entradas.cerrar', $entradaAbierta->id) }}" class="hidden">
                        @csrf
                        @method('PATCH')
                    </form>
                    <button onclick="confirmarCerrarAlbaran()"
                        class="bg-red-600 text-white text-xs px-3 py-1 rounded hover:bg-red-700">
                        Cerrar Albarán
                    </button>
                </div>

                <p class="text-sm text-gray-600 mb-3">
                    Total recepcionado: <strong>{{ number_format($entradaAbierta->peso_total, 2, ',', '.') }}
                        kg</strong>
                </p>

                <ul class="divide-y text-sm text-gray-800">
                    @foreach ($productosDeEstaEntrada as $prod)
                        <li class="py-2 flex justify-between">
                            <a href="javascript:void(0);" class="font-semibold uppercase text-blue-600 hover:underline"
                                onclick='editarProducto(@json($prod))'>
                                {{ $prod->codigo }}
                            </a>

                            <span>
                                {{ ucfirst($prod->productoBase->tipo ?? '-') }} /
                                Ø{{ $prod->productoBase->diametro ?? '-' }} mm —
                                {{ number_format($prod->peso_inicial, 2, ',', '.') }} kg
                            </span>
                        </li>
                    @endforeach

                </ul>
            </div>
        @endif


        <div class="text-center mt-4">
            <button onclick="iniciarRecepcion()"
                class="bg-green-600 text-white px-4 py-2 rounded-lg shadow hover:bg-green-700">
                ➕ Registrar nuevo paquete
            </button>
        </div>
        {{-- Formulario oculto --}}
        <form id="recepcionForm" method="POST"
            action="{{ route('pedidos.recepcion.guardar', ['pedido' => $pedido->id, 'producto_base' => $producto->id]) }}"
            style="display:none;">
            @csrf
            <input type="hidden" name="pedido_id" value="{{ $pedido->id }}">
            <input type="hidden" name="producto_base_id" value="{{ $producto->id }}">
            <input type="hidden" name="cantidad_paquetes" id="cantidad_paquetes_input">
            <input type="hidden" name="codigo" id="codigo_input">
            <input type="hidden" name="fabricante_manual" id="fabricante_id_input">
            <input type="hidden" name="n_colada" id="n_colada_input">
            <input type="hidden" name="n_paquete" id="n_paquete_input">
            <input type="hidden" name="codigo_2" id="codigo_2_input">
            <input type="hidden" name="n_colada_2" id="n_colada_2_input">
            <input type="hidden" name="n_paquete_2" id="n_paquete_2_input">
            <input type="hidden" name="peso" id="peso_input">
            <input type="hidden" name="ubicacion_id" id="ubicacion_input">
            <input type="hidden" name="otros" id="otros_input">
        </form>

    </div>


    <script>
        async function iniciarRecepcion() {
            try {
                console.log('🟢 Inicio flujo SweetAlert');

                // Paquetes
                const {
                    value: paquetes
                } = await Swal.fire({
                    title: '¿Cuántos paquetes?',
                    input: 'select',
                    inputOptions: {
                        '1': '1 paquete',
                        '2': '2 paquetes'
                    },
                    inputPlaceholder: 'Selecciona cantidad',
                    showCancelButton: true,
                    inputValidator: (value) => !value && 'Debes seleccionar una opción'
                });
                console.log('👉 paquetes', paquetes);
                if (!paquetes) return;
                document.getElementById('cantidad_paquetes_input').value = paquetes;

                // Código primer paquete
                const {
                    value: codigo
                } = await Swal.fire({
                    title: 'Código (escaneado)',
                    input: 'text',
                    inputPlaceholder: 'Escanea el código MP...',
                    inputValidator: (value) => !value && 'Código requerido'
                });
                console.log('👉 codigo', codigo);
                if (!codigo) return;
                document.getElementById('codigo_input').value = codigo;

                // Nº Colada
                const {
                    value: n_colada
                } = await Swal.fire({
                    title: 'Número de colada',
                    input: 'text',
                    inputValue: '{{ $coladaPorDefecto }}',
                    inputValidator: (value) => !value && 'Número de colada requerido'
                });
                console.log('👉 n_colada', n_colada);
                if (!n_colada) return;
                document.getElementById('n_colada_input').value = n_colada;

                // Nº Paquete
                const {
                    value: n_paquete
                } = await Swal.fire({
                    title: 'Número de paquete',
                    input: 'number',
                    inputValidator: (value) => !value && 'Número de paquete requerido'
                });
                console.log('👉 n_paquete', n_paquete);
                if (!n_paquete) return;
                document.getElementById('n_paquete_input').value = n_paquete;

                // Segundo paquete
                if (paquetes === '2') {
                    const {
                        value: codigo_2
                    } = await Swal.fire({
                        title: 'Código segundo paquete',
                        input: 'text',
                        inputValidator: (value) => !value && 'Código requerido'
                    });
                    console.log('👉 codigo_2', codigo_2);
                    if (!codigo_2) return;
                    document.getElementById('codigo_2_input').value = codigo_2;

                    const {
                        value: n_colada_2
                    } = await Swal.fire({
                        title: 'Colada segundo paquete',
                        input: 'text'
                    });
                    console.log('👉 n_colada_2', n_colada_2);
                    document.getElementById('n_colada_2_input').value = n_colada_2 || '';

                    const {
                        value: n_paquete_2
                    } = await Swal.fire({
                        title: 'Número segundo paquete',
                        input: 'number'
                    });
                    console.log('👉 n_paquete_2', n_paquete_2);
                    document.getElementById('n_paquete_2_input').value = n_paquete_2 || '';
                }

                // Peso
                const {
                    value: peso
                } = await Swal.fire({
                    title: 'Peso total (kg)',
                    input: 'number',
                    inputValidator: (value) => (value <= 0 ? 'Introduce un peso válido' : undefined)
                });
                console.log('👉 peso', peso);
                if (!peso) return;
                document.getElementById('peso_input').value = peso;

                // Ubicación
                const {
                    value: metodoUbicacion
                } = await Swal.fire({
                    title: '¿Cómo quieres introducir la ubicación?',
                    input: 'radio',
                    inputOptions: {
                        'select': 'Seleccionar de la lista',
                        'scan': 'Escanear código ubicación'
                    },
                    inputValidator: (value) => !value && 'Debes elegir un método',
                    showCancelButton: true,
                });
                console.log('👉 metodoUbicacion', metodoUbicacion);
                if (!metodoUbicacion) return;

                let ubicacionElegida = '';

                if (metodoUbicacion === 'select') {
                    const ubicaciones = @json($ubicaciones->pluck('nombre_sin_prefijo', 'id'));
                    console.log('👉 ubicaciones cargadas', ubicaciones);
                    const {
                        value: ubicacion
                    } = await Swal.fire({
                        title: 'Ubicación',
                        input: 'select',
                        inputOptions: ubicaciones,
                        inputPlaceholder: 'Selecciona ubicación',
                        inputValidator: (value) => !value && 'Selecciona ubicación'
                    });
                    console.log('👉 ubicacion', ubicacion);
                    if (!ubicacion) return;
                    ubicacionElegida = ubicacion;
                }

                if (metodoUbicacion === 'scan') {
                    const {
                        value: ubicacionScan
                    } = await Swal.fire({
                        title: 'Escanea la ubicación',
                        input: 'text',
                        inputPlaceholder: 'Escanea o introduce el código de ubicación',
                        inputValidator: (value) => !value && 'Debes introducir un código'
                    });
                    console.log('👉 ubicacionScan', ubicacionScan);
                    if (!ubicacionScan) return;
                    ubicacionElegida = ubicacionScan;
                }

                document.getElementById('ubicacion_input').value = ubicacionElegida;

                // Observaciones
                const {
                    value: otros
                } = await Swal.fire({
                    title: 'Observaciones',
                    input: 'text',
                    inputPlaceholder: 'Escribe observaciones (opcional)',
                    showCancelButton: true
                });
                console.log('👉 otros', otros);
                document.getElementById('otros_input').value = otros || '';

                // ✅ Enviar
                console.log('✅ Enviando formulario...');
                document.getElementById('recepcionForm').submit();

            } catch (e) {
                console.error('❌ Error capturado en catch:', e);
                Swal.fire('Error', 'Ha ocurrido un error inesperado.', 'error');
            }
        }

        function confirmarCerrarAlbaran() {
            Swal.fire({
                title: '¿Cerrar albarán?',
                text: "No podrás volver a editarlo después.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e3342f',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, cerrar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('cerrar-albaran-form').submit();
                }
            });
        }
    </script>
    <script>
        function verProducto(prod) {
            // Puedes personalizar los campos a mostrar:
            const html = `
            <div style="text-align:left;">
                <p><strong>Código:</strong> ${prod.codigo}</p>
                <p><strong>Tipo:</strong> ${prod.producto_base.tipo ?? ''}</p>
                <p><strong>Diámetro:</strong> Ø${prod.producto_base.diametro ?? ''} mm</p>
                <p><strong>Peso inicial:</strong> ${prod.peso_inicial} kg</p>
                <p><strong>Colada:</strong> ${prod.n_colada ?? '-'}</p>
                <p><strong>Nº paquete:</strong> ${prod.n_paquete ?? '-'}</p>
                <p><strong>Ubicación:</strong> ${prod.ubicacion_id ?? '-'}</p>
                <p><strong>Creado:</strong> ${prod.created_at}</p>
            </div>
        `;

            Swal.fire({
                title: `📦 Detalles`,
                html: html,
                confirmButtonText: 'Cerrar',
                customClass: {
                    popup: 'text-sm'
                }
            });
        }
    </script>
    <script>
        const fabricantesOptions = @json($fabricantes->pluck('nombre', 'id'));

        async function editarProducto(prod) {
            console.log('🟢 Abriendo modal para producto', prod);



            const formHtml = `
            <input id="swal-codigo" class="swal2-input" placeholder="Código" value="${prod.codigo || ''}">
            <input id="swal-colada" class="swal2-input" placeholder="Nº Colada" value="${prod.n_colada || ''}">
            <input id="swal-paquete" class="swal2-input" placeholder="Nº Paquete" value="${prod.n_paquete || ''}">
            <input id="swal-peso" class="swal2-input" type="number" step="0.01" placeholder="Peso inicial (kg)" value="${prod.peso_inicial || ''}">
           
            <input id="swal-ubicacion" class="swal2-input" placeholder="Ubicación" value="${prod.ubicacion_id || ''}">
        `;

            const {
                value: formValues
            } = await Swal.fire({
                title: 'Editar producto',
                html: formHtml,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: '💾 Guardar cambios',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    return {
                        codigo: document.getElementById('swal-codigo').value,
                        n_colada: document.getElementById('swal-colada').value,
                        n_paquete: document.getElementById('swal-paquete').value,
                        peso_inicial: document.getElementById('swal-peso').value,

                        ubicacion_id: document.getElementById('swal-ubicacion').value,
                    };
                }
            });

            if (formValues) {
                console.log('✅ Datos editados (POST):', formValues);

                // 👉 POST con _method: 'PUT'
                fetch(`/productos/${prod.id}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            ...formValues,
                            _method: 'PUT'
                        })
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Error al actualizar');
                        return res.json();
                    })
                    .then(data => {
                        Swal.fire('Guardado', 'El producto se actualizó correctamente.', 'success')
                            .then(() => location.reload());
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Error', 'No se pudo guardar.', 'error');
                    });
            }
        }
    </script>

</x-app-layout>
