@php
$numMaquinas = $maquinas->count();
@endphp

<x-app-layout>


    <div class="p-6 overflow-hidden relative min-h-[calc(100vh-80px)]">
        <div class="flex w-full justify-between">
            <h1 class="text-2xl font-bold mb-4">🧾 Planificación por Orden</h1>
            <div class="flex gap-5">
                <p>Encontrados -> <span id="cantidad_encontrados"></span></p>
                <select name="obra" id="select_obra">
                    <option value="0">Resaltar por obra</option>
                    @foreach($obras as $obra)
                    <option value="{{ $obra->id }}">{{ $obra->obra }}</option>
                    @endforeach
                </select>
                <button id="ambas" class="underline text-blue-600">Ambas</button>
                <button id="nave1" class="underline">Nave 1</button>
                <button id="nave2" class="underline">Nave 2</button>
            </div>
        </div>

        <div class="rounded-xl gap-2 flex overflow-x-scroll  h-[calc(100vh-120px)]">
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

            <div class="maquina flex flex-col w-full min-w-24 bg-neutral-200 rounded-t-xl"
                data-detalles='@json($detalles)'
                data-maquina-id="{{ $detalles['id'] }}">
                <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 w-full h-12 p-2 rounded-t-xl flex items-center justify-center text-white shadow-md uppercase font-bold text-xl">
                    <p class="uppercase text-2xl font-mono">{{ $detalles["codigo"] }}</p>
                </div>

                <div class="planillas flex-1 min-h-0 flex flex-col gap-2 overflow-auto 
                [&::-webkit-scrollbar]:w-2
                [&::-webkit-scrollbar-track]:bg-neutral-300
                [&::-webkit-scrollbar-thumb]:bg-emerald-700
                dark:[&::-webkit-scrollbar-track]:bg-neutral-700
                dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500
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
            class="absolute flex -bottom-14 left-1/2 -translate-x-1/2 p-2 bg-white shadow-xl gap-3 rounded-xl transition-all duration-150">
            <button id="btn_guardar" class="p-2 px-10 bg-emerald-600 rounded-xl hover:bg-emerald-700 hover:text-white font-semibold transition-all duration-150">
                Guardar
            </button>
            <button id="btn_cancelar_guardar" class="p-2 px-10 bg-neutral-400 rounded-xl hover:bg-red-600 hover:text-white font-semibold transition-all duration-150">
                Cancelar
            </button>
        </div>

        <div id="modal_transferir_a_maquina" class="bg-black bg-opacity-60 absolute top-0 left-0 w-screen h-screen flex items-center justify-center hidden backdrop-blur-sm">
            <div class="bg-white shadow-lg rounded-lg p-3 flex flex-col gap-4 items-center max-w-3xl">
                <div class="uppercase font-medium">Seleccione nueva ubicación</div>

                <div class="flex flex-col gap-3 max-h-96 overflow-y-scroll
            [&::-webkit-scrollbar]:w-2
          [&::-webkit-scrollbar-thumb]:bg-neutral-400
          [&::-webkit-scrollbar-track]:rounded-r-xl
          [&::-webkit-scrollbar-thumb]:rounded-xl
          dark:[&::-webkit-scrollbar-track]:bg-neutral-700
          dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500">
                    @forelse($maquinas as $maq)
                    <div data-id="{{ $maq->id }}" class="p-3 flex maquina_transferir justify-between gap-10 items-center cursor-pointer bg-neutral-300 hover:bg-neutral-400  transition-all duration-75 shadow-sm">
                        <p>{{ $maq->nombre }}</p>
                        <p class="text-xs font-mono font-semibold p-1 text-white rounded-md bg-neutral-600">{{ $maq->codigo }}</p>
                    </div>
                    @endforeach
                </div>

                <div>
                    <button id="transferir_elementos" class="p-2 bg-orange-400 w-full hover:bg-orange-500 hover:text-white transition-all duration-150 rounded-lg uppercase font-semibold font-mono shadow-md">Transferir</button>
                </div>
            </div>
        </div>


        <div id="modal_elementos" class="bg-black bg-opacity-60 absolute top-0 left-0 w-screen h-screen flex items-center justify-center hidden backdrop-blur-sm">
            <div id="div_elementos" class="flex flex-col transition-all duration-100 p-3 bg-white rounded-xl shadow-xl gap-3 items-center">
                <div class="uppercase flex gap-3 justify-center items-center">
                    <p>Elementos de <span id="seleccion_planilla_codigo" class="chip">****-******</span></p>
                    <p>en máquina <span id="seleccion_maquina_tag" class="chip">****</span></p1>
                </div>
                <div id="seleccion_elementos" class="w-full max-h-[35rem] overflow-auto grid grid-cols-4 gap-2 p-3 rounded-xl bg-neutral-200 border-2 border-neutral-400
            [&::-webkit-scrollbar]:w-2
          [&::-webkit-scrollbar-thumb]:bg-neutral-400
          [&::-webkit-scrollbar-track]:rounded-r-xl
          [&::-webkit-scrollbar-thumb]:rounded-xl
          dark:[&::-webkit-scrollbar-track]:bg-neutral-700
          dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500">
                    *
                </div>

                <div class="flex justify-end gap-3 w-full">
                    <button onclick="seleccionarMaquinaParaMovimiento()" class="flex p-3 bg-orange-400 hover:bg-orange-500 hover:text-white transition-all duration-150 font-sans font-semibold text-xs uppercase rounded-lg">transferir a otra máquina</button>
                    <button id="cancelar_modal_elementos" class="flex p-3 bg-red-500 hover:bg-red-600 hover:text-white transition-all duration-150 font-sans font-semibold text-xs uppercase rounded-lg">Cancelar</button>
                </div>
            </div>
        </div>

        <div id="modal_advertencia_compatibilidad" class="bg-black bg-opacity-60 absolute top-0 left-0 w-screen h-screen flex items-center justify-center hidden backdrop-blur-sm">
            <div class="bg-white shadow-lg rounded-lg p-4 flex flex-col gap-4 items-center max-w-2xl w-full max-h-[80vh]">
                <div class="uppercase font-medium text-center text-red-600">
                    ⚠️ Advertencia de Compatibilidad
                </div>

                <div class="text-center">
                    <p class="font-semibold">No todos los elementos son compatibles con la máquina seleccionada.</p>
                    <p class="text-sm text-gray-600 mt-2">Elementos incompatibles por diámetro:</p>
                </div>

                <div id="lista_elementos_incompatibles" class="w-full max-h-60 overflow-y-auto bg-red-50 border-2 border-red-300 rounded-lg p-3
            [&::-webkit-scrollbar]:w-2
            [&::-webkit-scrollbar-thumb]:bg-red-400
            [&::-webkit-scrollbar-track]:rounded-r-xl
            [&::-webkit-scrollbar-thumb]:rounded-xl">
                    <!-- Se llenará dinámicamente -->
                </div>

                <div class="text-sm text-gray-600">
                    <span id="count_validos" class="font-semibold text-green-600">0</span> elementos compatibles |
                    <span id="count_invalidos" class="font-semibold text-red-600">0</span> elementos incompatibles
                </div>

                <div class="flex gap-3">
                    <button id="advertencia_cancelar" class="p-2 px-6 bg-neutral-400 hover:bg-neutral-500 hover:text-white transition-all duration-150 rounded-lg uppercase font-semibold">
                        Cancelar Todo
                    </button>
                    <button id="advertencia_proseguir" class="p-2 px-6 bg-orange-400 hover:bg-orange-500 hover:text-white transition-all duration-150 rounded-lg uppercase font-semibold">
                        Proseguir con Aptos
                    </button>
                </div>
            </div>
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
        'dimensiones' => $elemento->dimensiones,
        'diametro' => $elemento->diametro,
        'diametro' => $elemento->diametro,
    ]) }}"></div>
        @endforeach
    </div>

    <div id="todasPlanillas" class="hidden">
        @foreach ($planillas as $p)
        <div data-planilla='@json(["id"=>$p->id,"codigo"=>$p->codigo, "obra_id" => $p->obra_id])'></div>
        @endforeach
    </div>

    <div id="ordenPlanillas" class="hidden">
        @foreach ($ordenPlanillas as $o)
        <div data-orden='@json(["maquina_id"=>$o->maquina_id,"planilla_id"=>$o->planilla_id,"posicion"=>$o->posicion])'></div>
        @endforeach
    </div>

    <div id="modal_fusionar_planilla" class="bg-black bg-opacity-60 absolute top-0 left-0 w-screen h-screen flex items-center justify-center hidden backdrop-blur-sm">
        <div class="bg-white shadow-lg rounded-lg p-4 flex flex-col gap-4 items-center max-w-md w-full">
            <div class="uppercase font-medium text-center">
                La máquina seleccionda ya tiene la <span class="font-mono px-2 py-1 rounded bg-neutral-200" id="fusionar_planilla_codigo">PLANILLA</span>.<br />
                ¿Quieres fusionar las planillas?
            </div>

            <div class="flex gap-3">
                <button id="fusionar_cancelar" class="p-2 px-6 bg-neutral-400 hover:bg-neutral-500 hover:text-white transition-all duration-150 rounded-lg uppercase font-semibold">Cancelar</button>
                <button id="fusionar_aceptar" class="p-2 px-6 bg-orange-400 hover:bg-orange-500 hover:text-white transition-all duration-150 rounded-lg uppercase font-semibold">Aceptar</button>
            </div>
        </div>
    </div>


    <script src="{{ asset('js/elementosJs/figuraElemento.js') }}" defer></script>
    <script src="{{ asset('js/planillas/planificacion.js') }}"></script>
    <style>
        .planilla.compi-resaltado {
            background-color: #fc9d55 !important;
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
    </style>


</x-app-layout>