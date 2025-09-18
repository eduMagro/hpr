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
        <div class="w-full">
            @if ($maquina->tipo === 'grua')
                <div class="grid grid-cols-1 sm:grid-cols-8 gap-6">
                    {{-- <x-maquinas.tipo.tipo-grua :movimientosPendientes="$movimientosPendientes" :ubicaciones="$ubicaciones" :paquetes="$paquetes" /> --}}
                    <x-maquinas.tipo.tipo-grua :movimientos-pendientes="$movimientosPendientes" :movimientos-completados="$movimientosCompletados" :ubicaciones-disponibles-por-producto-base="$ubicacionesDisponiblesPorProductoBase" />

                    @include('components.maquinas.modales.grua.modales-grua')
                @elseif ($maquina->tipo === 'dobladora manual')
                    <div class="grid grid-cols-1 sm:grid-cols-8 gap-6">
                        <x-maquinas.tipo.tipo-dobladora-manual :maquina="$maquina" :maquinas="$maquinas" :elementosAgrupados="$elementosAgrupados"
                            :productosBaseCompatibles="$productosBaseCompatibles" />
                    </div>
                @elseif ($maquina->tipo === 'cortadora manual')
                    <div class="grid grid-cols-1 sm:grid-cols-8 gap-6">
                        <x-maquinas.tipo.tipo-cortadora-manual :maquina="$maquina" :maquinas="$maquinas" :elementosAgrupados="$elementosAgrupados"
                            :productosBaseCompatibles="$productosBaseCompatibles" />
                    </div>
                @else
                    <x-maquinas.tipo.tipo-normal :maquina="$maquina" :maquinas="$maquinas" :elementos-agrupados="$elementosAgrupados"
                        :productos-base-compatibles="$productosBaseCompatibles" :producto-base-solicitados="$productoBaseSolicitados" :planillas-activas="$planillasActivas" :elementos-por-planilla="$elementosPorPlanilla" :mostrar-dos="$mostrarDos"
                        :sugerencias-por-elemento="$sugerenciasPorElemento" {{-- NUEVO: todo lo de barras va por props --}} :es-barra="$esBarra" :longitudes-por-diametro="$longitudesPorDiametro"
                        :diametro-por-etiqueta="$diametroPorEtiqueta" />


                    @include('components.maquinas.modales.normal.modales-normal')
            @endif

        </div>

        <!-- SCRIPT PARA IMPRIMIR QR -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script src="{{ asset('js/maquinaJS/trabajoEtiqueta.js') }}"></script>
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
        <script src="{{ asset('js/maquinaJS/elementInfoPanel.js') }}"></script>

        {{-- <script src="{{ asset('js/maquinaJS/canvasMaquinaSinBoton.js') }}" defer></script> --}}

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
        </script>

</x-app-layout>
