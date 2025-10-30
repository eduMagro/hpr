@php
$numMaquinas = $maquinas->count();
@endphp

<x-app-layout>


    <div class="p-6 overflow-hidden relative min-h-[calc(100vh-80px)]">
        <div class="flex w-full justify-between">
            <h1 class="text-2xl font-bold mb-4">🧾 Planificación por Orden</h1>
            <div class="flex gap-5">

                <button id="btn_mostrar_modal_obras" onclick="mostrarModalResaltarObra()" class="p-2 bg-gradient-to-r text-neutral-600 hover:text-white from-neutral-500/30 to-neutral-600/30 hover:from-neutral-500 hover:to-neutral-600 uppercase font-bold text-sm rounded-lg transition-all duration-75 hover:-translate-y-[1px]">Resaltar por obra</button>
                <button id="btn_quitar_resaltado" onclick="resaltarObra(1)"
                    class="hidden p-2 border-2 border-neutral-400 text-neutral-700 hover:bg-neutral-200 uppercase font-bold text-sm rounded-lg transition-all duration-75 hover:-translate-y-[1px]">
                    Quitar resaltado
                </button>

                @php
                $naves = [
                ['label' => 'Todas', 'value' => 'all'],
                ['label' => 'Nave 1', 'value' => '1'],
                ['label' => 'Nave 2', 'value' => '2'],
                ];
                @endphp

                @foreach ($naves as $i => $n)
                <button
                    class="filtro_nave p-2 border-2 border-emerald-600 {{ $i === 0 ? 'bg-gradient-to-r text-white' : 'text-emerald-700' }} from-emerald-600 to-emerald-700 uppercase font-bold text-sm rounded-lg transition-all duration-75 hover:-translate-y-[1px]"
                    data-nave="{{ $n['value'] }}">
                    {{ $n['label'] }}
                </button>
                @endforeach

            </div>
        </div>

        <div id="maquinas" class="rounded-xl gap-2 flex overflow-x-scroll  h-[calc(100vh-125px)] mt-3">
            @forelse($maquinas as $maq)

            @php
            foreach ($localizacionMaquinas as $loc) {
            if ($loc->maquina_id == $maq->id) {
            $ubicacion = $loc->nave_id;
            break;
            };
            };


            $detalles = [
            'id' => $maq->id,
            'nombre' => $maq->nombre,
            'codigo' => $maq->codigo,
            'estado' => $maq->estado,
            'tipo' => $maq->tipo,
            'tipo_material' => $maq->tipo_material,
            'diametro_min' => $maq->diametro_min,
            'diametro_max' => $maq->diametro_max,
            'peso_min' => $maq->peso_min,
            'peso_max' => $maq->peso_max,
            'nave_id' => $ubicacion
            ];
            @endphp

            <div class="maquina flex flex-col w-full min-w-[100px] bg-neutral-200 rounded-t-xl"
                data-detalles='@json($detalles)'
                data-maquina-id="{{ $detalles['id'] }}">
                <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 w-full h-12 p-2 rounded-t-xl flex items-center justify-center text-white shadow-md uppercase font-bold text-xl">
                    <p class="uppercase text-2xl font-mono">{{ $detalles["codigo"] }}</p>
                </div>

                <div class="planillas flex-1 min-h-0 flex flex-col gap-2 overflow-auto 
                [&::-webkit-scrollbar]:w-2
                [&::-webkit-scrollbar-track]:bg-neutral-300
                [&::-webkit-scrollbar-thumb]:bg-emerald-700
                p-1 py-2"></div>

            </div>






            @empty
            No hay máquinas
            @endforelse
            @foreach($maquinas as $maq)
            @endforeach
        </div>


        <div id="modal_guardar"
            data-save-url="{{ route('produccion.planillas.guardar') }}"
            class="opacity-0 gap-2 absolute flex -bottom-14 left-1/2 -translate-x-1/2 transition-all duration-150">
            <button id="btn_guardar" class="p-2 px-10 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 rounded-xl hover:text-white font-semibold transition-all duration-150">
                Guardar
            </button>
            <button id="btn_cancelar_guardar" class="p-2 px-10 rounded-xl bg-gradient-to-r from-red-400 to-red-500 hover:from-red-500 hover:to-red-600 hover:text-white font-semibold transition-all duration-150">
                Cancelar
            </button>
        </div>

        <div id="modal_transferir_a_maquina" class="bg-black bg-opacity-50 absolute top-0 left-0 w-screen h-screen flex items-center justify-center hidden backdrop-blur-sm">
            <div class="bg-neutral-100 shadow-lg rounded-lg p-3 flex flex-col gap-4 items-center min-w-[calc(100vw-40vw)]">
                <div class="uppercase font-medium">Seleccione nueva ubicación</div>

                <div class="grid grid-cols-2 md:grid-cols-3 w-full gap-2 p-2 max-h-96 overflow-y-scroll rounded-lg
            [&::-webkit-scrollbar]:w-2
          [&::-webkit-scrollbar-thumb]:bg-neutral-400">
                    @forelse($maquinas as $maq)
                    <div data-id="{{ $maq->id }}" data-id="{{ $maq->id }}" class="p-3 flex maquina_transferir justify-between gap-10 items-center cursor-pointer maquina_no_seleccionada hover:-translate-y-[1px] transition-all duration-75 shadow-sm rounded-lg">
                        <p class="text-indigo-900 font-mono font-extrabold uppercase">{{ $maq->nombre }}</p>
                        <p class="text-xs font-mono font-semibold p-1 text-white rounded-md bg-indigo-600">{{ $maq->codigo }}</p>
                    </div>
                    @endforeach
                </div>

                <div>
                    <button id="transferir_elementos" class="p-2 bg-orange-400 w-full hover:bg-orange-500 hover:text-white transition-all duration-150 rounded-lg uppercase font-semibold font-mono shadow-md">Transferir</button>
                </div>
            </div>
        </div>

        <div id="modal_resaltar_obra" class="bg-black bg-opacity-50 absolute top-0 left-0 md:px-10 w-screen h-screen flex items-center justify-center hidden backdrop-blur-sm">

            <div class="bg-neutral-100 shadow-lg rounded-lg p-3 flex flex-col gap-4 items-center min-w-[calc(100vw-40vw)]">
                <div class="uppercase font-medium">Seleccione obra para resaltar</div>

                <div>
                    <input id="input_filtrar_obra" type="text" placeholder="Filtrar por nombre" class="p-2 focus:outline-fuchsia-300 rounded-lg shadow-md">
                </div>

                <div id="obras_modal" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 w-full gap-2 p-2 max-h-96 overflow-y-scroll rounded-lg
            [&::-webkit-scrollbar]:w-2
          [&::-webkit-scrollbar-thumb]:bg-neutral-400">
                    @foreach($obras as $obra)
                    <div data-id="{{ $obra->id }}" class="p-3 flex obra justify-between gap-10 items-center cursor-pointer obra_no_seleccionada hover:-translate-y-[1px] transition-all duration-75 shadow-sm rounded-lg bg-gradient-to-tr from-neutral-200 to-neutral-300 hover:from-fuchsia-300 hover:to-fuchsia-400 hover:text-fuchsia-900">
                        <p class="font-mono font-extrabold uppercase">{{ $obra->obra }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>


        <div id="modal_elementos" class="bg-black bg-opacity-50 absolute top-0 left-0 w-screen h-screen flex items-center justify-center hidden backdrop-blur-sm">
            <div id="div_elementos" class="flex flex-col transition-all duration-100 p-3 bg-neutral-100 rounded-xl shadow-xl gap-3 items-center">
                <div class="uppercase flex gap-3 justify-center items-center">
                    <p>Elementos de <span id="seleccion_planilla_codigo" class="chip">****-******</span></p>
                    <p>en máquina <span id="seleccion_maquina_tag" class="chip">****</span></p1>
                </div>
                <div id="seleccion_elementos" class="w-full max-h-[35rem] overflow-auto grid grid-cols-4 gap-2 p-3 rounded-xl
            [&::-webkit-scrollbar]:w-2
          [&::-webkit-scrollbar-thumb]:bg-neutral-400
          [&::-webkit-scrollbar-track]:rounded-r-xl
          [&::-webkit-scrollbar-thumb]:rounded-xl">
                    *
                </div>

                <div class="flex justify-end gap-3 w-full">
                    <button onclick="seleccionarMaquinaParaMovimiento()" class="flex p-3 bg-orange-400 hover:bg-orange-500 hover:text-white transition-all duration-150 font-sans font-semibold text-xs uppercase rounded-lg">transferir a otra máquina</button>
                    <button id="cancelar_modal_elementos" class="flex p-3 bg-red-400 hover:bg-red-600 hover:text-white transition-all duration-150 font-sans font-semibold text-xs uppercase rounded-lg">Cancelar</button>
                </div>
            </div>
        </div>

        <!-- Modal elegir orden existente o crear nueva -->
        <div id="modal_elegir_orden" class="bg-black bg-opacity-50 absolute top-0 left-0 w-screen h-screen flex items-center justify-center hidden backdrop-blur-sm">
            <div class="bg-neutral-100 shadow-lg rounded-lg p-4 flex flex-col gap-4 items-center min-w-[calc(100vw-40vw)]">
                <div class="uppercase font-semibold text-center">
                    Coincidencias en <span id="meo_maquina_nombre" class="font-mono text-indigo-700"></span>
                </div>

                <div class="text-sm text-gray-700 text-center">
                    Ya existen planillas con el mismo código <span id="meo_codigo" class="font-mono font-semibold"></span>.
                    Selecciona una para fusionar (los elementos pasarán a esa orden) o crea una nueva al final.
                </div>

                <div id="meo_lista" class="w-full max-h-72 overflow-y-auto rounded-lg border border-neutral-200 p-2
      [&::-webkit-scrollbar]:w-2
      [&::-webkit-scrollbar-thumb]:bg-neutral-400">
                    <!-- se rellena dinámicamente -->
                </div>

                <div class="w-full">
                    <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer hover:bg-neutral-50">
                        <input type="radio" name="meo_opcion" value="__crear_nueva__">
                        <span class="text-sm font-semibold">Crear nueva orden al final</span>
                    </label>
                </div>

                <div class="flex gap-3">
                    <button id="meo_confirmar" class="p-2 px-6 bg-orange-400 hover:bg-orange-500 hover:text-white transition-all duration-150 rounded-lg uppercase font-semibold">
                        Confirmar
                    </button>
                    <button id="meo_cancelar" class="p-2 px-6 bg-red-400 hover:bg-red-600 hover:text-white transition-all duration-150 rounded-lg uppercase font-semibold">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>

    </div>

    <div id="modal_detalles" class="absolute bg-opacity-50 backdrop-blur-sm bg-gradient-to-tr from-emerald-200/10 to-emerald-300/10 border-2 border-emerald-700 left-4 uppercase top-[45vh] rounded-lg text-emerald-700 font-mono font-semibold text-sm p-2 flex flex-col gap-2">
        <div>
            <p>Obra:</p>
            <p><span></span></p>
        </div>
        <div>
            <p>Estado producción:</p>
            <p><span></span></p>
        </div>
        <div>
            <p>Fin programado:</p>
            <p><span></span></p>
        </div>
        <div>
            <p>Estimación entrega:</p>
            <p><span></span></p>
        </div>
    </div>

    <div id="todosElementos" class="hidden">
        @foreach ($elementos as $elemento)
        <div data-elementos="{{ json_encode([
        'id' => $elemento->id,
        'codigo' => $elemento->codigo,
        'maquina_id' => $elemento->maquina_id,
        'planilla_id' => $elemento->planilla_id,
        'peso' => $elemento->peso,
        'orden_planilla_id' => $elemento->orden_planilla_id,
        'dimensiones' => $elemento->dimensiones,
        'diametro' => $elemento->diametro,
        'diametro' => $elemento->diametro,
    ]) }}"></div>
        @endforeach
    </div>

    <div id="todasPlanillas" class="hidden">
        @foreach ($planillas as $p)
        <div data-planilla='{{ json_encode(["id" => $p->id, "codigo" => $p->codigo, "obra_id" => $p->obra_id, "estado" => $p->estado, "estimacion_entrega" => $p->fecha_estimada_entrega]) }}'></div>
        @endforeach
    </div>

    <div id="ordenPlanillas" class="hidden">
        @foreach ($ordenPlanillas as $o)
        <div
            data-orden='{{ json_encode([
    "id"          => $o->id,
    "maquina_id"  => $o->maquina_id,
    "planilla_id" => $o->planilla_id,
    "posicion"    => $o->posicion,
  ], JSON_UNESCAPED_UNICODE) }}'>
        </div>

        @endforeach
    </div>



    <div id="obras" class="hidden">
        @foreach ($obras as $o)
        <div data-obras='@json(["obra_id"=>$o->id, "nombre"=>$o->obra])'></div>
        @endforeach
    </div>

    <!-- emerald-300 #6ee7b7 -->
    <!-- emerald-400 #34d399 -->
    <!-- emerald-500 #10b981 -->

    <script src="{{ asset('js/elementosJs/figuraElemento.js') }}" defer></script>
    <script src="{{ asset('js/planillas/planificacion.js') }}"></script>
    <style>
        .planilla.compi-resaltado {
            background-color: #6ee7b7 !important;
        }

        .planilla.dragging {
            opacity: .6;
        }

        .planillas.drop-target {
            outline: 2px dashed #2563eb;
            outline-offset: 2px;
        }

        .chip {
            padding: 4px;
            background-color: lightgray;
            border-radius: 10px;
            font-family: monospace;
            font-weight: 700;
        }

        .planilla {
            will-change: transform;
        }

        .maquina_no_seleccionada {
            background-image: linear-gradient(to top right, #e0e7ff, #c7d2fe);
        }

        .maquina_no_seleccionada:hover {
            background-image: linear-gradient(to top right, #c7d2fe, #a5b4fc);
        }

        .maquina_si_seleccionada {
            background-image: linear-gradient(to top right, #10b981, #818cf8);
        }

        #transferir_elementos:hover .chiptransferirA {
            background: linear-gradient(to top right, #6ee7b7, #a5b4fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }

        #meo_lista .meo_item input[type="radio"] {
            accent-color: #10b981;
        }

        #meo_lista .meo_item:hover {
            background: rgba(16, 185, 129, 0.08);
        }

        .planilla.placeholder {
            border: 2px dashed #2563eb !important;
            background: repeating-linear-gradient(45deg,
                    rgba(99, 102, 241, .10),
                    rgba(99, 102, 241, .10) 8px,
                    rgba(16, 185, 129, .10) 8px,
                    rgba(16, 185, 129, .10) 16px) !important;
            opacity: .6 !important;

            box-sizing: border-box;
            flex-shrink: 0;
        }

        .maquina .planillas.drop-target {
            outline: 3px dashed #10b981;
            outline-offset: 4px;
            border-radius: .75rem;
        }

        .planilla.dragging {
            opacity: .5 !important;
            transform: rotate(1deg);
        }

        .planilla.resaltar-obra {
            background-image: linear-gradient(to top right, #f0abfc, #f472b6) !important;
        }

        .planilla.obra_resaltada:hover {
            border-color: #f472b6 !important;
            background-image: linear-gradient(to top right, #f0abfc, #f472b6) !important;
        }

        .planilla.obra_resaltada:hover .posicion,
        .planilla.obra_resaltada:hover .codigo {
            color: #a21caf !important;
        }

        /* Prioridad de indigo cuando resalta compis */
        .planilla.__hl-compi {
            /* gradiente indigo por defecto */
            background-image: linear-gradient(to top right, #c7d2fe, #a5b4fc) !important;
            /* from-indigo-200 to-indigo-300 */
            border-color: #6366f1 !important;
            /* border-indigo-500 */
        }

        .planilla.__hl-compi .codigo {
            color: #1e1b4b !important;
        }

        /* text-indigo-900 */
        .planilla.__hl-compi .posicion {
            color: #3730a3 !important;
        }

        /* text-indigo-700 */

        /* Evita que el hover de la card cambie a verde cuando está como compi */
        .planilla.__hl-compi:hover {
            background-image: linear-gradient(to top right, #c7d2fe, #a5b4fc) !important;
            border-color: #6366f1 !important;
        }

        #modal_detalles span {
            color: black;
            font-size: 1rem;
        }
    </style>






</x-app-layout>