@php
$numMaquinas = $maquinas->count();
@endphp

<x-app-layout>


    <div class="p-6">
        <div class="flex w-full justify-between">
            <h1 class="text-2xl font-bold mb-4">🧾 Planificación por Orden</h1>
            <div class="flex gap-5">
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

            <div class="maquina flex flex-col w-full min-w-24 bg-neutral-200"
                data-detalles='@json($detalles)'
                data-maquina-id="{{ $detalles['id'] }}">
                <div class="bg-neutral-400 w-full h-12 p-1 rounded-t-md flex items-center justify-center">
                    <p class="uppercase text-2xl">{{ $detalles["codigo"] }}</p>
                </div>

                <div class="planillas flex-1 min-h-0 flex flex-col gap-2 overflow-auto 
                [&::-webkit-scrollbar]:w-2
              [&::-webkit-scrollbar-track]:bg-neutral-200
              [&::-webkit-scrollbar-thumb]:bg-neutral-400
              dark:[&::-webkit-scrollbar-track]:bg-neutral-700
              dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500
              ">
                    @foreach ($ordenPlanillas as $orden)
                    @if ($orden->maquina_id == $detalles['id'])
                    @foreach ($planillas as $planilla)
                    @if ($planilla->id == $orden->planilla_id)
                    <div class="planilla p-3 flex justify-around items-center bg-orange-300 hover:bg-orange-400 cursor-pointer select-none text-center relative"
                        draggable="true"
                        data-planilla-id="{{ $planilla->id }}"
                        data-posicion="{{ $orden->posicion }}">
                        <p class="text-neutral-500 text-xs font-bold absolute top-1 left-1 pos-label">
                            {{ $orden->posicion }}
                        </p>
                        <p>{{ $planilla->codigo }}</p>
                    </div>
                    @endif
                    @endforeach
                    @endif
                    @endforeach
                </div>
            </div>






            @empty
            No hay máquinas
            @endforelse
            @foreach($maquinas as $maq)
            @endforeach
        </div>


        <div id="modal_guardar" class="absolute flex bottom-14 left-1/2 -translate-x-1/2 p-2 bg-white shadow-xl gap-3 rounded-xl">
            <button class="p-2 px-10 flex text-center justify-center items-center bg-green-400 rounded-xl hover:bg-green-500 font-semibold transition-all duration-150">Guardar</button>
            <button class="p-2 px-10 flex text-center justify-center items-center bg-neutral-400 rounded-xl hover:bg-red-400 font-semibold transition-all duration-150">Cancelar</button>
        </div>


        <div id="elementos_en_seleccion" class="absolute flex flex-col -left-96 top-1/2 transition-all duration-100 -translate-y-1/2 p-3 bg-white rounded-xl shadow-xl gap-3">
            <div class="flex justify-end gap-2 font-semibold cursor-pointer">
                <p id="mover_modal_elementos" class="h-7 w-7 hover:bg-blue-300 rounded-full flex items-center justify-center font-bold font-mono leading-none hover:text-white transition-all duration-150 text-blue-300">></p>
                <p id="quit_elementos" class="h-7 w-7 hover:bg-red-500 rounded-full flex items-center justify-center font-bold font-mono leading-none hover:text-white transition-all duration-150 text-red-500">x</p>
            </div>

            <div class="uppercase flex flex-col gap-3 justify-center items-center">
                <p>Elementos de <span id="seleccion_planilla_codigo" class="chip">****-******</span></p>
                <p>en máquina <span id="seleccion_maquina_tag" class="chip">****</span></p1>
            </div>
            <div id="seleccion_elementos" class="max-h-96 overflow-auto flex flex-col gap-2 p-3 rounded-xl bg-neutral-200
            [&::-webkit-scrollbar]:w-2
          [&::-webkit-scrollbar-track]:bg-neutral-300
          [&::-webkit-scrollbar-thumb]:bg-neutral-400
          [&::-webkit-scrollbar-track]:rounded-r-xl
          [&::-webkit-scrollbar-thumb]:rounded-xl
          dark:[&::-webkit-scrollbar-track]:bg-neutral-700
          dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500">
                *
            </div>
        </div>
    </div>

    <script src="{{ asset('js/planillas/planificacion.js') }}"></script>
    <style>
        .planilla {
            background-color: #fdba74;
        }

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
        }
    </style>


</x-app-layout>