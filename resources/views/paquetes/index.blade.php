<x-app-layout>
    <x-slot name="title">Paquetes - {{ config('app.name') }}</x-slot>
    <x-menu.planillas />
    <div class="w-full p-4 sm:p-6">
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
                                {{ $paquete->ubicacion->nombre ?? '-' }}</td>
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

        {{-- Modal para visualizar elementos del paquete --}}
        <div id="modal-dibujo" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center p-4">
            <div class="bg-white p-4 sm:p-6 rounded-lg w-full sm:w-[800px] md:w-[900px] lg:w-[1000px] max-w-[95vw] max-h-[90vh] flex flex-col shadow-lg relative">
                <button id="cerrar-modal" class="absolute top-2 right-2 text-red-600 hover:bg-red-100">
                    ✖
                </button>

                <h2 class="text-xl font-semibold mb-4 text-center">Elementos del paquete</h2>

                <div class="overflow-y-auto flex-1 min-h-0" style="max-height: 75vh;">
                    <div id="canvas-dibujo" class="border max-w-full h-auto"></div>
                </div>
            </div>
        </div>

        <script>
            // Preparar datos de paquetes con sus elementos
            window.paquetes = @json($paquetesJson);

            function mostrarDetallePaquete(paqueteId) {
                const paquete = window.paquetes.find(p => p.id === paqueteId);
                console.log('🔍 Buscando paquete:', paqueteId, 'Encontrado:', paquete);

                if (!paquete) {
                    console.warn('No se encontró el paquete.');
                    return;
                }

                // Obtener los elementos del paquete desde las etiquetas
                const elementos = [];
                if (paquete.etiquetas && paquete.etiquetas.length > 0) {
                    paquete.etiquetas.forEach(etiqueta => {
                        if (etiqueta.elementos && etiqueta.elementos.length > 0) {
                            etiqueta.elementos.forEach(elemento => {
                                elementos.push({
                                    id: elemento.id,
                                    dimensiones: elemento.dimensiones
                                });
                            });
                        }
                    });
                }

                console.log('📦 Elementos del paquete:', elementos);

                if (elementos.length === 0) {
                    Swal.fire('⚠️', 'Este paquete no tiene elementos para dibujar.', 'warning');
                    return;
                }

                // Obtener el modal y el contenedor del canvas
                const modal = document.getElementById('modal-dibujo');
                const canvasContainer = document.getElementById('canvas-dibujo');

                // Limpiar el contenedor
                canvasContainer.innerHTML = '';
                canvasContainer.style.width = '100%';
                canvasContainer.style.display = 'flex';
                canvasContainer.style.flexDirection = 'column';
                canvasContainer.style.gap = '20px';

                // Dibujar cada elemento en su propio contenedor
                elementos.forEach((elemento) => {
                    const elementoDiv = document.createElement('div');
                    elementoDiv.id = `elemento-${elemento.id}`;
                    elementoDiv.style.width = '100%';
                    elementoDiv.style.height = '200px';
                    elementoDiv.style.border = '1px solid #e5e7eb';
                    elementoDiv.style.borderRadius = '4px';
                    elementoDiv.style.background = 'white';
                    elementoDiv.style.position = 'relative';

                    canvasContainer.appendChild(elementoDiv);
                });

                // Mostrar el modal PRIMERO para que los elementos tengan dimensiones reales
                modal.classList.remove('hidden');

                // Usar requestAnimationFrame para asegurar que el navegador renderizó el modal
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        elementos.forEach((elemento) => {
                            console.log(`🎨 Dibujando elemento ${elemento.id}`);

                            if (typeof window.dibujarFiguraElemento === 'function') {
                                window.dibujarFiguraElemento(`elemento-${elemento.id}`, elemento.dimensiones, null);
                            } else {
                                console.error('❌ dibujarFiguraElemento no está disponible');
                            }
                        });
                    });
                });
            }

            // Cerrar modal
            document.addEventListener('DOMContentLoaded', function() {
                const cerrarBtn = document.getElementById('cerrar-modal');
                const modal = document.getElementById('modal-dibujo');

                if (cerrarBtn) {
                    cerrarBtn.addEventListener('click', function() {
                        modal.classList.add('hidden');
                    });
                }

                // Cerrar al hacer click fuera del modal
                if (modal) {
                    modal.addEventListener('click', function(e) {
                        if (e.target === modal) {
                            modal.classList.add('hidden');
                        }
                    });
                }
            });
        </script>

        <!-- SCRIPT PARA IMPRIMIR QR -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script src="{{ asset('js/imprimirQrS.js') }}"></script>
        <script src="{{ asset('js/elementosJs/figuraElemento.js') }}" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</x-app-layout>
