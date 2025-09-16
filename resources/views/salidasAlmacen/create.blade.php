<x-app-layout>
    <x-slot name="title">Crear Salida de Almacén - {{ config('app.name') }}</x-slot>
    <x-menu.salidas />
    <x-menu.salidas2 />
    <x-menu.salidasAlmacen />
    <div class="w-full px-6 py-4">
        <div x-data="crearSalidaAlmacen()" class="max-w-4xl mx-auto px-6 py-4 space-y-6">

            {{-- 1) Buscador de disponibilidad por Producto Base --}}

            <div class="bg-white rounded-lg shadow p-4">
                <h2 class="text-lg font-semibold mb-3">Disponibilidad por Producto Base</h2>

                <div class="flex flex-wrap items-end gap-3">
                    <!-- Diámetro -->
                    <div class="inline-flex items-center gap-2">
                        <label class="text-xs text-gray-600 whitespace-nowrap">Diámetro</label>
                        <select x-model.number="filtro.diametro"
                            class="border rounded px-2 py-1 text-sm w-auto min-w-24">
                            <option value="">—</option>
                            @foreach ($diametros as $d)
                                <option value="{{ $d }}">{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Longitud -->
                    <div class="inline-flex items-center gap-2">
                        <label class="text-xs text-gray-600 whitespace-nowrap">Longitud</label>
                        <select x-model.number="filtro.longitud"
                            class="border rounded px-2 py-1 text-sm w-auto min-w-24">
                            <option value="">—</option>
                            @foreach ($longitudes as $l)
                                <option value="{{ $l }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Botones: consultar + restablecer -->
                    <div class="inline-flex items-center gap-2">
                        <button @click="consultar()"
                            class="inline-flex items-center gap-2 bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700 active:scale-[.99] transition w-auto">
                            Consultar
                        </button>

                        {{-- ♻️ Botón reset --}}
                        <button type="button" @click="resetFiltros()"
                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                            title="Restablecer filtros">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 4v5h.582M20 20v-5h-.581M4.582 9A7.5 7.5 0 0112 4.5a7.5 7.5 0 016.418 3.418M19.418 15A7.5 7.5 0 0112 19.5a7.5 7.5 0 01-6.418-3.418" />
                            </svg>
                        </button>
                    </div>



                </div>


            </div>

            <template x-if="consultado">
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="bg-gray-50 p-3 rounded border">
                        <p class="text-xs text-gray-600">Peso disponible</p>
                        <p class="text-xl font-semibold" x-text="total_peso_kg.toFixed(2) + ' kg'"></p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded border">
                        <p class="text-xs text-gray-600">Nº Paquetes</p>
                        <p class="text-xl font-semibold" x-text="total_productos"></p>
                    </div>

                </div>
            </template>

            <template x-if="bases.length">
                <div class="mt-4 overflow-x-auto rounded-lg border bg-white shadow-sm">
                    <table class="w-full text-[13px]">
                        <thead class="bg-gray-100/80 sticky top-0 z-10">
                            <tr class="text-xs uppercase tracking-wide text-gray-600">

                                <th class="p-2 border">Diám.</th>
                                <th class="p-2 border">Long.</th>
                                <th class="p-2 border">Nº Paquetes</th>
                                <th class="p-2 border">Peso total (kg)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="b in bases" :key="b.id">
                                <tr>

                                    <td class="p-2 border" x-text="b.diametro"></td>
                                    <td class="p-2 border" x-text="b.longitud ?? '—'"></td>
                                    <td class="p-2 border" x-text="b.resumen.productos"></td>
                                    <td class="p-2 border" x-text="Number(b.resumen.peso_total).toFixed(2)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>

            <template x-if="preview.length">
                <div class="mt-4">
                    <h3 class="text-sm font-semibold mb-2">Primeros productos (FIFO)</h3>
                    <ul class="text-xs grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2">
                        <template x-for="p in preview" :key="p.id">
                            <li class="border rounded p-2 bg-white">
                                <div><span x-text="p.codigo"></span></div>
                                <div>Peso: <span x-text="p.peso_kg"></span> kg</div>

                            </li>
                        </template>
                    </ul>
                </div>
            </template>
        </div>

        {{-- 2) Formulario de creación de salida --}}
        <div class="bg-white rounded-lg shadow max-w-4xl mx-auto px-6 py-4 space-y-6">
            <form method="POST" action="{{ route('salidas-almacen.store') }}" x-data="{ productos: [{}] }"
                class="space-y-4">
                @csrf

                <h2 class="text-lg font-semibold">Nueva salida rápida</h2>

                <template x-for="(producto, index) in productos" :key="index">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 border-t pt-4 mt-4">
                        <!-- Producto base -->
                        <div class="sm:col-span-2">
                            <label class="text-xs text-gray-600">Producto base</label>
                            <select :name="`productos[${index}][producto_base_id]`"
                                class="w-full border rounded px-2 py-1 text-sm" required>
                                <option value="">— Selecciona —</option>
                                @foreach ($productosBase as $pb)
                                    <option value="{{ $pb->id }}">
                                        Ø{{ $pb->diametro }} @if ($pb->longitud)
                                            · {{ $pb->longitud }}m
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Criterios -->
                        <div class="bg-gray-50 p-3 rounded">
                            <label class="text-xs text-gray-600">Criterio</label>
                            <div class="flex items-center gap-2 mt-1">
                                <input :name="`productos[${index}][peso_objetivo_kg]`" type="number" step="0.01"
                                    min="0" class="w-full border rounded px-2 py-1 text-sm"
                                    placeholder="Peso objetivo (kg)">
                            </div>
                            <div class="text-[11px] text-gray-500 mt-1">o bien por unidades…</div>
                            <div class="flex items-center gap-2 mt-1">
                                <input :name="`productos[${index}][unidades_objetivo]`" type="number" min="1"
                                    class="w-full border rounded px-2 py-1 text-sm" placeholder="Unidades objetivo">
                            </div>

                            <!-- Eliminar línea -->
                            <div class="mt-2 text-right">
                                <button type="button" @click="productos.splice(index, 1)"
                                    class="text-red-500 text-xs hover:underline">Eliminar</button>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Botón añadir línea -->
                <div class="text-left">
                    <button type="button" @click="productos.push({})" class="text-sm text-blue-600 hover:underline">
                        ➕ Añadir otro producto base
                    </button>
                </div>

                <!-- ✅ Observaciones (campo único) -->
                <div>
                    <label class="text-sm text-gray-600">Observaciones</label>
                    <textarea name="observaciones" rows="3" class="w-full border rounded px-3 py-2 text-sm"
                        placeholder="Escribe aquí cualquier detalle adicional..."></textarea>
                </div>

                <!-- Botón crear salida -->
                <div class="flex justify-end pt-2">
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Crear salida
                    </button>
                </div>
            </form>
        </div>

    </div>


    <script>
        function crearSalidaAlmacen() {
            const DISP_URL = @json(route('salidas-almacen.disponibilidad'));
            const STORAGE_KEY = 'salidasAlmacenState';
            const TIPO_FIJO = 'barra';

            return {
                // ------- Estado -------
                filtro: {
                    diametro: '',
                    longitud: ''
                },
                consultado: false,
                total_peso_kg: 0,
                total_productos: 0,
                bases: [],
                preview: [],
                empresaSeleccionada: '',
                clientesObras: [{}],

                // ------- Lifecycle -------
                init() {
                    this.loadState();
                },

                // ------- Persistencia -------
                saveState() {
                    const payload = {
                        filtro: this.filtro,
                        consultado: this.consultado,
                        total_peso_kg: this.total_peso_kg,
                        total_productos: this.total_productos,
                        bases: this.bases,
                        preview: this.preview,
                        empresaSeleccionada: this.empresaSeleccionada,
                        clientesObras: this.clientesObras
                    };
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
                },

                loadState() {
                    try {
                        const raw = localStorage.getItem(STORAGE_KEY);
                        if (!raw) return;
                        const s = JSON.parse(raw);

                        if (s && typeof s === 'object') {
                            this.filtro = s.filtro ?? this.filtro;
                            this.consultado = !!s.consultado;
                            this.total_peso_kg = Number(s.total_peso_kg ?? 0);
                            this.total_productos = Number(s.total_productos ?? 0);
                            this.bases = Array.isArray(s.bases) ? s.bases : [];
                            this.preview = Array.isArray(s.preview) ? s.preview : [];
                            this.empresaSeleccionada = s.empresaSeleccionada ?? '';
                            this.clientesObras = Array.isArray(s.clientesObras) && s.clientesObras.length ? s
                                .clientesObras : [{}];
                        }
                    } catch (e) {
                        console.warn('No se pudo cargar el estado de Salidas Almacén:', e);
                    }
                },

                resetFiltros() {
                    this.filtro = {
                        diametro: '',
                        longitud: ''
                    };
                    this.consultado = false;
                    this.total_peso_kg = 0;
                    this.total_productos = 0;
                    this.bases = [];
                    this.preview = [];
                    // si quieres limpiar también empresa y clientes, descomenta:
                    // this.empresaSeleccionada = '';
                    // this.clientesObras = [{}];

                    localStorage.removeItem(STORAGE_KEY);

                },

                // ------- Acciones -------
                async consultar() {
                    if (!this.filtro.diametro) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Atención',
                            text: 'Debes seleccionar un diámetro antes de consultar.',
                            confirmButtonColor: '#2563eb'
                        });
                        return;
                    }

                    const params = new URLSearchParams({
                        tipo: TIPO_FIJO,
                        diametro: this.filtro.diametro,
                        longitud: this.filtro.longitud || ''
                    });

                    try {
                        const res = await fetch(`${DISP_URL}?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });

                        if (!res.ok) {
                            const txt = await res.text();
                            console.error('Respuesta no OK', res.status, txt);
                            Swal.fire({
                                icon: 'error',
                                title: `Error ${res.status}`,
                                text: 'Ocurrió un problema al consultar la disponibilidad.',
                                confirmButtonColor: '#dc2626'
                            });
                            return;
                        }

                        const data = await res.json();

                        this.consultado = true;
                        this.total_peso_kg = Number(data.total_peso_kg || 0);
                        this.total_productos = Number(data.total_productos || 0);
                        this.bases = data.bases || [];
                        this.preview = data.productos_preview || [];

                        this.saveState(); // <-- guarda todo tras consultar
                    } catch (e) {
                        console.error(e);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error inesperado',
                            text: 'No se pudo cargar la disponibilidad (revisa consola).',
                            confirmButtonColor: '#dc2626'
                        });
                    }
                }
            }
        }
    </script>

</x-app-layout>
