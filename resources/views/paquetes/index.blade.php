<x-app-layout>
    <x-slot name="title">Paquetes - {{ config('app.name') }}</x-slot>
    <x-menu.planillas />
    <div class="w-full p-4 sm:p-6">

        {{-- BOTÓN PARA IR AL MAPA DE LOCALIZACIONES --}}
        <div class="mb-4 flex justify-end">
            <a href="{{ route('mapa.paquetes', ['obra' => request('nave')]) }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg shadow-md hover:from-blue-700 hover:to-blue-800 transition-all duration-200 transform hover:scale-105">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                <span>Ver Mapa de Localizaciones</span>
            </a>
        </div>

        <!-- Contenedor de la tabla -->
        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[800px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white text-10">
                    <tr class="text-center text-xs uppercase">
                        <th class="p-2 border">{!! $ordenables['id'] ?? 'ID' !!}</th>
                        <th class="p-2 border">{!! $ordenables['codigo'] ?? 'Código' !!}</th>

                        <th class="p-2 border">{!! $ordenables['planilla'] ?? 'Planilla' !!}</th>
                        <th class="p-2 border">{!! $ordenables['nave'] ?? 'Nave' !!}</th>
                        <th class="p-2 border">{!! $ordenables['ubicacion'] ?? 'Ubicación' !!}</th>
                        <th class="p-2 border">{!! $ordenables['elementos'] ?? 'Elementos' !!}</th>
                        <th class="p-2 border">{!! $ordenables['peso'] ?? 'Peso (Kg)' !!}</th>
                        <th class="p-2 border">{!! $ordenables['created_at'] ?? 'Fecha Creación' !!}</th>
                        <th class="p-2 border">{!! $ordenables['fecha_limite'] ?? 'Fecha Límite Reparto' !!}</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>

                    <tr class="text-center text-xs uppercase">
                        <form method="GET" action="{{ route('paquetes.index') }}">
                            <th class="p-1 border">
                                <x-tabla.input name="id" value="{{ request('id') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="codigo" value="{{ request('codigo') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="planilla" value="{{ request('planilla') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="nave" value="{{ request('nave') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="ubicacion" value="{{ request('ubicacion') }}" />
                            </th>
                            <th class="p-1 border"></th> {{-- Elementos: sin filtro --}}
                            <th class="p-1 border"></th> {{-- Peso: sin filtro --}}
                            <th class="p-1 border">
                                <x-tabla.input type="date" name="created_at" value="{{ request('created_at') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input type="date" name="fecha_limite"
                                    value="{{ request('fecha_limite') }}" />
                            </th>
                            <x-tabla.botones-filtro ruta="paquetes.index" />
                        </form>
                    </tr>
                </thead>

                </tbody>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($paquetes as $paquete)
                    <!-- Modal de Detalle de Paquete (Dinamico) -->
                    <div id="modal-detalle-paquete"
                        class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 flex justify-center items-center z-50">
                        <div
                            class="bg-white rounded-xl shadow-xl max-w-5xl w-full max-h-[90vh] overflow-y-auto p-6 relative">
                            {{-- Botón Imprimir todas --}}
                            <button x-data="{
                                    cargando: false,
                                    async imprimir() {
                                        this.cargando = true;
                                
                                        // Esperar 1 frame real para que el DOM actualice el spinner
                                        await new Promise(resolve => requestAnimationFrame(resolve));
                                
                                        // Esperar un poco más si hay canvas implicados
                                        await new Promise(resolve => setTimeout(resolve, 30));
                                
                                        await imprimirTodasDelPaquete({{ $paquete->id }});
                                
                                        this.cargando = false;
                                    }
                                }" x-on:click="imprimir" x-bind:disabled="cargando"
                                class="w-6 h-6 bg-orange-100 text-orange-600 rounded hover:bg-orange-200 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                                title="Imprimir todas las etiquetas del paquete">

                                <span x-show="!cargando">🖨️</span>

                                <svg x-show="cargando" class="h-4 w-4 animate-spin"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4l3.536-3.536A9 9 0 103 12h4z" />
                                </svg>
                            </button>


                            <button onclick="cerrarModalDetalle()"
                                class="absolute top-2 right-2 text-red-600 hover:text-red-800 text-xl font-bold">✖</button>

                            <h2 class="text-2xl font-bold mb-4 text-center">🏷️ Etiquetas del Paquete</h2>

                            <div id="contenido-detalle-paquete" class="space-y-4">
                                <!-- Aquí se insertarán dinámicamente las etiquetas del paquete -->
                            </div>



                        </div>
                    </div>
                    <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">
                        <td class="p-2 text-center border">{{ $paquete->id }}</td>
                        <td class="p-2 text-center border">{{ $paquete->codigo }}</td>
                        <td class="p-2 text-center border">
                            <a href="{{ route('planillas.index', ['planilla_id' => $paquete->planilla->id]) }}"
                                class="text-blue-500 hover:underline">
                                {{ $paquete->planilla->codigo_limpio }}
                            </a>
                        </td>
                        <td class="p-2 text-center border">

                            {{ $paquete->nave->obra ?? '-' }}

                        </td>
                        <td class="p-2 text-center border">
                            {{ $paquete->ubicacion->nombre ?? '-' }}
                        </td>
                        <td class="p-2 text-center border">
                            @if ($paquete->etiquetas->isNotEmpty())
                            @foreach ($paquete->etiquetas as $etiqueta)
                            <p class="font-semibold text-blue-700">
                                🏷️ <a href="{{ route('etiquetas.index', ['id' => $etiqueta->id]) }}"
                                    class="hover:underline">
                                    {{ $etiqueta->nombre }}{{ $etiqueta->etiqueta_sub_id }}
                                </a> –
                                {{ $etiqueta->peso_kg }}
                            </p>
                            @if ($etiqueta->elementos->isNotEmpty())
                            <ul class="ml-2 text-sm text-gray-700">
                                @foreach ($etiqueta->elementos as $elemento)
                                <li>
                                    <a href="{{ route('elementos.index', ['id' => $elemento->id]) }}"
                                        class="text-green-600 hover:underline">
                                        {{ $elemento->codigo }} – {{ $elemento->figura }}
                                        – {{ $elemento->peso_kg }}
                                    </a>
                                </li>
                                @endforeach
                            </ul>
                            @else
                            <p class="ml-2 text-gray-500 text-xs">Sin elementos registrados</p>
                            @endif
                            <hr>
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
                        <td class="p-2 text-center border">
                            <div class="flex flex-row justify-center items-center gap-3">
                                {{-- Botón QR --}}
                                <button
                                    onclick="generateAndPrintQR('{{ $paquete->codigo }}', '{{ $paquete->planilla->codigo_limpio }}', 'PAQUETE')"
                                    class="w-6 h-6 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center"
                                    title="Generar QR">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4 4h4v4H4V4zm6 0h4v4h-4V4zm6 0h4v4h-4V4zM4 10h4v4H4v-4zm6 10h4v-4h-4v4zm6 0h4v-4h-4v4z" />
                                    </svg>
                                </button>

                                {{-- Botón Ver --}}
                                <button onclick="mostrarDetallePaquete({{ $paquete->id }})"
                                    class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center"
                                    title="Ver dibujo del paquete">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>

                                {{-- NUEVO: Botón Mapa de Localizaciones (por paquete individual) --}}
                                <a href="{{ route('mapa.paquetes', ['obra' => $paquete->nave_id, 'paquete' => $paquete->id]) }}"
                                    class="w-6 h-6 bg-purple-100 text-purple-600 rounded hover:bg-purple-200 flex items-center justify-center"
                                    title="Ver ubicación en el mapa">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </a>

                                {{-- Botón eliminar --}}
                                <x-tabla.boton-eliminar :action="route('paquetes.destroy', $paquete->id)" />
                            </div>
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
        <x-tabla.paginacion :paginador="$paquetes" />




        <script>
            function mostrarDetallePaquete(paqueteId) {
                const paquete = window.paquetes.find(p => p.id === paqueteId);
                if (!paquete || !paquete.etiquetas) return;

                const contenedor = document.getElementById('contenido-detalle-paquete');
                contenedor.innerHTML = '';

                paquete.etiquetas.forEach((etiqueta) => {
                    const safeId = etiqueta.etiqueta_sub_id.replace(/\./g, '_');
                    const html = `
        <div class="bg-orange-300 border border-black p-4 shadow-md rounded-md">
            <div class="flex justify-between">
                <h3 class="text-lg font-bold text-gray-900">
                    ${etiqueta.etiqueta_sub_id} - ${etiqueta.nombre || 'Sin nombre'}
                </h3>
            </div>
            <p><strong>Código:</strong> ${etiqueta.codigo}</p>
            <p><strong>Peso:</strong> ${etiqueta.peso_kg} kg</p>
            <canvas id="canvas-imprimir-etiqueta-${safeId}" class="w-full border mt-2" height="100"></canvas>
        </div>
    `;
                    contenedor.insertAdjacentHTML('beforeend', html);
                });



                document.getElementById('modal-detalle-paquete').classList.remove('hidden');

                // Espera a que el DOM esté pintado para llamar a la función de dibujo
                setTimeout(() => {
                    const etiquetasFiltradas = window.elementosAgrupadosScript.filter(({
                            etiqueta
                        }) =>
                        paquete.etiquetas.some(e => e.etiqueta_sub_id === etiqueta.etiqueta_sub_id)
                    );

                    dibujarCanvasParaEtiquetas(etiquetasFiltradas);

                }, 100);
            }

            function cerrarModalDetalle() {
                document.getElementById('modal-detalle-paquete').classList.add('hidden');
            }

            //......................................................................................

            window.paquetes = @json($paquetesJson);
            window.elementosAgrupadosScript = @json($elementosAgrupadosScript);
        </script>
        <!-- SCRIPT PARA IMPRIMIR QR -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script src="{{ asset('js/imprimirQrS.js') }}"></script>
        <script src="{{ asset('js/maquinaJS/canvasMaquinaSinBoton.js') }}" defer></script>
        <script>
            function imprimirTodasDelPaquete(paqueteId) {
                return new Promise((resolve) => {
                    const paquete = window.paquetes.find(p => p.id === paqueteId);
                    if (!paquete || !paquete.etiquetas) return resolve();

                    const etiquetaIds = paquete.etiquetas.map(e => e.etiqueta_sub_id.replace(/\./g, '_'));

                    // Esperar a que canvas estén dibujados si es necesario
                    setTimeout(() => {
                        imprimirEtiquetasLote(etiquetaIds);
                        resolve();
                    }, 10);
                });
            }
        </script>

</x-app-layout>