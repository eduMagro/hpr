<x-app-layout>
    <x-slot name="title">Materiales - {{ config('app.name') }}</x-slot>

    @include('components.maquinas.modales.grua.modales-grua', [
        'maquinasDisponibles' => $maquinasDisponibles,
    ])

    @php
        $successFlash = session()->pull('success');
        $statusFlash = session()->pull('status');
    @endphp

    <div class="w-full md:flex md:flex-col md:gap-3 md:h-[calc(100vh-100px)] md:overflow-hidden">
        <!-- Botones superiores -->
        <div
            class="mb-4 flex max-sm:hidden flex-wrap items-center justify-center md:justify-end gap-2 px-2 md:mb-0 md:flex-shrink-0">
            @if (auth()->check() && auth()->id() === 1)
                <x-tabla.boton-azul :href="route('entradas.create')" class="text-sm px-4 py-2">
                    ➕ Nueva Entrada
                </x-tabla.boton-azul>
            @endif
        </div>

        <!-- Modal Generar Códigos (disponible en todas las vistas) -->
        <div id="modalGenerarCodigos"
            class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center px-4">
            <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative">
                <h2 class="text-xl font-semibold mb-4">Generar y exportar códigos</h2>

                <form action="{{ route('productos.generar.crearExportar') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label for="cantidad" class="block text-sm font-medium text-gray-700">Cantidad a
                            generar</label>
                        <input type="number" id="cantidad" name="cantidad" value="10" min="1"
                            class="w-full p-2 border border-gray-300 rounded-lg" required>
                    </div>

                    <div class="flex justify-end pt-2 space-x-2">
                        <p class="text-xs text-gray-500 mt-2">
                            ⚠️ Esta exportación es importante. Exporta solo si vas a imprimir etiquetas QR para
                            evitar duplicados.
                        </p>

                        <button type="button" onclick="cerrarModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Generar
                        </button>
                    </div>
                </form>

            </div>
        </div>
        <script>
            function abrirModal() {
                const modal = document.getElementById('modalGenerarCodigos');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function cerrarModal() {
                const modal = document.getElementById('modalGenerarCodigos');
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    cerrarModal();
                }
            });

            window.addEventListener('click', function(event) {
                const modal = document.getElementById('modalGenerarCodigos');
                if (event.target === modal) {
                    cerrarModal();
                }
            });
        </script>

        <!-- 🖥️ Tabla solo en pantallas medianas o grandes -->
        <div class="hidden md:flex flex-col gap-3 flex-1 min-h-0 overflow-hidden">

            <div class="hidden md:flex items-center gap-3 mb-4 px-1">
                <!-- Catálogo de Productos Base -->
                <div x-data="{ modalCatalogo: false }" @keydown.window.escape="modalCatalogo = false" class="flex-1">
                    <div
                        class="rounded-lg border border-gray-200 bg-white shadow-sm px-3 py-2 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <div class="bg-gradient-to-tr from-blue-500 to-blue-600 text-white p-1.5 rounded-md">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xl text-gray-600 leading-tight">Catálogo de Productos Base</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 font-semibold text-xs">
                            <button @click="modalCatalogo = true"
                                class="shrink-0 bg-gradient-to-tr from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 inline-flex items-center gap-2 text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                                <span>Ver catálogo</span>
                            </button>

                            <button onclick="abrirModal()"
                                class="shrink-0 bg-gradient-to-tr from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 inline-flex items-center gap-2 text-sm">
                                <svg viewBox="0 0 512 512" version="1.1" xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#ffffff"
                                    stroke="#ffffff">
                                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                    <g id="SVGRepo_iconCarrier">
                                        <title>qr-code</title>
                                        <g id="Page-1" stroke="none" stroke-width="1" fill="none"
                                            fill-rule="evenodd">
                                            <g id="qr-code" fill="#ffffff"
                                                transform="translate(64.000000, 63.978667)">
                                                <path
                                                    d="M384,149.354667 L384,384.021333 L149.333333,384.021333 L149.348268,298.688 L191.997479,298.688 L191.982544,341.354667 L341.350789,341.354667 L341.350789,192.021333 L320,192.021 L320,192 L298.678,192 L298.678304,149.354667 L384,149.354667 Z M42.6666667,298.688 L42.6666667,341.370882 L106.666667,341.370882 L106.666667,384.021333 L-4.26325641e-14,384.021333 L-4.26325641e-14,298.688 L42.6666667,298.688 Z M298.666667,234.688 L298.666667,298.688 L234.666667,298.688 L234.666667,234.688 L298.666667,234.688 Z M256,0.0213333333 L256,192 L213.333,192 L213.333333,42.688 L42.6666667,42.688 L42.6666667,213.354667 L192,213.354333 L192,256.021333 L-4.26325641e-14,256.021333 L-4.26325641e-14,0.0213333333 L256,0.0213333333 Z M170.666667,85.3546667 L170.666667,170.688 L85.3333333,170.688 L85.3333333,85.3546667 L170.666667,85.3546667 Z M298.666667,1.42108547e-14 L384,0.0213333333 L384,85.3333333 L341.346136,85.312 L341.346136,42.6666667 L298.666667,42.6666667 L298.666667,1.42108547e-14 Z">
                                                </path>
                                            </g>
                                        </g>
                                    </g>
                                </svg>
                                QR
                            </button>
                        </div>
                    </div>

                    <div x-cloak x-show="modalCatalogo" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm flex flex-col p-3 md:p-6">
                        <div
                            class="relative bg-white w-full h-full rounded-lg shadow-2xl flex flex-col overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-3 border-b bg-gray-50">
                                <div class="flex items-center gap-3">
                                    <div class="bg-gradient-to-tr from-gray-800 to-gray-700 p-2 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-800">Catálogo de Productos Base
                                        </h3>
                                        <p class="text-xs text-gray-600">Listado disponible en modal a pantalla
                                            completa
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500 hidden sm:inline">Presiona ESC para
                                        cerrar</span>
                                    <button @click="modalCatalogo = false" class="text-gray-700 hover:text-gray-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="flex-1 overflow-auto p-4">
                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-[600px] text-sm rounded-t-lg overflow-hidden">
                                        <thead class="bg-gradient-to-t from-gray-800 to-gray-700 text-white">
                                            <tr>
                                                <th class="px-4 py-3 border-b border-blue-400 font-semibold">ID</th>
                                                <th class="px-4 py-3 border-b border-blue-400 font-semibold">Tipo</th>
                                                <th class="px-4 py-3 border-b border-blue-400 font-semibold">Diámetro
                                                </th>
                                                <th class="px-4 py-3 border-b border-blue-400 font-semibold">Longitud
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($productosBase as $base)
                                                <tr class="border-b hover:bg-gray-200 transition-colors">
                                                    <td class="px-4 py-3 text-center font-medium text-gray-700">
                                                        {{ $base->id }}</td>
                                                    <td class="px-4 py-3 text-center">
                                                        <span
                                                            class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-semibold">
                                                            {{ ucfirst($base->tipo) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-center font-medium text-gray-700">
                                                        {{ $base->diametro }} mm
                                                    </td>
                                                    <td class="px-4 py-3 text-center text-gray-600">
                                                        {{ $base->longitud ?? '—' }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center py-8">
                                                        <div class="flex flex-col items-center gap-2">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-12 w-12 text-gray-400" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                                            </svg>
                                                            <span class="text-gray-500 font-medium">No hay productos
                                                                base
                                                                registrados</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 text-center text-xs text-gray-200">
                            Desplázate dentro del modal para revisar el catálogo completo.
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 min-h-0">
                @livewire('productos-table')
            </div>
        </div>

        <!-- 📱 Vista móvil renovada -->
        <div class="block md:hidden" x-data="{ filtrosAbiertos: false }">
            <div class="mb-4">
                <div class="bg-gradient-to-r from-blue-700 to-blue-600 text-white p-3 shadow-lg cursor-pointer"
                    :class="filtrosAbiertos ? 'rounded-t-xl' : 'rounded-xl'"
                    @click="filtrosAbiertos = !filtrosAbiertos">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 flex-1">
                            {{-- Ícono toggle filtros --}}
                            <div class="text-white">
                                <svg x-show="!filtrosAbiertos" class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                                <svg x-show="filtrosAbiertos" class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 15l7-7 7 7" />
                                </svg>
                            </div>
                            <h2 class="text-base font-semibold">Materiales</h2>
                        </div>
                        <button onclick="abrirModal()" @click.stop
                            class="bg-white/15 flex items-center gap-1 text-white text-[10px] font-semibold px-2 py-1.5 rounded-lg shadow hover:bg-white/25 transition">
                            <svg viewBox="0 0 512 512" version="1.1" xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#ffffff"
                                stroke="#ffffff">
                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                <g id="SVGRepo_iconCarrier">
                                    <title>qr-code</title>
                                    <g id="Page-1" stroke="none" stroke-width="1" fill="none"
                                        fill-rule="evenodd">
                                        <g id="qr-code" fill="#ffffff" transform="translate(64.000000, 63.978667)">
                                            <path
                                                d="M384,149.354667 L384,384.021333 L149.333333,384.021333 L149.348268,298.688 L191.997479,298.688 L191.982544,341.354667 L341.350789,341.354667 L341.350789,192.021333 L320,192.021 L320,192 L298.678,192 L298.678304,149.354667 L384,149.354667 Z M42.6666667,298.688 L42.6666667,341.370882 L106.666667,341.370882 L106.666667,384.021333 L-4.26325641e-14,384.021333 L-4.26325641e-14,298.688 L42.6666667,298.688 Z M298.666667,234.688 L298.666667,298.688 L234.666667,298.688 L234.666667,234.688 L298.666667,234.688 Z M256,0.0213333333 L256,192 L213.333,192 L213.333333,42.688 L42.6666667,42.688 L42.6666667,213.354667 L192,213.354333 L192,256.021333 L-4.26325641e-14,256.021333 L-4.26325641e-14,0.0213333333 L256,0.0213333333 Z M170.666667,85.3546667 L170.666667,170.688 L85.3333333,170.688 L85.3333333,85.3546667 L170.666667,85.3546667 Z M298.666667,1.42108547e-14 L384,0.0213333333 L384,85.3333333 L341.346136,85.312 L341.346136,42.6666667 L298.666667,42.6666667 L298.666667,1.42108547e-14 Z">
                                            </path>
                                        </g>
                                    </g>
                                </g>
                            </svg>
                            QR
                        </button>
                    </div>
                </div>

                <div x-show="filtrosAbiertos" x-collapse
                    class="bg-white border border-gray-200 rounded-b-xl shadow-sm p-3">
                    <form method="GET" action="{{ route('productos.index') }}" class="space-y-2">
                        <div class="grid grid-cols-2 gap-2">
                            {{-- Código QR --}}
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-semibold text-gray-700">Código QR</label>
                                <input type="text" name="codigo" value="{{ request('codigo') }}"
                                    placeholder="Buscar..."
                                    class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                            </div>

                            {{-- Fabricante --}}
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-semibold text-gray-700">Fabricante</label>
                                <input type="text" name="fabricante" value="{{ request('fabricante') }}"
                                    placeholder="Buscar..."
                                    class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                            </div>

                            {{-- Tipo --}}
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-semibold text-gray-700">Tipo</label>
                                <input type="text" name="tipo" value="{{ request('tipo') }}"
                                    placeholder="Buscar..."
                                    class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                            </div>

                            {{-- Diámetro --}}
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-semibold text-gray-700">Diámetro (mm)</label>
                                <input type="text" name="diametro" value="{{ request('diametro') }}"
                                    placeholder="Ej: 12"
                                    class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                            </div>

                            {{-- N° Colada --}}
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-semibold text-gray-700">N° Colada</label>
                                <input type="text" name="n_colada" value="{{ request('n_colada') }}"
                                    placeholder="Buscar..."
                                    class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                            </div>

                            {{-- N° Paquete --}}
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-semibold text-gray-700">N° Paquete</label>
                                <input type="text" name="n_paquete" value="{{ request('n_paquete') }}"
                                    placeholder="Buscar..."
                                    class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                            </div>

                            {{-- Estado --}}
                            <div class="flex flex-col gap-1 col-span-2">
                                <label class="text-[10px] font-semibold text-gray-700">Estado</label>
                                <select name="estado"
                                    class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700">
                                    <option value="">Todos</option>
                                    <option value="almacenado" @selected(request('estado') === 'almacenado')>Almacenado</option>
                                    <option value="fabricando" @selected(request('estado') === 'fabricando')>Fabricando</option>
                                    <option value="consumido" @selected(request('estado') === 'consumido')>Consumido</option>
                                </select>
                            </div>

                            {{-- Ubicación --}}
                            <div class="flex flex-col gap-1 col-span-2">
                                <label class="text-[10px] font-semibold text-gray-700">Ubicación</label>
                                <input type="text" name="ubicacion" value="{{ request('ubicacion') }}"
                                    placeholder="Buscar..."
                                    class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2">
                            @include('components.tabla.limpiar-filtros', [
                                'href' => route('productos.index'),
                            ])
                            <button type="submit"
                                class="bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow hover:bg-blue-800">
                                Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="space-y-2 pb-4">
                @forelse($registrosProductos as $producto)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div
                            class="bg-gradient-to-r from-blue-700 to-blue-600 text-white px-3 py-2 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <h3 class="text-sm font-semibold tracking-tight truncate">{{ $producto->codigo }}</h3>
                                <span
                                    class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] uppercase font-semibold bg-emerald-500/50 text-emerald-100 border border-emerald-400/30">
                                    {{ $producto->estado }}
                                </span>
                            </div>
                            <div class="text-right space-y-0.5 flex-shrink-0">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-semibold bg-white/10 border border-white/20">
                                    {{ strtoupper($producto->productoBase->tipo ?? '—') }}
                                </span>
                                <div class="text-[9px] text-gray-200">
                                    Ø{{ $producto->productoBase->diametro ?? '—' }}
                                    {{ $producto->productoBase->longitud ? '· ' . $producto->productoBase->longitud . ' m' : '' }}
                                </div>
                            </div>
                        </div>

                        <div class="p-2.5 space-y-2">
                            <div class="flex flex-wrap gap-2 text-[10px]">
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Fabricante</p>
                                    <p class="font-semibold text-gray-900 truncate">
                                        {{ $producto->fabricante->nombre ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Nº Colada</p>
                                    <p class="font-semibold text-gray-900">{{ $producto->n_colada }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Nº Paquete</p>
                                    <p class="font-semibold text-gray-900">{{ $producto->n_paquete }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Peso ini.</p>
                                    <p class="font-semibold text-gray-900">{{ $producto->peso_inicial }} kg</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Stock</p>
                                    <p class="font-semibold text-gray-900">{{ $producto->peso_stock }} kg</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Creado</p>
                                    <p class="font-semibold text-gray-900">
                                        {{ $producto->created_at->format('d/m/y') }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-1 justify-start w-full">
                                <div class="flex min-w-0 items-center">
                                    <svg class="h-5 w-5" viewBox="0 0 48.00 48.00" xmlns="http://www.w3.org/2000/svg"
                                        fill="#225CE5" stroke="#225CE5" stroke-width="0.00048000000000000007">
                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"
                                            stroke="#CCCCCC" stroke-width="0.768"></g>
                                        <g id="SVGRepo_iconCarrier">
                                            <path d="M0 0h48v48H0z" fill="none"></path>
                                            <g id="Shopicon">
                                                <path
                                                    d="M24,44c0,0,14-12,14-26c0-7.732-6.268-14-14-14s-14,6.268-14,14C10,32,24,44,24,44z M24,16c1.105,0,2,0.895,2,2 c0,1.105-0.895,2-2,2c-1.105,0-2-0.895-2-2C22,16.895,22.895,16,24,16z">
                                                </path>
                                            </g>
                                        </g>
                                    </svg>
                                    @if (isset($producto->ubicacion->nombre))
                                        <p class="font-semibold text-gray-900 text-[10px] truncate">
                                            {{ $producto->ubicacion->nombre }}</p>
                                    @elseif (isset($producto->maquina->nombre))
                                        <p class="font-semibold text-gray-900 text-[10px] truncate">
                                            {{ $producto->maquina->nombre }}</p>
                                    @else
                                        <p class="font-semibold text-gray-900 text-[10px] truncate">No está ubicada</p>
                                    @endif
                                </div>

                            </div>

                            @php
                                $usuario = auth()->user();
                                $esOficina = $usuario->rol === 'oficina';
                                $esGruista = $usuario->rol !== 'oficina' && $usuario->maquina?->tipo === 'grua';
                            @endphp

                            @if ($esOficina || $esGruista)
                                <div class="flex gap-1 justify-end text-xs font-semibold">
                                    <a href="{{ route('productos.show', $producto->id) }}" wire:navigate
                                        class="bg-gray-600 text-white rounded-lg px-2 py-1 text-center shadow hover:bg-gray-800 flex items-center">
                                        Ver
                                    </a>
                                    <a href="{{ route('productos.edit', $producto->id) }}" wire:navigate
                                        class="bg-orange-400 text-white rounded-lg px-2 py-1 text-center shadow hover:bg-gray-600 flex items-center">
                                        Editar
                                    </a>
                                    <button onclick="abrirModalMovimientoLibre('{{ $producto->codigo }}')"
                                        class="bg-emerald-500 text-white rounded-lg px-2 py-1 text-center shadow hover:bg-emerald-600">
                                        Mover
                                    </button>

                                    <a href="{{ route('productos.editarConsumir', $producto->id) }}" wire:navigate
                                        data-consumir="{{ route('productos.editarConsumir', $producto->id) }}"
                                        class="btn-consumir bg-blue-600 text-white rounded-lg px-2 py-1 text-center shadow hover:bg-red-600 flex items-center">
                                        Consumir
                                    </a>

                                    <form action="{{ route('productos.destroy', $producto->id) }}" method="POST"
                                        class="form-eliminar col-span-2">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="btn-eliminar w-full h-full bg-red-600 text-gray-800 rounded-lg px-2 py-1 text-center font-semibold shadow hover:bg-gray-300">
                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round"
                                                    stroke-linejoin="round"></g>
                                                <g id="SVGRepo_iconCarrier">
                                                    <path
                                                        d="M3 6.38597C3 5.90152 3.34538 5.50879 3.77143 5.50879L6.43567 5.50832C6.96502 5.49306 7.43202 5.11033 7.61214 4.54412C7.61688 4.52923 7.62232 4.51087 7.64185 4.44424L7.75665 4.05256C7.8269 3.81241 7.8881 3.60318 7.97375 3.41617C8.31209 2.67736 8.93808 2.16432 9.66147 2.03297C9.84457 1.99972 10.0385 1.99986 10.2611 2.00002H13.7391C13.9617 1.99986 14.1556 1.99972 14.3387 2.03297C15.0621 2.16432 15.6881 2.67736 16.0264 3.41617C16.1121 3.60318 16.1733 3.81241 16.2435 4.05256L16.3583 4.44424C16.3778 4.51087 16.3833 4.52923 16.388 4.54412C16.5682 5.11033 17.1278 5.49353 17.6571 5.50879H20.2286C20.6546 5.50879 21 5.90152 21 6.38597C21 6.87043 20.6546 7.26316 20.2286 7.26316H3.77143C3.34538 7.26316 3 6.87043 3 6.38597Z"
                                                        fill="#ffffff"></path>
                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                        d="M11.5956 22.0001H12.4044C15.1871 22.0001 16.5785 22.0001 17.4831 21.1142C18.3878 20.2283 18.4803 18.7751 18.6654 15.8686L18.9321 11.6807C19.0326 10.1037 19.0828 9.31524 18.6289 8.81558C18.1751 8.31592 17.4087 8.31592 15.876 8.31592H8.12404C6.59127 8.31592 5.82488 8.31592 5.37105 8.81558C4.91722 9.31524 4.96744 10.1037 5.06788 11.6807L5.33459 15.8686C5.5197 18.7751 5.61225 20.2283 6.51689 21.1142C7.42153 22.0001 8.81289 22.0001 11.5956 22.0001ZM10.2463 12.1886C10.2051 11.7548 9.83753 11.4382 9.42537 11.4816C9.01321 11.525 8.71251 11.9119 8.75372 12.3457L9.25372 17.6089C9.29494 18.0427 9.66247 18.3593 10.0746 18.3159C10.4868 18.2725 10.7875 17.8856 10.7463 17.4518L10.2463 12.1886ZM14.5746 11.4816C14.9868 11.525 15.2875 11.9119 15.2463 12.3457L14.7463 17.6089C14.7051 18.0427 14.3375 18.3593 13.9254 18.3159C13.5132 18.2725 13.2125 17.8856 13.2537 17.4518L13.7537 12.1886C13.7949 11.7548 14.1625 11.4382 14.5746 11.4816Z"
                                                        fill="#ffffff"></path>
                                                </g>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div
                        class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm text-gray-600">
                        No hay productos disponibles.
                    </div>
                @endforelse

                {{-- Paginación móvil --}}
                <x-tabla.paginacion-mobile :paginador="$registrosProductos" />
            </div>
        </div>

        <script>
            if (!window._toastProductosInit) {
                const toastPosition = () => window.matchMedia('(max-width: 768px)').matches ? 'top' : 'top-end';
                window.swalToastProductos = Swal.mixin({
                    toast: true,
                    position: toastPosition(),
                    showConfirmButton: false,
                    timer: 3200,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'rounded-xl shadow-lg max-w-[280px] sm:max-w-sm'
                    },
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });

                window.matchMedia('(max-width: 768px)').addEventListener('change', () => {
                    window.swalToastProductos.update({
                        position: toastPosition()
                    });
                });

                window._toastProductosInit = true;
            }

            const successMessage = @json($successFlash);
            const statusMessage = @json($statusFlash);
            if (successMessage || statusMessage) {
                window.swalToastProductos.fire({
                    icon: 'success',
                    title: successMessage || statusMessage
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                // Delegación de eventos para botones "Consumir"
                document.body.addEventListener('click', async (e) => {
                    const btn = e.target.closest('.btn-consumir');
                    if (!btn) return;

                    e.preventDefault();

                    const url = btn.dataset.consumir || btn.getAttribute('href');

                    const {
                        value: opcion
                    } = await Swal.fire({
                        title: '¿Cómo deseas consumir el material?',
                        text: 'Selecciona si quieres consumirlo completo o solo unos kilos.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Consumir completo',
                        cancelButtonText: 'Cancelar',
                        showDenyButton: true,
                        denyButtonText: 'Consumir por kilos'
                    });

                    if (opcion) {
                        // ✅ Consumir completo
                        if (opcion === true) {
                            window.location.href = url + '?modo=total';
                        }
                    } else if (opcion === false) {
                        // ✅ Consumir por kilos
                        const {
                            value: kilos
                        } = await Swal.fire({
                            title: 'Introduce los kilos a consumir',
                            input: 'number',
                            inputAttributes: {
                                min: 1,
                                step: 0.01
                            },
                            inputPlaceholder: 'Ejemplo: 250',
                            showCancelButton: true,
                            confirmButtonText: 'Consumir',
                            cancelButtonText: 'Cancelar',
                            preConfirm: (value) => {
                                if (!value || value <= 0) {
                                    Swal.showValidationMessage(
                                        'Debes indicar un número válido mayor que 0');
                                    return false;
                                }
                                return value;
                            }
                        });

                        if (kilos) {
                            // Redirigimos con cantidad en la URL (ejemplo GET)
                            window.location.href = url + '?modo=parcial&kgs=' + kilos;
                        }
                    }
                });

                // Confirmar eliminación
                document.querySelectorAll('.form-eliminar').forEach(form => {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();

                        Swal.fire({
                            title: '¿Estás seguro?',
                            text: "Esta acción eliminará la materia prima de forma permanente.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#6c757d',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    });
                });
            });
        </script>

        <!-- Formulario oculto para eliminar Producto Base -->
        <form id="formEliminarProductoBase" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>

        <!-- Modal Crear Producto Base -->
        <div id="modalProductoBase"
            class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
            <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative">
                <h2 class="text-xl font-semibold mb-4">Nuevo Producto Base</h2>

                <form action="{{ route('productos-base.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="redirect_to" value="productos">

                    <div>
                        <label for="pb_tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                        <select id="pb_tipo" name="tipo" required onchange="toggleLongitud()"
                            class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Seleccione tipo</option>
                            <option value="barra">Barra</option>
                            <option value="encarretado">Encarretado</option>
                        </select>
                    </div>

                    <div>
                        <label for="pb_diametro" class="block text-sm font-medium text-gray-700 mb-1">Diametro (mm)
                            *</label>
                        <input type="number" id="pb_diametro" name="diametro" required min="1"
                            class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Ej: 12">
                    </div>

                    <div id="pb_longitud_container">
                        <label for="pb_longitud" class="block text-sm font-medium text-gray-700 mb-1">Longitud
                            (m)</label>
                        <input type="number" id="pb_longitud" name="longitud" min="1"
                            class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Ej: 12 (solo para barras)">
                    </div>

                    <div>
                        <label for="pb_descripcion"
                            class="block text-sm font-medium text-gray-700 mb-1">Descripcion</label>
                        <textarea id="pb_descripcion" name="descripcion" rows="2"
                            class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Descripcion opcional..."></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <button type="button" onclick="cerrarModalProductoBase()"
                            class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function abrirModalProductoBase() {
                document.getElementById('modalProductoBase').classList.remove('hidden');
                document.getElementById('modalProductoBase').classList.add('flex');
            }

            function cerrarModalProductoBase() {
                document.getElementById('modalProductoBase').classList.remove('flex');
                document.getElementById('modalProductoBase').classList.add('hidden');
                // Limpiar formulario
                document.getElementById('pb_tipo').value = '';
                document.getElementById('pb_diametro').value = '';
                document.getElementById('pb_longitud').value = '';
                document.getElementById('pb_descripcion').value = '';
            }

            function toggleLongitud() {
                const tipo = document.getElementById('pb_tipo').value;
                const container = document.getElementById('pb_longitud_container');
                const input = document.getElementById('pb_longitud');

                if (tipo === 'encarretado') {
                    container.style.display = 'none';
                    input.value = '';
                } else {
                    container.style.display = 'block';
                }
            }

            function eliminarProductoBase(id, nombre) {
                Swal.fire({
                    title: '¿Eliminar producto base?',
                    html: `<p class="text-gray-600">Se eliminará: <strong>${nombre}</strong></p><p class="text-xs text-gray-400 mt-1">ID: ${id}</p>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('formEliminarProductoBase');
                        form.action = `/productos-base/${id}`;
                        form.submit();
                    }
                });
            }

            // Cerrar modal con ESC
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    cerrarModalProductoBase();
                }
            });

            // Cerrar al hacer clic fuera
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('modalProductoBase');
                if (event.target === modal) {
                    cerrarModalProductoBase();
                }
            });
        </script>

</x-app-layout>
