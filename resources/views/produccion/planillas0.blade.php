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

        <div class="rounded-xl gap-2 flex bg-neutral-300 overflow-scroll">
            @forelse($maquinas as $maq)

            @php
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
            ];
            @endphp

            <div class="maquina border-2 border-black w-full min-w-24" data-detalles='@json($detalles)'>
                <div class="bg-slate-500 w-full h-12 p-1 rounded-t-md text-center">
                    <p class="uppercase bold">
                        {{ $maq->codigo }}
                    </p>
                </div>
                <div class="planillas">

                </div>
            </div>





            @empty
            No hay máquinas
            @endforelse
            @foreach($maquinas as $maq)
            @endforeach
        </div>
    </div>

    <script src="{{ asset('js/planillas/planificacion.js') }}"></script>


</x-app-layout>