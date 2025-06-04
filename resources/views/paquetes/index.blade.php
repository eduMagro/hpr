<x-app-layout>
    <x-slot name="title">Paquetes - {{ config('app.name') }}</x-slot>
    @php
        $rutaActual = request()->route()->getName();
    @endphp

    @if (auth()->user()->rol !== 'operario')
        <div class="w-full" x-data="{ open: false }">
            <!-- Menú móvil -->
            <div class="sm:hidden relative" x-data="{ open: false }">
                <button @click="open = !open"
                    class="w-1/2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 shadow transition">
                    Opciones
                </button>

                <div x-show="open" x-transition @click.away="open = false"
                    class="absolute z-30 mt-0 w-1/2 bg-white border border-gray-200 rounded-b-lg shadow-xl overflow-hidden divide-y divide-gray-200"
                    x-cloak>

                    <a href="{{ route('planillas.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'planillas.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        📄 Planillas
                    </a>

                    <a href="{{ route('paquetes.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'paquetes.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        📦 Paquetes
                    </a>

                    <a href="{{ route('etiquetas.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'etiquetas.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        🏷️ Etiquetas
                    </a>

                    <a href="{{ route('elementos.index') }}"
                        class="block px-2 py-3 transition text-sm font-medium 
                    {{ $rutaActual === 'elementos.index' ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-blue-700 hover:bg-blue-50 hover:text-blue-900' }}">
                        🔩 Elementos
                    </a>
                </div>
            </div>

            <!-- Menú escritorio -->
            <div class="hidden sm:flex sm:mt-0 w-full">
                <a href="{{ route('planillas.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none first:rounded-l-lg transition font-semibold
                {{ $rutaActual === 'planillas.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    📄 Planillas
                </a>

                <a href="{{ route('paquetes.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ $rutaActual === 'paquetes.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    📦 Paquetes
                </a>

                <a href="{{ route('etiquetas.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none transition font-semibold
                {{ $rutaActual === 'etiquetas.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    🏷️ Etiquetas
                </a>

                <a href="{{ route('elementos.index') }}"
                    class="flex-1 text-center px-4 py-2 rounded-none last:rounded-r-lg transition font-semibold
                {{ $rutaActual === 'elementos.index' ? 'bg-blue-800 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                    🔩 Elementos
                </a>
            </div>
        </div>
    @endif

    <div class="w-full p-4 sm:p-6">

        <!-- Contenedor de la tabla -->
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[800px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-left text-sm text-center uppercase">
                        <th class="p-2 border">ID
                            <!-- Formulario de búsqueda por ID -->
                            <form method="GET" action="{{ route('paquetes.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="id"
                                    class="w-20 px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="p-2 border">Planilla
                            <!-- Formulario de búsqueda por ID -->
                            <form method="GET" action="{{ route('paquetes.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="planilla"
                                    class="w-20 px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="p-2 border">Ubicación</th>
                        <th class="p-2 border">Elementos</th>
                        <th class="p-2 border">Peso (Kg)</th>
                        <th class="p-2 border">Fecha Creación</th>
                        <th class="p-2 border">Fecha Límite Reparto</th>
                        <th class="p-2 border text-center">Acciones</th>
                    </tr>
                </thead>
                </tbody>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($paquetes as $paquete)

                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">
                            <td class="p-2 text-center border">{{ $paquete->id }}</td>
                            <td class="p-2 text-center border">
                                <a href="{{ route('planillas.index', ['planilla_id' => $paquete->planilla->id]) }}"
                                    class="text-blue-500 hover:underline">
                                    {{ $paquete->planilla->codigo_limpio }}
                                </a>
                            </td>
                            <td class="p-2 text-center border">
                                {{ $paquete->ubicacion->nombre ?? 'Sin ubicación' }}</td>
                            <td class="p-2 text-center border">
                                @if ($paquete->elementos->isNotEmpty())
                                    @foreach ($paquete->elementos as $elemento)
                                        <ul class="text-sm">
                                            <li>
                                                <a href="{{ route('etiquetas.index', ['id' => $elemento->etiquetaRelacion->id]) }}"
                                                    class="text-blue-500 hover:underline">
                                                    {{ $elemento->etiquetaRelacion->nombre }}
                                                    (#{{ $elemento->etiquetaRelacion->id }})
                                                </a>
                                                <a href="{{ route('elementos.index', ['id' => $elemento->id]) }}"
                                                    class="text-green-500 hover:underline">
                                                    #{{ $elemento->id }} - FIGURA {{ $elemento->figura }}
                                                </a>
                                            </li>
                                        </ul>
                                    @endforeach
                                @else
                                    <span class="text-gray-500">Vacío</span>
                                @endif
                            </td>
                            <td class="p-2 text-center border">{{ $paquete->peso }} Kg
                            </td>
                            <td class="p-2 text-center border">{{ $paquete->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="p-2 text-center border">
                                {{ optional($paquete->planilla->fecha_estimada_reparto)->format('d/m/Y') ?? 'No disponible' }}
                            </td>
                            <td class="p-2 text-center border flex flex-row justify-center gap-4">
                                <button
                                    onclick="generateAndPrintQR('{{ $paquete->id }}', '{{ $paquete->planilla->codigo_limpio }}', 'PAQUETE')"
                                    class="p-2 bg-green-500 text-white rounded hover:bg-green-600"><i
                                        class="fas fa-qrcode"></i>
                                </button>
                                <button onclick="mostrarDibujo({{ $paquete->id }})"
                                    class="text-blue-500 hover:underline">
                                    Ver
                                </button>
                                <x-boton-eliminar :action="route('paquetes.destroy', $paquete->id)" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-gray-500">No hay paquetes registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="mt-4 flex justify-center">{{ $paquetes->onEachSide(2)->links('vendor.pagination.bootstrap-5') }}
        </div>
        <!-- Modal con Canvas para Dibujar las Dimensiones -->
        <div id="modal-dibujo"
            class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center p-4">
            <div
                class="bg-white p-4 sm:p-6 rounded-lg w-full sm:w-auto max-w-[95vw] max-h-[90vh] flex flex-col shadow-lg relative">
                <button id="cerrar-modal" class="absolute top-2 right-2 text-red-600 hover:bg-red-100">
                    ✖
                </button>

                <h2 class="text-xl font-semibold mb-4 text-center">Elementos del paquete</h2>
                <!-- Contenedor desplazable -->
                <div class="overflow-y-auto flex-1 min-h-0" style="max-height: 75vh;">
                    <canvas id="canvas-dibujo" width="800" height="600" class="border max-w-full h-auto"></canvas>
                </div>
            </div>
        </div>
        <script src="{{ asset('js/paquetesJs/figurasPaquete.js') }}" defer></script>
        <script>
            window.paquetes = @json($paquetes->items());
        </script>
        <!-- SCRIPT PARA IMPRIMIR QR -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script src="{{ asset('js/imprimirQrS.js') }}"></script>
</x-app-layout>
