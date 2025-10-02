<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('maquinas.index') }}" class="text-blue-600">
                {{ __('Movimientos') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Recepción del ') }}{{ $pedido->codigo }}
        </h2>
    </x-slot>

    <div class="py-6">
        @php
            $producto = $productoBase;
            $defecto = $ultimos[$producto->id] ?? null;
            $coladaPorDefecto = $defecto?->n_colada ?? null;
            $ubicacionPorDefecto = $defecto?->ubicacion_id ?? null;

            $entradaAbierta = $pedido->entradas()->where('estado', 'abierto')->with('productos')->latest()->first();

            $productosDeEstaEntrada = \App\Models\Producto::where('entrada_id', $entradaAbierta?->id)
                ->where('producto_base_id', $producto->id)
                ->with('productoBase')
                ->get();

            // 👇 Cargamos el movimiento pendiente asociado a la línea actual
            $movimientoPendiente = $linea?->movimientos()->where('estado', 'pendiente')->first();
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
                        <input type="hidden" name="movimiento_id" value="{{ $movimientoPendiente?->id }}">
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
            {{-- se manda aún, pero backend debe fiarse del movimiento --}}
            <input type="hidden" name="pedido_producto_id" value="{{ $linea?->id }}">
            <input type="hidden" name="producto_base_id" value="{{ $producto->id }}">
            <input type="hidden" name="cantidad_paquetes" id="cantidad_paquetes_input">
            <input type="hidden" name="codigo" id="codigo_input">
            <input type="hidden" name="fabricante_id" id="fabricante_id_input">
            <input type="hidden" name="n_colada" id="n_colada_input">
            <input type="hidden" name="n_paquete" id="n_paquete_input">
            <input type="hidden" name="codigo_2" id="codigo_2_input">
            <input type="hidden" name="n_colada_2" id="n_colada_2_input">
            <input type="hidden" name="n_paquete_2" id="n_paquete_2_input">
            <input type="hidden" name="peso" id="peso_input">
            <input type="hidden" name="ubicacion_id" id="ubicacion_input">
            <input type="hidden" name="otros" id="otros_input">
            {{-- 👇 Aquí enviamos el movimiento_id también --}}
            <input type="hidden" name="movimiento_id" value="{{ $movimientoPendiente?->id }}">
        </form>
    </div>


    <script>
        const requiereFabricante = @json($requiereFabricanteManual);
        const fabricantes = @json($fabricantes->pluck('nombre', 'id'));
        const ultimoFabricanteId = @json($ultimoFabricante);

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
                    inputValue: '1', // 👈 Valor por defecto seleccionado
                    inputPlaceholder: 'Selecciona cantidad',
                    showCancelButton: true,
                    inputValidator: (value) => !value && 'Debes seleccionar una opción'
                });
                console.log('👉 paquetes', paquetes);
                if (!paquetes) return;
                document.getElementById('cantidad_paquetes_input').value = paquetes;
                console.log(requiereFabricante);
                console.log(ultimoFabricanteId);
                if (requiereFabricante) {
                    const {
                        value: fabricante_id
                    } = await Swal.fire({
                        title: 'Selecciona el fabricante',
                        input: 'select',
                        inputOptions: fabricantes,
                        inputValue: ultimoFabricanteId, // valor por defecto
                        inputPlaceholder: 'Selecciona un fabricante',
                        showCancelButton: true,
                        inputValidator: (value) => !value && 'Debes seleccionar un fabricante'
                    });

                    if (!fabricante_id) return;
                    document.getElementById('fabricante_id_input').value = fabricante_id;
                }

                // Código primer paquete
                const {
                    value: codigo
                } = await Swal.fire({
                    title: 'Código (escaneado)',
                    input: 'text',
                    inputPlaceholder: 'Escanea el código MP...',
                    inputValidator: (value) => {
                        const v = (value || '').trim();
                        if (!v) return 'Código requerido';
                        if (!/^mp/i.test(v)) return 'El código debe empezar por MP';
                        if (v.length > 20) return 'Máximo 20 caracteres';
                    }
                });
                if (!codigo) return;
                const codigoNorm = codigo.trim().toUpperCase();
                document.getElementById('codigo_input').value = codigoNorm;


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
                // Segundo paquete
                if (paquetes === '2') {
                    const {
                        value: codigo_2
                    } = await Swal.fire({
                        title: 'Código segundo paquete',
                        input: 'text',
                        inputValidator: (value) => {
                            const v = (value || '').trim();
                            if (!v) return 'Código requerido';
                            if (!/^mp/i.test(v)) return 'El código debe empezar por MP';
                            if (v.length > 20) return 'Máximo 20 caracteres';
                        }
                    });
                    if (!codigo_2) return;
                    const codigo2Norm = codigo_2.trim().toUpperCase();
                    document.getElementById('codigo_2_input').value = codigo2Norm;
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
                        title: 'Nº Paqute segundo paquete',
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
                let ubicacionElegida = '';
                const {
                    value: ubicacionSel
                } = await Swal.fire({
                    title: 'Selecciona ubicación',
                    html: `
    <div style="display:flex;flex-direction:column;align-items:stretch;gap:12px;text-align:left;">
      <label style="font-weight:600;font-size:14px;">Ubicación</label>
      <select id="swal-ubicacion" style="
        width:100%;
        padding:8px;
        border:1px solid #ccc;
        border-radius:4px;
        font-size:14px;
        box-sizing:border-box;
      ">
        ${Object.entries(@json($ubicaciones->pluck('nombre_sin_prefijo', 'id')))
          .map(([id,nombre]) =>
            `<option value="${id}" ${id == '{{ $ubicacionPorDefecto }}' ? 'selected' : ''}>${nombre}</option>`
          ).join('')}
      </select>
      <label style="display:flex;align-items:center;gap:6px;font-size:14px;margin-top:4px;">
        <input type="checkbox" id="swal-scan-checkbox" style="transform:scale(1.2);">
        Quiero escanear en su lugar
      </label>
    </div>
  `,
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'Continuar',
                    preConfirm: () => {
                        return document.getElementById('swal-ubicacion').value;
                    }
                });
                if (!ubicacionSel) return;
                ubicacionElegida = ubicacionSel;

                // Si marcó escanear
                const scanCheckbox = Swal.getPopup()?.querySelector('#swal-scan-checkbox');
                if (scanCheckbox && scanCheckbox.checked) {
                    const {
                        value: ubicacionScan
                    } = await Swal.fire({
                        title: 'Escanea la ubicación',
                        input: 'text',
                        inputPlaceholder: 'Escanea o introduce el código de ubicación',
                        inputValidator: (value) => !value && 'Debes introducir un código',
                        showCancelButton: true
                    });
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

            const fabricanteOptions = Object.entries(fabricantesOptions).map(([id, nombre]) => {
                const selected = id == prod.fabricante_id ? 'selected' : '';
                return `<option value="${id}" ${selected}>${nombre}</option>`;
            }).join('');

            const formHtml = `
                <input id="swal-codigo" class="swal2-input" placeholder="Código" value="${prod.codigo || ''}">
                <input id="swal-colada" class="swal2-input" placeholder="Nº Colada" value="${prod.n_colada || ''}">
                <input id="swal-paquete" class="swal2-input" placeholder="Nº Paquete" value="${prod.n_paquete || ''}">
                <input id="swal-peso" class="swal2-input" type="number" step="0.01" placeholder="Peso inicial (kg)" value="${prod.peso_inicial || ''}">
                <input id="swal-ubicacion" class="swal2-input" placeholder="Ubicación" value="${prod.ubicacion_id || ''}">
                <select id="swal-fabricante" class="swal2-input">
                    <option value="">Sin fabricante</option>
                    ${fabricanteOptions}
                </select>
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
                        fabricante_id: document.getElementById('swal-fabricante').value,
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
