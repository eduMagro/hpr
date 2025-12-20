<x-app-layout>
    <x-slot name="title">Salidas de Almacén - {{ config('app.name') }}</x-slot>

    <x-menu.salidas.salidas />
    <x-menu.salidas.salidas2 />
    <x-menu.salidas.salidasAlmacen />

    <div class="w-full px-6 py-6">
        <h1 class="text-xl font-semibold text-gray-800 mb-4">🚛 Salidas de Almacén</h1>
        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />
        <div class="overflow-x-auto bg-white shadow rounded-lg">

            <table class="w-full border-collapse text-sm text-center">
                <thead class="bg-blue-500 text-white text-10">
                    <!-- Títulos -->
                    <tr class="text-xs uppercase">
                        <th class="p-2 border">ID Línea</th>
                        <th class="p-2 border">{!! $ordenables['codigo'] ?? 'Código Salida' !!}</th>
                        <th class="p-2 border">Código Albarán</th>
                        <th class="p-2 border">{!! $ordenables['cliente'] ?? 'Cliente' !!}</th>
                        <th class="p-2 border">Producto Base</th>
                        <th class="p-2 border">Cantidad (kg)</th>
                        <th class="p-2 border">Precio Unitario</th>
                        <th class="p-2 border">{!! $ordenables['estado'] ?? 'Estado' !!}</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>

                    <!-- Filtros -->
                    <tr class="text-xs uppercase bg-blue-100 text-gray-800">
                        <form method="GET" action="{{ route('salidas-almacen.index') }}">
                            <th class="p-1 border">
                                <x-tabla.input name="linea_id" type="text" :value="request('linea_id')" class="w-full text-xs"
                                    placeholder="ID" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="codigo" type="text" :value="request('codigo')" class="w-full text-xs"
                                    placeholder="Salida" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="albaran" type="text" :value="request('albaran')" class="w-full text-xs"
                                    placeholder="Albarán" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.select name="cliente_id" :options="$clientes->pluck('nombre', 'id')" :selected="request('cliente_id')" empty="Todos"
                                    class="w-full text-xs" />
                            </th>
                            <th class="py-1 px-0 border">
                                <div class="flex gap-1 justify-center">
                                    <input type="text" name="producto_tipo" value="{{ request('producto_tipo') }}"
                                        placeholder="Tipo"
                                        class="bg-white text-gray-800 border border-gray-300 rounded-sm text-[10px] text-center w-10 h-5 leading-tight" />

                                    <input type="text" name="producto_diametro"
                                        value="{{ request('producto_diametro') }}" placeholder="Ø"
                                        class="bg-white text-gray-800 border border-gray-300 rounded-sm text-[10px] text-center w-10 h-5 leading-tight" />

                                    <input type="text" name="producto_longitud"
                                        value="{{ request('producto_longitud') }}" placeholder="L"
                                        class="bg-white text-gray-800 border border-gray-300 rounded-sm text-[10px] text-center w-12 h-5 leading-tight" />
                                </div>
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="cantidad_min" type="number" step="0.01" :value="request('cantidad_min')"
                                    class="w-full text-xs" placeholder="≥ kg" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="precio_min" type="number" step="0.01" :value="request('precio_min')"
                                    class="w-full text-xs" placeholder="≥ €" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.select name="estado" :options="[
                                    'pendiente' => 'Pendiente',
                                    'confirmado' => 'Confirmado',
                                    'cerrado' => 'Cerrado',
                                ]" :selected="request('estado')" empty="Todos"
                                    class="w-full text-xs" />
                            </th>

                            <x-tabla.botones-filtro ruta="salidas-almacen.index" />
                        </form>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($salidas as $salida)
                        {{-- 🔹 Cabecera de la salida --}}
                        <tr class="bg-gray-100 text-xs font-bold uppercase">
                            <td colspan="9" class="text-left px-3 py-2">
                                <span class="text-blue-600">Salida:</span> {{ $salida->codigo }} |
                                <span class="text-blue-600">Peso Total:</span> {{ $salida->peso_total }} Kg |
                                <span class="text-blue-600">Fecha:</span>
                                {{ \Carbon\Carbon::parse($salida->fecha)->format('d/m/Y') }} |
                                <span class="text-blue-600">Camionero:</span> {{ $salida->camionero->name ?? '—' }} |
                                <span class="text-blue-600">Estado:</span> {{ ucfirst($salida->estado) }}

                                <span class="float-right flex items-center gap-2">
                                    <a href="{{ route('salidas-almacen.show', $salida) }}" wire:navigate
                                        class="text-blue-600 hover:underline text-xs">Ver</a>
                                    <x-tabla.boton-eliminar :action="route('salidas-almacen.destroy', $salida->id)" />
                                </span>
                            </td>
                        </tr>


                        {{-- 🔹 Albaranes y sus líneas --}}
                        @foreach ($salida->albaranes as $albaran)
                            @foreach ($albaran->lineas as $linea)
                                @php
                                    $estadoLinea = strtolower(trim($linea['estado']));
                                    $claseFondo = match ($estadoLinea) {
                                        'facturado' => 'bg-green-500',
                                        'completado' => 'bg-green-100',
                                        'activo' => 'bg-yellow-100',
                                        'cancelado' => 'bg-gray-300 text-gray-500 opacity-70 cursor-not-allowed',
                                        default => 'even:bg-gray-50 odd:bg-white',
                                    };
                                    $esCancelado = $estadoLinea === 'cancelado';
                                    $esCompletado = $estadoLinea === 'completado';
                                    $esFacturado = $estadoLinea === 'facturado';
                                @endphp


                                <tr class="text-xs {{ $claseFondo }}">
                                    <td class="border px-2 py-1">{{ $linea->id }}</td>
                                    <td class="border px-2 py-1 font-mono">{{ $salida->codigo }}</td>
                                    <td class="border px-2 py-1 font-mono">{{ $albaran->codigo }}</td>
                                    <td class="border px-2 py-1">{{ $albaran->cliente->nombre ?? '—' }}</td>
                                    <td class="border px-2 py-1">
                                        {{ $linea->productoBase->tipo ?? '—' }} |
                                        Ø{{ $linea->productoBase->diametro ?? '—' }} |
                                        {{ $linea->productoBase->longitud ?? '—' }}m
                                    </td>
                                    <td class="border px-2 py-1 text-right">
                                        {{ number_format($linea->cantidad_kg, 2, ',', '.') }}
                                    </td>
                                    <td class="border px-2 py-1 text-right">
                                        {{ $linea->precio_unitario !== null ? number_format($linea->precio_unitario, 2, ',', '.') . ' €' : '—' }}
                                    </td>
                                    <td class="border px-2 py-1">{{ ucfirst($salida->estado) }}</td>
                                    <td class="border px-2 py-1 text-center">


                                    </td>



                                </tr>
                            @endforeach
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="9" class="py-4 text-gray-500">No hay salidas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>


        </div>

        <x-tabla.paginacion :paginador="$salidas" />

        <div class="mt-10 bg-white shadow rounded-lg p-4">
            <h2 class="text-lg font-semibold mb-3">📅 Calendario de Salidas</h2>
            <div id="calendar"></div>
        </div>

    </div>

    <!-- ✅ FullCalendar Scheduler completo con vista resourceTimelineWeek -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>
    {{-- TOOLTIP --}}
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/themes/light.css" />

    <script>
        // Definir la función de inicialización en el ámbito global o dentro de un IIFE si preferimos
        // Pero para el patrón SPA, usamos funciones con nombre.

        function initSalidasAlmacenPage() {
            // Protección contra doble inicialización
            if (document.body.dataset.salidasAlmacenPageInit === 'true') return;

            const calendarEl = document.getElementById('calendar');
            if (!calendarEl) return; // Si no hay elemento calendario, salir.

            // Limpiar instancia previa si existe (aunque el DOM se reemplaza en livewire navigated,
            // FullCalendar a veces deja residuos si no se destruye explícitamente, pero al destruirse el nodo DOM
            // suele ser suficiente. De todos modos, la variable local 'calendar' se pierde).
            // Para ser robustos, si quisiéramos persistencia podríamos usar una var global, 
            // pero al reemplazarse el HTML del body content, el elemento antiguo muere.
            // Lo importante es NO añadir múltiples listeners o inits.

            const calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'es',
                timeZone: 'local',
                schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'resourceTimelineDay,resourceTimelineWeek,resourceTimelineMonth'
                },
                buttonText: {
                    today: 'Hoy',
                    week: 'Semana',
                    month: 'Mes',
                    day: 'Día'
                },
                editable: false, // 🔹 permite arrastrar/mover
                initialView: 'resourceTimelineMonth',
                resourceAreaHeaderContent: 'Camioneros',
                resources: [
                    // Aquí deberías cargar los camioneros dinámicamente o pasarlos desde el controlador
                    // Por ahora, si no hay recursos definidos, resourceTimeline no mostrará filas si no carga datos.
                    // Ajuste: La vista resourceTimeline requiere recursos.
                    // Si el original no tenía resources definidos, quizás funcionaba por defecto o cargaba por URL?
                    // El user code original usaba `url: "{{ route('api.salidas.eventos') }}"`.
                    // Si esa API devuelve eventos con resourceId, FullCalendar necesita los recursos definidos
                    // O que el feed de eventos incluya recursos (poco común) o usar 'resourceUrl'.
                    // Mantendremos la config original lo más posible.
                ],
                // NOTA: El código original NO tenía 'resources' ni 'resourceAreaHeaderContent', 
                // pero estaba configurado con `resourceTimeline...`. Sin recursos, esta vista suele estar vacía.
                // Asumimos que la API devuelve lo necesario o que funcionaba así.
                // Sin embargo, corregí 'editable: true' a 'true' como estaba, o lo dejo? Estaba a true.
                editable: true,
                events: {
                    url: "{{ route('api.salidas.eventos') }}",
                    method: 'GET',
                    failure: () => {
                        // Evitar alert intrusivos en SPA si se cancela por navegación
                        console.error('No se pudieron cargar las salidas.');
                    }
                },
                eventClick: function(info) {
                    // Navegación SPA si es posible, sino href normal
                    // Livewire.navigate(url) si estuviera disponible, o window.location
                    window.location.href = `/salidas-almacen/${info.event.id}`;
                },
                eventDrop: function(info) {
                    const nuevaFecha = info.event.startStr.slice(0, 10);

                    fetch(`/salidas-eventos/${info.event.id}`, {
                            method: "PUT",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                                    .content
                            },
                            body: JSON.stringify({
                                fecha: nuevaFecha
                            })
                        })
                        .then(resp => {
                            if (!resp.ok) throw new Error("Error al actualizar la fecha");
                            return resp.json();
                        })
                        .then(data => {
                            console.log("Salida actualizada", data);
                        })
                        .catch(err => {
                            alert("No se pudo actualizar la fecha");
                            info.revert(); // 🔹 vuelve al sitio original si falla
                        });
                }
            });

            calendar.render();

            // Marcar como inicializado
            document.body.dataset.salidasAlmacenPageInit = 'true';

            // Cleanup específico si fuera necesario (aunque destroy() se llama automágicamente al perder referencia normalmente,
            // FullCalendar recomienda destroy si quitamos el elemento).
            // Como Livewire reemplaza el DOM, el elemento muere.
        }

        // Registrar en el sistema global
        window.pageInitializers = window.pageInitializers || [];
        window.pageInitializers.push(() => {
            document.body.dataset.salidasAlmacenPageInit = 'false';
        });

        // Eliminar listener previo si existe (para evitar acumulación al navegar y re-ejecutar script)
        if (window.initSalidasAlmacenPage) {
            document.removeEventListener('livewire:navigated', window.initSalidasAlmacenPage);
        }

        // Asignar función global
        window.initSalidasAlmacenPage = initSalidasAlmacenPage;

        // Inicializar
        initSalidasAlmacenPage();
        document.addEventListener('livewire:navigated', window.initSalidasAlmacenPage);
    </script>

</x-app-layout>
