<x-app-layout>
    <x-slot name="title">{{ $maquina->nombre }} - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('maquinas.index') }}" class="text-blue-500">
                {{ __('Máquinas') }}
            </a><span> / </span>{{ __('Trabajando en Máquina') }}: <strong>{{ $maquina->nombre }}</strong>
        </h2>
    </x-slot>


    <div class="container mx-auto px-4 py-6">
        <!-- Mostrar los compañeros -->
        <div class="mb-4">
            <div class="flex flex-col md:flex-row md:space-x-6">
                <!-- Usuario principal -->
                <div class="bg-white border p-3 rounded-lg shadow-md w-full md:w-1/2">
                    <h4 class="text-gray-600 font-semibold">Operario</h4>
                    <p class="text-gray-800 font-bold text-lg">{{ $usuario1->name }}</p>
                </div>

                <!-- Compañero seleccionado -->
                @if ($usuario2)
                    <div class="bg-white border p-3 rounded-lg shadow-md w-full md:w-1/2">
                        <h4 class="text-gray-600 font-semibold">Compañero seleccionado</h4>
                        <p class="text-gray-800 font-bold text-lg">{{ $usuario2->name }}</p>
                    </div>
                @else
                    <div class="bg-white border p-3 rounded-lg shadow-md w-full md:w-1/2">
                        <h4 class="text-gray-600 font-semibold">Compañero seleccionado</h4>
                        <p class="text-red-500 font-bold text-lg">No se ha seleccionado un compañero.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Grid principal -->
        <div class="grid grid-cols-1 sm:grid-cols-8 gap-6">
            <!-- --------------------------------------------------------------- Información de la máquina --------------------------------------------------------------- -->
            <div class="w-full bg-white border shadow-md rounded-lg self-start sm:col-span-2 md:sticky md:top-4">
                <h3 class="block w-full bg-gray-200 font-bold text-xl text-center break-words p-2 rounded-md">
                    {{ $maquina->codigo }}
                </h3>

                <!-- Mostrar los productos en la máquina -->
                @if ($maquina->productos->isEmpty())
                    <p>No hay productos en esta máquina.</p>
                @else
                    <ul class="list-none p-6 break-words">
                        @foreach ($maquina->productos->sortBy([['diametro', 'asc'], ['peso_stock', 'asc']]) as $producto)
                            <li class="mb-1">
                                <div class="flex items-center justify-between">
                                    <div class="flex flex-col">
                                        <span><strong>Ø</strong> {{ $producto->diametro_mm }}</span>
                                        @if (strtoupper($producto->tipo === 'BARRA'))
                                            <span><strong>L:</strong> {{ $producto->longitud_metros }}</span>
                                        @endif
                                        <a href="{{ route('productos.index', ['id' => $producto->id]) }}"
                                            class="btn btn-sm btn-primary mb-2">Ver</a>
                                    </div>
                                    <div class="flex flex-col">
                                        <a href="{{ route('productos.consumir', $producto->id) }}"
                                            class="btn btn-sm btn-primary mb-2">❌</a>
                                    </div>

                                    @if (strtoupper($producto->tipo == 'ENCARRETADO'))
                                        <div id="progreso-container-{{ $producto->id }}"
                                            class="ml-4 relative w-20 h-20 bg-gray-300 overflow-hidden rounded-lg">
                                            <div id="progreso-barra-{{ $producto->id }}"
                                                class="absolute bottom-0 w-full bg-green-500"
                                                style="height: {{ ($producto->peso_stock / max($producto->peso_inicial, 1)) * 100 }}%;">
                                            </div>
                                            <span id="progreso-texto-{{ $producto->id }}"
                                                class="absolute top-2 left-2 text-white text-xs font-semibold">
                                                {{ $producto->peso_stock }} / {{ $producto->peso_inicial }} kg
                                            </span>
                                        </div>
                                    @elseif(strtoupper($producto->tipo == 'BARRA'))
                                        <div id="progreso-container-{{ $producto->id }}"
                                            class="ml-4 relative w-60 h-10 bg-gray-300 overflow-hidden rounded-lg">
                                            <div class="barra verde"
                                                style="width: {{ ($producto->peso_stock / max($producto->peso_inicial, 1)) * 100 }}%;
                                    height: 100%; 
                                    background-color: green; 
                                    position: absolute; 
                                    right: 0;">
                                            </div>
                                            <span id="progreso-texto-{{ $producto->id }}"
                                                class="absolute top-2 left-2 text-white text-xs font-semibold">
                                                {{ $producto->peso_stock }} / {{ $producto->peso_inicial }} kg
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </li>
                            <hr>
                        @endforeach
                    </ul>
                @endif
                <div class="flex flex-col gap-2 p-4">
                    <!-- Botón Reportar Incidencia -->
                    <button onclick="document.getElementById('modalIncidencia').classList.remove('hidden')"
                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow-md w-full sm:w-auto">
                        🚨 Reportar Incidencia
                    </button>

                    <!-- Botón Realizar Chequeo de Máquina -->
                    <button onclick="document.getElementById('modalCheckeo').classList.remove('hidden')"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow-md w-full sm:w-auto">
                        🛠️ Chequeo de Máquina
                    </button>
                </div>
            </div>
            <!-- --------------------------------------------------------------- Planificación para la máquina agrupada por etiquetas --------------------------------------------------------------- -->
            <div class="bg-white border p-2 shadow-md w-full rounded-lg sm:col-span-4">

                @php

                    function debeSerExcluido($elemento)
                    {
                        // Verificar si el elemento tiene un paquete directo
                        $tienePaqueteDirecto = !is_null($elemento->paquete_id);

                        // Verificar si el elemento tiene subpaquetes y si todos están en un paquete
                        $tieneTodosLosSubpaquetesEnPaquete =
                            $elemento->subpaquetes->isNotEmpty() &&
                            $elemento->subpaquetes->every(function ($subpaquete) {
                                return !is_null($subpaquete->paquete_id);
                            });

                        // Verificar si el estado es "completado"
                        $estaCompletado = strtolower($elemento->estado) === 'completado';

                        // Excluir solo si el elemento tiene un paquete directo O si todos sus subpaquetes están en un paquete Y además está completado
                        return ($tienePaqueteDirecto || $tieneTodosLosSubpaquetesEnPaquete) && $estaCompletado;
                    }

                    if (stripos($maquina->tipo, 'ensambladora') !== false) {
                        $elementosAgrupados = $elementosMaquina
                            ->filter(function ($elemento) {
                                return !debeSerExcluido($elemento);
                            })
                            ->groupBy('etiqueta_id')
                            ->map(function ($grupo) {
                                return $grupo->filter(function ($elemento) {
                                    return strtolower(optional($elemento->etiquetaRelacion)->estado ?? '') ===
                                        'ensamblando';
                                });
                            })
                            ->filter(function ($grupo) {
                                return $grupo->isNotEmpty();
                            });
                    } elseif (stripos($maquina->nombre, 'Soldadora') !== false) {
                        $elementosAgrupados = $maquina
                            ->elementosTerciarios()
                            ->where('maquina_id_3', $maquina->id)
                            ->get()
                            ->filter(function ($item) {
                                return !debeSerExcluido($item) &&
                                    strtolower(optional($item->etiquetaRelacion)->estado ?? '') === 'soldando';
                            })
                            ->groupBy(function ($item) {
                                return $item->etiqueta_id . '-' . $item->marca;
                            });
                    } else {
                        $elementosAgrupados = $elementosMaquina
                            ->filter(function ($elemento) {
                                return !debeSerExcluido($elemento);
                            })
                            ->groupBy('etiqueta_id');
                    }
                    // Ordenar los grupos por la fecha_estimada_entrega de la planilla sin alterar el orden interno
                    $elementosAgrupados = $elementosAgrupados->sortBy(
                        fn($grupo) => optional($grupo->first()->planilla)->fecha_estimada_entrega,
                    );

                    $elementosAgrupadosScript = $elementosAgrupados
                        ->map(function ($grupo) {
                            return [
                                'etiqueta' => $grupo->first()->etiquetaRelacion ?? null,
                                'planilla' => $grupo->first()->planilla ?? null,
                                'elementos' => $grupo
                                    ->filter(function ($elemento) {
                                        return !debeSerExcluido($elemento);
                                    })
                                    ->map(function ($elemento) {
                                        return [
                                            'id' => $elemento->id,
                                            'dimensiones' => $elemento->dimensiones,
                                            'estado' => $elemento->estado,
                                            'peso' => $elemento->peso_kg,
                                            'diametro' => $elemento->diametro_mm,
                                            'longitud' => $elemento->longitud_cm,
                                            'barras' => $elemento->barras,
                                            'figura' => $elemento->figura,
                                        ];
                                    })
                                    ->values(),
                            ];
                        })
                        ->values();
                @endphp

                @forelse ($elementosAgrupados as $etiquetaId => $elementos)
                    @php
                        $firstElement = $elementos->first();
                        if ($firstElement) {
                            $etiqueta = $firstElement->etiquetaRelacion;
                            $planilla = $firstElement->planilla;
                        } else {
                            $etiqueta = null;
                            $planilla = null;
                        }
                        $tieneElementosEnOtrasMaquinas =
                            isset($otrosElementos[$etiquetaId]) && $otrosElementos[$etiquetaId]->isNotEmpty();

                    @endphp

                    <div
                        class="{{ isset($planilla) && str_contains(strtolower(optional($planilla)->ensamblado ?? ''), 'taller') ? 'bg-red-200' : 'bg-yellow-200' }} p-6 rounded-lg shadow-md mt-4">
                        <h2 class="text-lg font-semibold text-gray-700">
                            <strong> {{ optional($planilla)->codigo_limpio }}
                            </strong>
                        </h2>
                        <h3 class="text-lg font-semibold text-gray-800">
                            {{ $etiqueta->id_et ?? 'N/A' }} {{ $etiqueta->nombre ?? 'Sin nombre' }} -
                            {{ $etiqueta->marca ?? 'Sin Marca' }}
                            (Número: {{ $etiqueta->numero_etiqueta ?? 'Sin número' }})
                            - {{ $etiqueta->peso_kg ?? 'N/A' }}
                        </h3>
                        <!-- Contenedor oculto para generar el QR -->
                        <div id="qrContainer-{{ $etiqueta->id ?? 'N/A' }}" style="display: none;"></div>

                        <div class="mb-4 bg-yellow-100 p-2 rounded-lg">
                            <p>
                                <strong>Fecha Inicio:</strong>
                                <span id="inicio-{{ $etiqueta->id ?? 'N/A' }}">
                                    {{ $etiqueta->fecha_inicio ?? 'No asignada' }}
                                </span>
                                <strong>Fecha Finalización:</strong>
                                <span id="final-{{ $etiqueta->id ?? 'N/A' }}">
                                    {{ $etiqueta->fecha_finalizacion ?? 'No asignada' }}
                                </span>
                                <span id="emoji-{{ $etiqueta->id ?? 'N/A' }}"></span><br>
                                <strong> Estado: </strong>
                                <span id="estado-{{ $etiqueta->id ?? 'N/A' }}">{{ $etiqueta->estado ?? 'N/A' }}</span>
                            </p>
                        </div>

                        <hr style="border: 1px solid black; margin: 10px 0;">
                        <!-- 🔹 Elementos de la misma etiqueta en otras máquinas -->
                        @if (isset($otrosElementos[$etiqueta?->id]) && $otrosElementos[$etiqueta?->id]->isNotEmpty())
                            <h4 class="font-semibold text-red-700 mt-6 mb-6">⚠️ Hay elementos en otras máquinas, crea un
                                paquete con la etiqueta e imprimme QR!!</h4>
                            {{-- <div class="bg-red-100 p-4 rounded-lg shadow-md">
                                @foreach ($otrosElementos[$etiqueta->id] as $elementoOtro)
                                    <p class="text-gray-600">
                                        <strong>ID:</strong> {{ $elementoOtro->id_el }} |
                                        <strong>Máquina:</strong> {{ $elementoOtro->maquina->nombre }} |
                                        <strong>Peso:</strong> {{ $elementoOtro->peso_kg }} kg |
                                        <strong>Dimensiones:</strong> {{ $elementoOtro->dimensiones ?? 'No asignado' }}
                                        <strong>Estado:</strong> {{ $elementoOtro->estado }}
                                    </p>
                                    <hr class="my-2">
                                @endforeach
                            </div> --}}
                        @endif
                        <!-- GRID PARA ELEMENTOS -->
                        <div class="grid grid-cols-1 gap-1">
                            @foreach ($elementos as $elemento)
                                <div id="elemento-{{ $elemento->id }}"
                                    class="bg-white p-2 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                                    <p class="text-gray-600 text-sm">
                                        <strong>{{ $loop->iteration }} </strong> {{ $elemento->id_el }} -
                                        <strong>Peso:</strong> {{ $elemento->peso_kg }}
                                        -
                                        <strong>Ø</strong> {{ $elemento->diametro_mm }}
                                        <!-- Botón para Subpaquetar -->
                                        @if ($elemento->peso > 500 || $elemento->barras > 30)
                                            <button onclick="mostrarModalSubpaquete({{ $elemento->id }})"
                                                class="p-1 ml-4 bg-blue-500 text-white rounded hover:bg-blue-700">
                                                ➕ Subpaquetar
                                            </button>
                                        @endif
                                    </p>
                                    {{-- @if ($tieneElementosEnOtrasMaquinas)
                                         <p class="text-gray-600 text-sm">
                                            <strong>Fecha Inicio:</strong>
                                            <span id="inicio-{{ $elemento->id }}">
                                                {{ $elemento->fecha_inicio ?? 'No asignada' }}
                                            </span>
                                            <strong>Fecha Finalización:</strong>
                                            <span id="final-{{ $elemento->id }}">
                                                {{ $elemento->fecha_finalizacion ?? 'No asignada' }}
                                            </span>
                                            <span id="emoji-{{ $elemento->id }}"></span><br>
                                            <strong> Estado: </strong>
                                            <span id="estado-{{ $elemento->id }}">{{ $elemento->estado }}</span>
                                        </p> --}}

                                    <!-- Si el elemento NO tiene subpaquetes, mostrar botón QR del elemento -->
                                    {{-- @if ($elemento->subpaquetes->isEmpty())
                                            <button
                                                onclick="generateAndPrintQR('{{ $elemento->id }}', '{{ $elemento->planilla->codigo_limpio }}', 'ELEMENTO')"
                                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                                QR Elemento
                                            </button>
                                        @endif
                            @endif  --}}
                                    <!-- SUBPAQUETES RELACIONADOS -->
                                    @if ($elemento->subpaquetes->isNotEmpty())
                                        <div class="bg-gray-100 p-3 rounded-lg mt-3 shadow-md">
                                            <h4 class="font-bold text-gray-700">🔹 Subpaquetes:</h4>
                                            <ul class="list-none">
                                                @foreach ($elemento->subpaquetes as $subpaquete)
                                                    <li class="bg-white p-2 rounded-lg shadow-sm mt-2">
                                                        <button
                                                            onclick="generateAndPrintQR('{{ $subpaquete->id }}', '{{ $elemento->planilla->codigo_limpio }}', 'SUBPAQUETE')"
                                                            class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                                                            QR Subpaquete #{{ $subpaquete->id }}
                                                        </button>
                                                        <p><strong>#</strong>{{ $subpaquete->id }}</p>
                                                        <p><strong>Peso:</strong> {{ $subpaquete->peso }} kg</p>
                                                        <p><strong>Cantidad:</strong> {{ $subpaquete->cantidad }}</p>
                                                        <p><strong>Descripción:</strong>
                                                            {{ $subpaquete->descripcion ?? 'Sin descripción' }}</p>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif



                                </div>
                            @endforeach
                        </div>
                        <!-- Contenedor para el canvas -->
                        <div id="canvas-container" style="width: 100%; border: 1px solid #ccc; border-radius: 8px;">
                            <canvas id="canvas-etiqueta-{{ $etiqueta->id ?? 'N/A' }}" class="border"></canvas>
                        </div>

                    </div>
                @empty
                    <div class="col-span-4 text-center py-4 text-gray-600">
                        No hay elementos disponibles para esta máquina.
                    </div>
                @endforelse
            </div>
            <!-- Modal para Crear Subpaquete -->
            <div id="modalSubpaquete"
                class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center">
                <div class="bg-white p-6 rounded-lg shadow-lg w-96">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">➕ Crear Subpaquete</h2>

                    <form id="formSubpaquete" method="POST" action="{{ route('subpaquetes.store') }}">
                        @csrf
                        <input type="hidden" name="elemento_id" id="subpaquete_elemento_id">

                        <label for="peso" class="block text-sm font-medium text-gray-700 mb-1">Peso</label>
                        <input type="number" step="0.01" name="peso" id="peso"
                            class="w-full border rounded p-2 mb-4" placeholder="Peso en kg">

                        <label for="cantidad" class="block text-sm font-medium text-gray-700 mb-1">Cantidad</label>
                        <input type="number" name="cantidad" id="cantidad" class="w-full border rounded p-2 mb-4"
                            value="1">

                        <label for="descripcion"
                            class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea name="descripcion" id="descripcion" class="w-full border rounded p-2 mb-4" rows="3"
                            placeholder="Detalles del subpaquete"></textarea>

                        <div class="flex justify-end mt-4">
                            <button type="button"
                                onclick="document.getElementById('modalSubpaquete').classList.add('hidden')"
                                class="mr-2 px-4 py-2 bg-gray-500 text-white rounded">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded">
                                Crear
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- --------------------------------------------------------------- GRID PARA OTROS --------------------------------------------------------------- -->
            <div class="bg-white border p-4 shadow-md rounded-lg self-start sm:col-span-2 md:sticky md:top-4">
                <div class="flex flex-col gap-4">
                    <!-- Input de lectura de QR -->
                    <div class="bg-white border p-2 shadow-md rounded-lg self-start sm:col-span-1 md:sticky">
                        <h3 class="font-bold text-xl">PROCESO ETIQUETA</h3>
                        <input type="text" id="procesoEtiqueta" class="w-full border p-2 rounded"
                            placeholder="Escanea un QR..." autofocus>
                        <div id="maquina-info" data-maquina-id="{{ $maquina->id }}"></div>
                    </div>

                    <!-- Sistema de inputs para crear paquetes -->
                    <div class="bg-gray-100 border p-2 mb-2 shadow-md rounded-lg">
                        <h3 class="font-bold text-xl">Crear Paquete</h3>
                        <div class="mb-2">
                            <label for="itemType" class="block text-gray-700 font-semibold">Selecciona el
                                tipo:</label>
                            <select id="itemType" class="border rounded p-2 w-full">
                                <option value="Etiqueta">Etiqueta</option>
                                <option value="Elemento">Elemento</option>
                                <option value="Subpaquete">Subpaquete</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="qrItem" class="block text-gray-700 font-semibold">Escanear QR:</label>
                            <input type="text" id="qrItem" class="border rounded p-2 w-full"
                                placeholder="Escanea o ingresa un código QR">
                        </div>

                        <!-- Listado dinámico de items -->
                        <div class="mb-4">
                            <h4 class="font-semibold text-gray-700 mb-2">Items agregados:</h4>
                            <ul id="itemsList" class="list-disc pl-6 space-y-2">
                                <!-- Los items se agregarán aquí dinámicamente -->
                            </ul>
                        </div>

                        <!-- Botón para crear el paquete -->
                        <button id="crearPaqueteBtn"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow-md w-full">
                            📦 Crear Paquete
                        </button>

                    </div>
                </div>
                <!-- ---------------------------------------- ELIMINAR PAQUETE ------------------------------- -->
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <label for="paquete_id" class="block text-gray-700 font-semibold mb-2">
                        ID del Paquete a Eliminar:
                    </label>
                    <input type="number" name="paquete_id" id="paquete_id" required
                        class="w-full border p-2 rounded mb-2" placeholder="Ingrese ID del paquete">
                    <button type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow-md mt-2">
                        🗑️ Eliminar Paquete
                    </button>
                </form>

                <script>
                    document.getElementById('deleteForm').addEventListener('submit', function(event) {
                        event.preventDefault(); // Evita el envío inmediato

                        const paqueteId = document.getElementById('paquete_id').value;

                        if (!paqueteId) {
                            Swal.fire({
                                icon: "warning",
                                title: "Campo vacío",
                                text: "Por favor, ingrese un ID válido.",
                                confirmButtonColor: "#3085d6",
                            });
                            return;
                        }

                        Swal.fire({
                            title: "¿Estás seguro?",
                            text: "Esta acción no se puede deshacer.",
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonColor: "#d33",
                            cancelButtonColor: "#3085d6",
                            confirmButtonText: "Sí, eliminar",
                            cancelButtonText: "Cancelar"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                this.action = "/paquetes/" + paqueteId; // Modifica la acción con el ID
                                this.submit(); // Envía el formulario
                            }
                        });
                    });
                </script>

            </div>

            <!-- Modal Reportar Incidencia (Oculto por defecto) -->
            <div id="modalIncidencia"
                class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center">
                <div class="bg-white p-6 rounded-lg shadow-lg w-96">

                    <!-- Formulario -->
                    <form method="POST" action="{{ route('alertas.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="mensaje" class="block text-sm font-semibold">Mensaje:</label>
                            <textarea id="mensaje" name="mensaje" rows="3"
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500" required>{{ old('mensaje') }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label for="categoria" class="block text-sm font-semibold">Destinatarios
                                particulares</label>
                            <select id="categoria" name="categoria" class="w-full border rounded-lg p-2">
                                <option value="">-- Seleccionar una Categoría --</option>
                                <option value="administracion">Administración</option>
                                <option value="mecanico">Mecánico</option>
                                <option value="programador">Programador</option>
                            </select>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end space-x-2">
                            <button type="button"
                                onclick="document.getElementById('modalIncidencia').classList.add('hidden')"
                                class="bg-gray-400 hover:bg-gray-500 text-white py-2 px-4 rounded-lg">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Chequeo de Máquina (Oculto por defecto) -->
            <div id="modalCheckeo"
                class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center">
                <div class="bg-white p-6 rounded-lg shadow-lg w-96">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">🛠️ Chequeo de Máquina</h2>

                    <form id="formCheckeo">
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2" name="limpieza">
                                🔹 Máquina limpia y sin residuos
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2" name="herramientas">
                                🔹 Herramientas en su ubicación correcta
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2" name="lubricacion">
                                🔹 Lubricación y mantenimiento básico realizado
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2" name="seguridad">
                                🔹 Elementos de seguridad en buen estado
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2" name="registro">
                                🔹 Registro de incidencias actualizado
                            </label>
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex justify-end mt-4">
                            <button type="button"
                                onclick="document.getElementById('modalCheckeo').classList.add('hidden')"
                                class="mr-2 px-4 py-2 bg-gray-500 text-white rounded">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">
                                Guardar Chequeo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        function mostrarModalSubpaquete(elementoId) {
            document.getElementById('subpaquete_elemento_id').value = elementoId;
            document.getElementById('modalSubpaquete').classList.remove('hidden');
        }

        const maquinaId = @json($maquina->id);
        const ubicacionId = @json(optional($ubicacion)->id); // Esto puede ser null si no se encontró

        window.etiquetasData =
            @json($etiquetasData); // Ej.: [{ codigo: "3718", elementos: [27906,27907,...], pesoTotal: 155.55 }, ...]
        window.pesosElementos = @json($pesosElementos); // Ej.: { "27906": "77.81", "27907": "3.87", ... }
        window.subpaquetesData = @json($subpaquetesData); // Ej.: { "sub001": 27906, "sub002": 27907, ... }
        console.log("Datos precargados de etiquetas:", window.etiquetasData);
        console.log("Pesos precargados de elementos:", window.pesosElementos);
        console.log("Datos precargados de subpaquetes:", window.subpaquetesData);

        let elementosEnUnaSolaMaquina = @json($elementosEnUnaSolaMaquina->pluck('id')->toArray());
        let etiquetasEnUnaSolaMaquina = @json($etiquetasEnUnaSolaMaquina);
    </script>
    <!-- SCRIPT PARA IMPRIMIR QR -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="{{ asset('js/maquinaJS/trabajoEtiqueta.js') }}"></script>
    <script src="{{ asset('js/imprimirQrS.js') }}"></script>
    <script>
        window.elementosAgrupadosScript = @json($elementosAgrupadosScript);
    </script>
    <script src="{{ asset('js/maquinaJS/canvasMaquina.js') }}"></script>
    <script src="{{ asset('js/maquinaJS/crearPaquetes.js') }}" defer></script>
</x-app-layout>
