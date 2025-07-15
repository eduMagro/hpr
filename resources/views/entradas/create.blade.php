<x-app-layout>
    @if (auth()->user()->rol !== 'operario')
        <x-slot name="header">
            <h2 class="text-lg font-semibold text-gray-800">
                <a href="{{ route('entradas.index') }}" class="text-blue-600">
                    {{ __('Entradas') }}
                </a>
                <span class="mx-2">/</span>
                {{ __('Crear Entradas de Material') }}
            </h2>
        </x-slot>
    @endif

    <div class="container mx-auto px-0 py-4 sm:px-4 sm:py-6" x-data="{ paquetes: '1', peso: '', ubicacion: '{{ old('ubicacion', $ultimaUbicacionId) }}' }">

        <form id="inventarioForm" method="POST" action="{{ route('entradas.store') }}" style="display:none;">
            @csrf
            <input type="hidden" name="cantidad_paquetes" id="cantidad_paquetes_input">
            <input type="hidden" name="codigo" id="codigo_input">
            <input type="hidden" name="fabricante_id" id="fabricante_id_input">
            <input type="hidden" name="albaran" id="albaran_input">
            <input type="hidden" name="producto_base_id" id="producto_base_id_input">
            <input type="hidden" name="n_colada" id="n_colada_input">
            <input type="hidden" name="n_paquete" id="n_paquete_input">
            <input type="hidden" name="codigo_2" id="codigo_2_input">
            <input type="hidden" name="n_colada_2" id="n_colada_2_input">
            <input type="hidden" name="n_paquete_2" id="n_paquete_2_input">
            <input type="hidden" name="peso" id="peso_input">
            <input type="hidden" name="ubicacion" id="ubicacion_input">
        </form>
        {{-- <button onclick="iniciarRegistro()" class="bg-blue-500 text-white py-2 px-4 mt-4 rounded-lg hover:bg-blue-600">
            Registrar Entrada
        </button> --}}

    </div>

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const errores = `{!! implode('<br>', $errors->all()) !!}`;

                Swal.fire({
                    title: '⚠️ Errores de validación',
                    html: errores,
                    icon: 'error',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // ✅ Relanza el flujo solo después de ver los errores
                    iniciarRegistro();
                });
            });
        </script>
    @else
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                iniciarRegistro(); // 🚀 Llama directamente al flujo de SweetAlert al cargar
            });
        </script>
    @endif


    <script>
        const ubicaciones = @json($ubicaciones->pluck('nombre_sin_prefijo', 'id'));
        const fabricantes = @json($fabricantes->pluck('nombre', 'id'));

        async function iniciarRegistro() {
            try {
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

                // Código primer paquete
                const {
                    value: codigo
                } = await Swal.fire({
                    title: 'Código (escaneado)',
                    input: 'text',
                    inputPlaceholder: 'Escanea el código MP...',
                    inputValue: '',
                    showCancelButton: true,
                    inputValidator: (value) => !value && 'Código requerido'
                });
                if (!codigo) return;
                document.getElementById('codigo_input').value = codigo;

                // Fabricante
                const {
                    value: fabricante_id
                } = await Swal.fire({
                    title: 'Fabricante',
                    input: 'select',
                    inputOptions: fabricantes,
                    inputValue: '{{ $ultimoFabricanteId }}', // ✅ valor por defecto
                    inputPlaceholder: 'Selecciona fabricante',
                    showCancelButton: true,
                    inputValidator: (value) => !value && 'Selecciona fabricante'
                });
                if (!fabricante_id) return;
                document.getElementById('fabricante_id_input').value = fabricante_id;

                // Albarán
                document.getElementById('albaran_input').value = 'Entrada manual';

                // Producto base
                const productosBase = {
                    @foreach ($productosBase as $producto)
                        {{ $producto->id }}: '{{ strtoupper($producto->tipo) }} Ø{{ $producto->diametro }}{{ $producto->longitud ? ' | ' . $producto->longitud . 'm' : '' }}',
                    @endforeach
                };
                const {
                    value: producto_base_id
                } = await Swal.fire({
                    title: 'Producto base',
                    input: 'select',
                    inputOptions: productosBase,
                    inputValue: '{{ $ultimoProductoBaseId }}', // ✅ valor por defecto
                    inputPlaceholder: 'Selecciona producto base',
                    showCancelButton: true,
                    inputValidator: (value) => !value && 'Selecciona producto base'
                });

                if (!producto_base_id) return;
                document.getElementById('producto_base_id_input').value = producto_base_id;

                // Colada
                const {
                    value: n_colada
                } = await Swal.fire({
                    title: 'Número de colada',
                    input: 'text',
                    inputValue: '{{ $ultimaColada }}',
                    inputValidator: (value) => !value && 'Número de colada requerido'
                });
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
                if (!n_paquete) return;
                document.getElementById('n_paquete_input').value = n_paquete;

                // Si hay segundo paquete
                if (paquetes === '2') {
                    const {
                        value: codigo_2
                    } = await Swal.fire({
                        title: 'Código segundo paquete',
                        input: 'text',
                        inputValidator: (value) => !value && 'Código requerido'
                    });
                    if (!codigo_2) return;
                    document.getElementById('codigo_2_input').value = codigo_2;

                    const {
                        value: n_colada_2
                    } = await Swal.fire({
                        title: 'Colada segundo paquete',
                        input: 'text'
                    });
                    document.getElementById('n_colada_2_input').value = n_colada_2 || '';

                    const {
                        value: n_paquete_2
                    } = await Swal.fire({
                        title: 'Número segundo paquete',
                        input: 'number'
                    });
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
                if (!peso) return;
                document.getElementById('peso_input').value = peso;

                // 👉 Primero mostrar el select con la última ubicación seleccionada por defecto
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
          `<option value="${id}" ${id == '{{ $ultimaUbicacionId }}' ? 'selected' : ''}>${nombre}</option>`
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
                        // devuelve la seleccion inicial
                        return document.getElementById('swal-ubicacion').value;
                    }
                });
                if (!ubicacionSel) return;
                ubicacionElegida = ubicacionSel;

                // 👉 Ahora comprobamos si el usuario quiere escanear
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

                // 👉 Guardar el resultado final en el input oculto
                document.getElementById('ubicacion_input').value = ubicacionElegida;



                // ✅ Enviar
                document.getElementById('inventarioForm').submit();
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Ha ocurrido un error inesperado.', 'error');
            }
        }
    </script>

</x-app-layout>
