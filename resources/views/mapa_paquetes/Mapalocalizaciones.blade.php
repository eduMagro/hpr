<x-app-layout>
    {{-- Título de la pestaña / header --}}
    <x-slot name="title">Mapa de Localizaciones -
        {{ config('app.name') }}</x-slot>

    <div class="w-full p-4 flex flex-col gap-4">
        {{-- === Cabecera de la página === --}}
        <div class="bg-white rounded-lg shadow-sm p-4 ">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Mapa de
                        Localizaciones</h1>
                    <p class="text-gray-600 mt-1">
                        Obra: <span
                            class="font-semibold">{{ $dimensiones['obra'] ?? 'Sin obra' }}</span>
                        | Dimensiones:
                        <span class="font-semibold">{{ $dimensiones['ancho'] }}m
                            × {{ $dimensiones['largo'] }}m</span>
                        | Cliente: <span
                            class="font-semibold">{{ $cliente->empresa ?? 'Sin cliente' }}</span>
                    </p>
                </div>

                {{-- Selector de obra --}}
                <div class="flex items-center gap-3">
                    <label for="obra-select"
                        class="text-sm font-medium text-gray-700">Obra:</label>
                    <select id="obra-select"
                        class=" rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        onchange="window.location.href = '{{ route('mapa.paquetes') }}?obra=' + this.value">
                        @foreach ($obras as $obra)
                            <option value="{{ $obra->id }}"
                                {{ $obra->id == $obraActualId ? 'selected' : '' }}>
                                {{ $obra->obra }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- === GRID principal: Mapa + Panel lateral === --}}
        <div class="flex gap-4 w-full" style="height: calc(100vh - 170px);">

            {{-- COMPONENTE DE MAPA (nuevo) --}}
            <div class="flex-1 overflow-hidden  rounded-md">
                <x-mapa-simple :nave-id="$obraActualId" :modo-edicion="true" class="w-full h-full" />
            </div>

            {{-- PANEL LATERAL: Lista de paquetes (igual que lo tenías) --}}
            <div
                class="bg-white rounded-lg shadow-sm overflow-hidden flex flex-col w-full max-w-xl flex-shrink-0 ">
                <div
                    class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4">
                    <h2 class="text-lg font-bold">Paquetes Ubicados</h2>
                    <p class="text-sm text-blue-100 mt-1">
                        Total: {{ $paquetesConLocalizacion->count() }} paquetes
                    </p>
                </div>

                <div class="p-3 border-b border-gray-200 space-y-2">
                    <input type="text" id="search-paquetes"
                        placeholder="Buscar por código..."
                        class="w-full px-3 py-2  rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />

                    <select id="filter-obra-paquetes"
                        class="w-full px-3 py-2  rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
                        <option value="">Todas las obras</option>
                    </select>

                </div>

                <div class="flex-1 overflow-y-auto p-3" id="lista-paquetes">
                    @forelse($paquetesConLocalizacion as $paquete)
                        <div class="paquete-item bg-gray-50 rounded-lg p-3 mb-2 border border-gray-200 hover:border-blue-400 hover:shadow-md transition cursor-pointer"
                            data-paquete-id="{{ $paquete['id'] }}"
                            data-obra="{{ $paquete['obra'] }}"
                            data-x1="{{ $paquete['x1'] }}"
                            data-y1="{{ $paquete['y1'] }}"
                            data-x2="{{ $paquete['x2'] }}"
                            data-y2="{{ $paquete['y2'] }}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-bold text-gray-800 text-sm">📦
                                    {{ $paquete['codigo'] }}</span>
                                <span
                                    class="text-xs text-gray-500">{{ $paquete['cantidad_etiquetas'] }}
                                    etiquetas</span>
                            </div>
                            <div
                                class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                                <div><span class="text-gray-500">Peso:</span>
                                    <span
                                        class="font-semibold">{{ number_format($paquete['peso'], 2) }}
                                        kg</span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-gray-500">Obra:</span>
                                    <span
                                        class="font-semibold">{{ $paquete['obra'] }}</span>
                                </div>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                @if ($paquete['tipo_contenido'] === 'barras')
                                    <span
                                        class="inline-block w-3 h-3 bg-blue-500 rounded-full"></span><span
                                        class="text-xs text-gray-600">Barras</span>
                                @elseif($paquete['tipo_contenido'] === 'estribos')
                                    <span
                                        class="inline-block w-3 h-3 bg-green-500 rounded-full"></span><span
                                        class="text-xs text-gray-600">Estribos</span>
                                @else
                                    <span
                                        class="inline-block w-3 h-3 bg-orange-500 rounded-full"></span><span
                                        class="text-xs text-gray-600">Mixto</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500">
                            <svg class="w-16 h-16 mx-auto mb-3 text-gray-300"
                                fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round"
                                    stroke-linejoin="round" stroke-width="2"
                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="font-medium">No hay paquetes ubicados</p>
                            <p class="text-sm mt-1">Los paquetes con
                                localización aparecerán aquí</p>
                        </div>
                    @endforelse
                </div>

                <div class="border-t border-gray-200 p-3 bg-gray-50">
                    <h3 class="text-xs font-bold text-gray-700 mb-2">LEYENDA
                    </h3>
                    <div class="space-y-1 text-xs">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-blue-500 rounded"></div>
                            <span>Barras</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-green-500 rounded"></div>
                            <span>Estribos</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-orange-500 rounded"></div>
                            <span>Mixto</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 bg-gray-400 rounded"></div>
                            <span>Máquinas</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>




    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const listaPaquetes = document.getElementById('lista-paquetes');
            const paquetesItems = listaPaquetes.querySelectorAll('.paquete-item');
            let paqueteSeleccionadoCodigo = null;

            // Función para obtener la instancia del mapa
            function getMapaContainer() {
                // Buscamos el contenedor del mapa por el atributo data-mapa-simple
                // Como el ID es dinámico, buscamos el elemento que tenga ese atributo
                return document.querySelector('[data-mapa-simple]');
            }

            paquetesItems.forEach(item => {
                item.addEventListener('click', function() {
                    const mapaContainer = getMapaContainer();
                    if (!mapaContainer || !mapaContainer.mostrarPaquete) {
                        console.warn('El componente de mapa no está listo o no expone las funciones necesarias.');
                        return;
                    }

                    // Obtener código del paquete (limpiando el emoji y espacios)
                    const codigoSpan = item.querySelector('span.font-bold');
                    const codigo = codigoSpan.textContent.replace('📦', '').trim();

                    // Si es el mismo paquete, no hacemos nada (o podríamos deseleccionar)
                    if (paqueteSeleccionadoCodigo === codigo) {
                        return;
                    }

                    // Deseleccionar anterior visualmente
                    paquetesItems.forEach(p => {
                        p.classList.remove('bg-blue-100', 'border-blue-500', 'ring-2', 'ring-blue-200');
                        p.classList.add('bg-gray-50', 'border-gray-200');
                    });

                    // Ocultar anterior en mapa
                    if (paqueteSeleccionadoCodigo) {
                        mapaContainer.ocultarPaquete(paqueteSeleccionadoCodigo);
                    }

                    // Seleccionar nuevo visualmente
                    item.classList.remove('bg-gray-50', 'border-gray-200');
                    item.classList.add('bg-blue-100', 'border-blue-500', 'ring-2', 'ring-blue-200');

                    // Mostrar nuevo en mapa
                    mapaContainer.mostrarPaquete(codigo);
                    paqueteSeleccionadoCodigo = codigo;
                });
            });
        });
    </script>
</x-app-layout>
