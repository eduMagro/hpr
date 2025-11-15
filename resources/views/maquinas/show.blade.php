<x-app-layout>
    <x-slot name="title">{{ $maquina->nombre }} - {{ config('app.name') }}</x-slot>

    <div class="w-full sm:px-4">
        <!-- Grid principal -->
        <div class="w-full">
            @if ($maquina->tipo === 'grua')
                <div class="grid grid-cols-1 sm:grid-cols-8 gap-6">
                    {{-- <x-maquinas.tipo.tipo-grua :movimientosPendientes="$movimientosPendientes" :ubicaciones="$ubicaciones" :paquetes="$paquetes" /> --}}
                    <x-maquinas.tipo.tipo-grua :maquina="$maquina" :movimientos-pendientes="$movimientosPendientes" :movimientos-completados="$movimientosCompletados"
                        :ubicaciones-disponibles-por-producto-base="$ubicacionesDisponiblesPorProductoBase" />
                    @include('components.maquinas.modales.grua.modales-grua')
                @elseif ($maquina->tipo === 'dobladora_manual')
                    <div class="grid grid-cols-1 sm:grid-cols-8 gap-6">
                        <x-maquinas.tipo.tipo-dobladora-manual :maquina="$maquina" :maquinas="$maquinas" :elementosAgrupados="$elementosAgrupados"
                            :productosBaseCompatibles="$productosBaseCompatibles" />
                    </div>
                @elseif ($maquina->tipo === 'cortadora_manual')
                    <div class="grid grid-cols-1 sm:grid-cols-8 gap-6">
                        <x-maquinas.tipo.tipo-cortadora-manual :maquina="$maquina" :maquinas="$maquinas" :elementosAgrupados="$elementosAgrupados"
                            :productosBaseCompatibles="$productosBaseCompatibles" />
                    </div>
                @else
                    <x-maquinas.tipo.tipo-normal :maquina="$maquina" :maquinas="$maquinas" :elementos-agrupados="$elementosAgrupados"
                        :productos-base-compatibles="$productosBaseCompatibles" :producto-base-solicitados="$productoBaseSolicitados" :planillas-activas="$planillasActivas" :elementos-por-planilla="$elementosPorPlanilla" :es-barra="$esBarra"
                        :longitudes-por-diametro="$longitudesPorDiametro" :diametro-por-etiqueta="$diametroPorEtiqueta" :elementos-agrupados-script="$elementosAgrupadosScript" :posiciones-disponibles="$posicionesDisponibles" :posicion1="$posicion1"
                        :posicion2="$posicion2" />

                    @include('components.maquinas.modales.normal.modales-normal')
            @endif

        </div>

        <!-- SCRIPT PARA IMPRIMIR QR -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

        <script src="{{ asset('js/maquinaJS/sl28/cortes.js') }}"></script>
        <script src="{{ asset('js/maquinaJS/actualizarDom.js') }}"></script>
        <script src="{{ asset('js/maquinaJS/trabajoEtiqueta.js') }}"></script>
        <script src="{{ asset('js/maquinaJS/trabajoPaquete.js') }}"></script>
        <script src="{{ asset('js/imprimirQrS.js') }}"></script>
        <script>
            window.SUGERENCIAS = @json($sugerenciasPorElemento ?? []);
            window.elementosAgrupadosScript = @json($elementosAgrupadosScript ?? null);
            window.rutaDividirElemento = "{{ route('elementos.dividir') }}";
            window.etiquetasData = @json($etiquetasData);
            window.pesosElementos = @json($pesosElementos);
            window.maquinaId = @json($maquina->id);
            window.tipoMaquina = @json($maquina->tipo_material); // 👈 Añadido
            window.ubicacionId = @json(optional($ubicacion)->id);
            console.log('etiquetasData', window.etiquetasData);
        </script>

        <script src="{{ asset('js/maquinaJS/canvasMaquina.js') }}"></script>
        <script src="{{ asset('js/maquinaJS/crearPaquetes.js') }}" defer></script>
        {{-- Al final del archivo Blade --}}

        <script>
            // Bloquea el menú contextual solo dentro de .proceso (tu tarjeta de etiqueta)
            document.addEventListener('contextmenu', function(e) {
                if (e.target.closest('.proceso')) {
                    e.preventDefault();
                }
            }, {
                capture: true
            });

            // Validación de posiciones de planillas en el header
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('form-posiciones-planillas-header');
                if (!form) return;

                const select1 = form.querySelector('select[name="posicion_1"]');
                const select2 = form.querySelector('select[name="posicion_2"]');
                if (!select1 || !select2) return;

                function validar() {
                    const pos1 = select1.value;
                    const pos2 = select2.value;

                    if (pos1 && pos2 && pos1 === pos2) {
                        select2.value = '';
                        Swal.fire({
                            icon: 'warning',
                            title: 'Posiciones duplicadas',
                            text: 'No puedes seleccionar la misma posición dos veces',
                            confirmButtonColor: '#3085d6',
                        });
                        return false;
                    }
                    return true;
                }

                select1.addEventListener('change', validar);
                select2.addEventListener('change', validar);
                form.addEventListener('submit', (e) => !validar() && e.preventDefault());
            });
        </script>

</x-app-layout>
