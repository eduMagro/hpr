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
            <input type="hidden" name="ubicacion_id" id="ubicacion_input">
            {{-- ✅ NUEVO: obra/almacén --}}
            <input type="hidden" name="obra_id" id="obra_id_input">
        </form>
        {{-- <button onclick="iniciarRegistro()" class="bg-blu e-500 text-white py-2 px-4 mt-4 rounded-lg hover:bg-blue-600">
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
        // ====== Datos desde backend ======
        // ubicacionesFull: { id: { nombre: '...', almacen: '0A'|'0B'|'AL' } }
        const ubicacionesFull = @json(
            $ubicaciones->mapWithKeys(fn($u) => [
                    $u->id => ['nombre' => $u->nombre_sin_prefijo, 'almacen' => $u->almacen],
                ])) || {};

        const fabricantes = @json($fabricantes->pluck('nombre', 'id')) || {};
        const obras = @json($obras->pluck('obra', 'id')) || {}; // { obra_id: 'Nombre Obra' }
        const obraAlm = @json($obraAlmacenes) || {}; // { obra_id: '0A'|'0B'|'AL' }

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
                    inputValue: '1',
                    inputPlaceholder: 'Selecciona cantidad',
                    showCancelButton: true,
                    inputValidator: (value) => !value && 'Debes seleccionar una opción'
                });
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
                    inputValue: '{{ $ultimoFabricanteId }}',
                    inputPlaceholder: 'Selecciona fabricante',
                    showCancelButton: true,
                    inputValidator: (value) => !value && 'Selecciona fabricante'
                });
                if (!fabricante_id) return;
                document.getElementById('fabricante_id_input').value = fabricante_id;

                // Albarán
                document.getElementById('albaran_input').value = 'Entrada manual';

                // Producto base (como en tu blade original)
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
                    inputValue: '{{ $ultimoProductoBaseId }}',
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
                    showCancelButton: true,
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
                    showCancelButton: true,
                    inputValidator: (value) => !value && 'Número de paquete requerido'
                });
                if (!n_paquete) return;
                document.getElementById('n_paquete_input').value = n_paquete;

                // Segundo paquete (si aplica)
                if (paquetes === '2') {
                    const {
                        value: codigo_2
                    } = await Swal.fire({
                        title: 'Código segundo paquete',
                        input: 'text',
                        showCancelButton: true,
                        inputValidator: (value) => !value && 'Código requerido'
                    });
                    if (!codigo_2) return;
                    document.getElementById('codigo_2_input').value = codigo_2;

                    const {
                        value: n_colada_2
                    } = await Swal.fire({
                        title: 'Colada segundo paquete',
                        input: 'text',
                        showCancelButton: true
                    });
                    document.getElementById('n_colada_2_input').value = n_colada_2 || '';

                    const {
                        value: n_paquete_2
                    } = await Swal.fire({
                        title: 'Número segundo paquete',
                        input: 'number',
                        showCancelButton: true
                    });
                    document.getElementById('n_paquete_2_input').value = n_paquete_2 || '';
                }

                // Peso total
                const {
                    value: peso
                } = await Swal.fire({
                    title: 'Peso total (kg)',
                    input: 'number',
                    showCancelButton: true,
                    inputValidator: (value) => (value <= 0 ? 'Introduce un peso válido' : undefined)
                });
                if (!peso) return;
                document.getElementById('peso_input').value = peso;

                // ====== NUEVO: Selección de ALMACÉN (obra) ======
                const {
                    value: obra_id
                } = await Swal.fire({
                    title: 'Selecciona almacén',
                    input: 'select',
                    inputOptions: obras, // { id: 'Nave A' | 'Nave B' | 'Almacén ...' }
                    inputPlaceholder: 'Elige un almacén (obra)',
                    inputValue: @json($obraActualId), // seguro aunque sea null
                    showCancelButton: true,
                    inputValidator: (value) => !value && 'Debes seleccionar un almacén'
                });
                if (!obra_id) return;
                document.getElementById('obra_id_input').value = obra_id;

                // Código de almacén de la obra: '0A' | '0B' | 'AL'
                const code = obraAlm[String(obra_id)] || 'AL';

                // ====== Selección de UBICACIÓN filtrada por almacén ======
                const opcionesFiltradas = Object.entries(ubicacionesFull)
                    .filter(([id, u]) => u.almacen === code)
                    .map(([id, u]) => `<option value="${id}">${u.nombre}</option>`)
                    .join('');

                if (!opcionesFiltradas) {
                    await Swal.fire('Sin ubicaciones', 'No hay ubicaciones disponibles para el almacén seleccionado.',
                        'warning');
                    return;
                }

                let ubicacionElegida = '';
                const {
                    value: ubicacionSel
                } = await Swal.fire({
                    title: `Selecciona ubicación (${code})`,
                    html: `
                  <div style="display:flex;flex-direction:column;align-items:stretch;gap:12px;text-align:left;">
                    <label style="font-weight:600;font-size:14px;">Ubicación</label>
                    <select id="swal-ubicacion" style="
                      width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;font-size:14px;box-sizing:border-box;
                    ">
                      ${opcionesFiltradas}
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
                    preConfirm: () => document.getElementById('swal-ubicacion').value
                });
                if (!ubicacionSel) return;
                ubicacionElegida = ubicacionSel;

                // ¿Escanear ubicación manualmente?
                const scanCheckbox = Swal.getPopup()?.querySelector('#swal-scan-checkbox');
                if (scanCheckbox && scanCheckbox.checked) {
                    const {
                        value: ubicacionScan
                    } = await Swal.fire({
                        title: 'Escanea la ubicación',
                        input: 'text',
                        inputPlaceholder: 'Escanea o introduce el código de ubicación',
                        showCancelButton: true,
                        inputValidator: (value) => !value && 'Debes introducir un código'
                    });
                    if (!ubicacionScan) return;
                    ubicacionElegida = ubicacionScan;
                }

                // Guardar ubicación y enviar
                document.getElementById('ubicacion_input').value = ubicacionElegida;
                document.getElementById('inventarioForm').submit();

            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Ha ocurrido un error inesperado.', 'error');
            }
        }
    </script>

</x-app-layout>
