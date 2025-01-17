<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detalles Máquina') }}
        </h2>
    </x-slot>
    <div class="container mx-auto px-4 py-6">
        <!-- Grid principal -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @if (isset($maquina))
                <!-- Información de la máquina -->
                <div class="bg-white border p-4 shadow-md rounded-lg">
                    <h3 class="font-bold text-xl break-words">{{ $maquina->codigo }}</h3>
                    <p><strong>Nombre Máquina:</strong> {{ $maquina->nombre }}</p>
                    <p>
                        <button id="generateQR" onclick="generateAndPrintQR('{{ $maquina->codigo }}')"
                            class="btn btn-primary">QR</button>
                    </p>
                    <div id="qrCanvas" style="display:none;"></div>
                    <p><strong>Diámetros aceptados:</strong>
                        {{ $maquina->diametro_min . ' - ' . $maquina->diametro_max }}</p>
                    <p><strong>Pesos bobinas:</strong>
                        {{ $maquina->peso_min && $maquina->peso_max ? $maquina->peso_min . ' - ' . $maquina->peso_max : 'Barras' }}
                    </p>

                    <!-- Mostrar los productos en la máquina -->
                    <h4 class="mt-4 font-semibold">Productos en máquina:</h4>
                    @if ($maquina->productos->isEmpty())
                        <p>No hay productos en esta máquina.</p>
                    @else
                        <ul class="list-disc pl-6 break-words">
                            @foreach ($maquina->productos as $producto)
                                <li class="mb-2 flex items-center justify-between">
                                    <span>
                                        ID{{ $producto->id }} - Tipo: {{ $producto->tipo }} - D{{ $producto->diametro }}
                                        - L{{ $producto->longitud ?? '??' }}
                                    </span>
                                    <a href="{{ route('productos.show', $producto->id) }}"
                                        class="btn btn-sm btn-primary">Ver</a>
                                    @if ($producto->tipo == 'encarretado')
                                        <div
                                            style="width: 100px; height: 100px; background-color: #ddd; position: relative; overflow: hidden;">
                                            <div class="cuadro verde"
                                                style="width: 100%; 
                                                       height: {{ ($producto->peso_stock / $producto->peso_inicial) * 100 }}%; 
                                                       background-color: green; 
                                                       position: absolute; 
                                                       bottom: 0;">
                                            </div>
                                            <span style="position: absolute; top: 10px; left: 10px; color: white;">
                                                {{ $producto->peso_stock }} / {{ $producto->peso_inicial }} kg
                                            </span>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <!-- Planificación para la máquina agrupada por etiquetas -->
                <div class="bg-white border p-4 shadow-md w-full">
                    <h3 class="font-bold text-xl">Planificación prevista</h3>

                    @php
                        // Agrupamos los elementos por etiqueta_id
                        $elementosAgrupados = $maquina->elementos->groupBy('etiqueta_id');
                    @endphp

                    @forelse ($elementosAgrupados as $etiquetaId => $elementos)
                        @php
                            $etiqueta = $elementos->first()->etiquetaRelacion ?? null; // Obtener la etiqueta del primer elemento para mostrarla. cambio el nombre de la relacion para que nocree conflicto con la columna etiquta
                            $planilla = $elementos->first()->planilla ?? null; // Obtener la etiqueta del primer elemento para mostrarla
                        @endphp

                        <div class="bg-yellow-100 p-6 rounded-lg shadow-md mt-4">
                            <h2 class="text-lg font-semibold text-gray-700"><strong>Planilla:</strong>
                                {{ $planilla->codigo ?? 'Sin planilla' }}</h2>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                Etiqueta: {{ $etiqueta->nombre ?? 'Sin nombre' }}
                                (Número: {{ $etiqueta->numero_etiqueta ?? 'Sin número' }})
                            </h3>

                            <!-- GRID PARA ELEMENTOS (2 columnas dentro de cada etiqueta) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach ($elementos as $elemento)
                                    <div id="elemento-{{ $elemento->id }}"
                                        class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                                        {{ $loop->iteration }}.
                                        <p class="text-gray-500 text-sm">
                                            <strong>Estado:</strong> {{ $elemento->estado ?? 'Sin estado' }}
                                        </p>
                                        <hr class="my-2">

                                        <p class="text-gray-500 text-sm">
                                            <strong>Peso:</strong> {{ $elemento->peso ?? 'No asignado' }}
                                        </p>
                                        <hr class="my-2">

                                        <p class="text-gray-500 text-sm">
                                            <strong>Diámetro:</strong> {{ $elemento->diametro ?? 'No asignado' }}
                                        </p>
                                        <hr class="my-2">

                                        <p class="text-gray-500 text-sm">
                                            <strong>Longitud:</strong> {{ $elemento->longitud ?? 'No asignado' }}
                                        </p>
                                        <hr class="my-2">

                                        <p class="text-gray-500 text-sm">
                                            <strong>Número de piezas:</strong> {{ $elemento->barras ?? 'No asignado' }}
                                        </p>
                                        <hr class="my-2">

                                        <p class="text-gray-500 text-sm">
                                            <strong>Tipo de Figura:</strong> {{ $elemento->figura ?? 'No asignado' }}
                                        </p>
                                        <hr class="my-2">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="col-span-4 text-center py-4 text-gray-600">
                            No hay elementos disponibles para esta máquina.
                        </div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
