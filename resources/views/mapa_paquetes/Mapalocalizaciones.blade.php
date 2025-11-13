<x-app-layout>
    {{-- Título de la pestaña / header --}}
    <x-slot name="title">Mapa de Localizaciones -
        {{ config('app.name') }}</x-slot>

    {{-- Menú lateral/top --}}
    <x-menu.planillas />

    <div class="w-full p-4 sm:p-6">
        {{-- === Cabecera de la página === --}}
        <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
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
                        class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
        <div class="flex gap-4 w-full h-[calc(100vh-190px)]">

            {{-- COMPONENTE DE MAPA (nuevo) --}}
            <x-mapa-component :ctx="$ctx" :localizaciones-zonas="$localizacionesZonas"
                :localizaciones-maquinas="$localizacionesMaquinas" :paquetes-con-localizacion="$paquetesConLocalizacion" :dimensiones="$dimensiones"
                :obra-actual-id="$obraActualId" :show-controls="false" :mostrarObra="false"
                :show-scan-result="false" :ruta-paquete="route('paquetes.tamaño')" :ruta-guardar="route('localizaciones.storePaquete')"
                height="" class='w-full h-full border-2 overflow-hidden' />

            {{-- PANEL LATERAL: Lista de paquetes (igual que lo tenías) --}}
            <div
                class="bg-white rounded-lg shadow-sm overflow-hidden flex flex-col w-full max-w-xl">
                <div
                    class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4">
                    <h2 class="text-lg font-bold">Paquetes Ubicados</h2>
                    <p class="text-sm text-blue-100 mt-1">
                        Total: {{ $paquetesConLocalizacion->count() }} paquetes
                    </p>
                </div>

                <div class="p-3 border-b border-gray-200">
                    <input type="text" id="search-paquetes"
                        placeholder="Buscar por código o ubicación..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                </div>

                <div class="flex-1 overflow-y-auto p-3" id="lista-paquetes">
                    @forelse($paquetesConLocalizacion as $paquete)
                        <div class="paquete-item bg-gray-50 rounded-lg p-3 mb-2 border border-gray-200 hover:border-blue-400 hover:shadow-md transition cursor-pointer"
                            data-paquete-id="{{ $paquete['id'] }}"
                            data-x1="{{ $paquete['x1'] }}"
                            data-y1="{{ $paquete['y1'] }}"
                            data-x2="{{ $paquete['x2'] }}"
                            data-y2="{{ $paquete['y2'] }}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-bold text-gray-800 text-sm">📦
                                    {{ $paquete['codigo'] }}</span>
                                <span
                                    class="text-xs text-gray-500">{{ $paquete['cantidad_elementos'] }}
                                    elementos</span>
                            </div>
                            <div
                                class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                                <div><span class="text-gray-500">Peso:</span>
                                    <span
                                        class="font-semibold">{{ number_format($paquete['peso'], 2) }}
                                        kg</span></div>
                                <div><span class="text-gray-500">Tipo:</span>
                                    <span
                                        class="font-semibold capitalize">{{ $paquete['tipo_contenido'] }}</span>
                                </div>
                                <div class="col-span-2">
                                    <span
                                        class="text-gray-500">Ubicación:</span>
                                    <span
                                        class="font-semibold">({{ $paquete['x1'] }},{{ $paquete['y1'] }})
                                        →
                                        ({{ $paquete['x2'] }},{{ $paquete['y2'] }})</span>
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

    {{-- Script para integrar el panel lateral con el componente del mapa --}}
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const canvas = document.querySelector('[data-mapa-canvas]');
                const mapaInstance = canvas?.mapaInstance;

                if (!mapaInstance) {
                    console.warn('No se encontró la instancia del mapa');
                    return;
                }

                document.querySelectorAll('.paquete-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const id = parseInt(this.dataset
                            .paqueteId);

                        document.querySelectorAll(
                            '.paquete-item').forEach(i => {
                            i.classList.remove(
                                'border-blue-500',
                                'bg-blue-50');
                        });

                        this.classList.add('border-blue-500',
                            'bg-blue-50');

                        mapaInstance.setHighlight(id);
                        mapaInstance.focusPaquete(
                            parseInt(this.dataset.x1),
                            parseInt(this.dataset.y1),
                        );
                    });

                    item.addEventListener('mouseenter', function() {
                        const id = parseInt(this.dataset
                            .paqueteId);
                        mapaInstance.setHighlight(id);
                    });

                    item.addEventListener('mouseleave', function() {
                        if (!this.classList.contains(
                                'border-blue-500')) {
                            mapaInstance.clearHighlight();
                        }
                    });
                });

                document.getElementById('search-paquetes')?.addEventListener(
                    'input', (e) => {
                        const query = e.target.value.toLowerCase();
                        document.querySelectorAll('.paquete-item').forEach(
                            item => {
                                const text = item.textContent
                                    .toLowerCase();
                                item.style.display = text.includes(
                                    query) ? 'block' : 'none';
                            });
                    });
            });
        </script>
    @endpush
</x-app-layout>
