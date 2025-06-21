<script>
    // 🔧 Función global para enviar alerta al departamento Programador
    function notificarProgramador(mensaje) {
        const urlActual = window.location.href;
        const mensajeCompleto = `🔗 URL: ${urlActual}\n📜 Mensaje: ${mensaje}`;

        fetch("{{ route('alertas.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content
                },
                body: JSON.stringify({
                    mensaje: mensajeCompleto,
                    enviar_a_departamentos: ['Programador']
                })
            })
            .then(async resp => {
                if (!resp.ok) {
                    const texto = await resp.text();
                    throw new Error(`HTTP ${resp.status}: ${texto}`);
                }
                return resp.json();
            })
            .then(() => {
                Swal.fire('Notificación enviada', 'Los programadores han sido notificados.', 'success');
            })
            .catch(err => {
                console.error('⚠️ Error:', err);
                Swal.fire('Error', 'No se pudo enviar la notificación.', 'error');
            });
    }

    // ⚡ Definimos la función global que Alpine usa para cada ubicación
    window.inventarioUbicacion = function(productosEsperados) {
        return {
            productosEsperados,
            escaneados: [],
            sospechosos: [],

            procesarQR(codigo) {
                codigo = (codigo || '').trim();
                if (!codigo) return;
                if (this.productosEsperados.includes(codigo)) {
                    if (!this.escaneados.includes(codigo)) this.escaneados.push(codigo);
                } else {
                    if (!this.sospechosos.includes(codigo)) this.sospechosos.push(codigo);
                }
            },

            productoEscaneado(codigo) {
                return this.escaneados.includes(codigo);
            },

            /** Reportar errores → usa notificarProgramador() */
            reportarErrores() {
                const faltantes = this.productosEsperados.filter(c => !this.escaneados.includes(c));
                const inesperados = this.sospechosos;

                if (faltantes.length === 0 && inesperados.length === 0) {
                    Swal.fire('Sin errores', 'No hay errores que reportar en esta ubicación.', 'info');
                    return;
                }

                const texto = [
                    '🚨 Reporte de inventario',
                    'Ubicación: ' + this.nombreUbicacion,
                    `Faltantes (${faltantes.length}): ${faltantes.join(', ') || 'ninguno'}`,
                    `Inesperados (${inesperados.length}): ${inesperados.join(', ') || 'ninguno'}`
                ].join('\n');

                notificarProgramador(texto);
            },

            /** Se asignará desde Blade */
            nombreUbicacion: ''
        }
    }
</script>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ubicaciones.index') }}" class="text-gray-600 hover:text-gray-800">
                {{ __('Ubicaciones') }}
            </a>
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 py-6">
        @foreach ($ubicacionesPorSector as $sector => $listaUbis)
            @php \Log::debug("Sector $sector => ".$listaUbis->count().' ubicaciones'); @endphp
            <h2 class="text-lg font-bold mt-6">Sector {{ $sector }}</h2>


            @foreach ($listaUbis as $ubicacion)
                <div x-data="Object.assign(inventarioUbicacion(@json($ubicacion->productos->pluck('codigo'))), { nombreUbicacion: '{{ $ubicacion->ubicacion }}' })" class="bg-white shadow rounded-2xl overflow-hidden mt-4">
                    <div
                        class="flex flex-col sm:flex-row sm:justify-between sm:items-center bg-gray-800 text-white px-4 py-3 gap-3">
                        <div class="text-sm sm:text-base">

                            <span>Ubicación: <strong>{{ $ubicacion->ubicacion }}</strong></span>
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] sm:text-xs font-medium bg-gray-200 text-gray-900 ml-2">
                                {{ count($ubicacion->productos) }} prod. esperados
                            </span>
                        </div>

                        <input type="text"
                            class="w-full sm:w-64 border border-gray-300 rounded-md px-3 py-2 text-xs text-gray-900 placeholder-gray-400 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 shadow"
                            placeholder="Escanea aquí…"
                            x-on:keydown.enter.prevent="procesarQR($event.target.value); $event.target.value = ''"
                            x-ref="inputQR" @if ($loop->first && $loop->parent->first) autofocus @endif>
                    </div>

                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full text-xs md:text-sm divide-y divide-gray-200">
                            <thead class="bg-gray-100 text-gray-800">
                                <tr>
                                    <th class="px-2 py-1 text-center w-12">#</th>
                                    <th class="px-2 py-1 text-center">Código</th>
                                    <th class="px-2 py-1 text-center">Producto</th>
                                    <th class="px-2 py-1 text-center">Tipo</th>
                                    <th class="px-2 py-1 text-center">Ø / Long.</th>
                                    <th class="px-2 py-1 text-center">Peso</th>
                                    <th class="px-2 py-1 text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">

                                @foreach ($ubicacion->productos as $idx => $producto)
                                    <tr :class="productoEscaneado('{{ $producto->codigo }}') ? 'bg-green-50' : ''">

                                        <td class="px-2 py-1 text-center">{{ $idx + 1 }}</td>
                                        <td class="px-2 py-1 text-xs text-center">{{ $producto->codigo }}</td>
                                        <td class="px-2 py-1 text-center">{{ $producto->nombre }}</td>
                                        <td class="px-2 py-1 capitalize text-center">
                                            {{ $producto->productoBase->tipo ?? '—' }}
                                        </td>

                                        <td class="px-2 py-1 text-center">
                                            @if ($producto->productoBase?->tipo === 'encarretado')
                                                Ø {{ $producto->productoBase->diametro ?? '—' }} mm
                                            @else
                                                {{ $producto->productoBase->longitud ?? '—' }} m
                                            @endif
                                        </td>

                                        <td class="px-2 py-1 text-center">
                                            {{ number_format($producto->peso_inicial, 1, ',', '.') }}</td>
                                        <td class="px-2 py-1 text-center">
                                            <span x-show="productoEscaneado('{{ $producto->codigo }}')"
                                                class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-100 text-green-800">OK</span>
                                            <span x-show="!productoEscaneado('{{ $producto->codigo }}')"
                                                class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-yellow-100 text-yellow-800">Pend.</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="sm:hidden divide-y divide-gray-100 text-xs">
                        @foreach ($ubicacion->productos as $producto)
                            <div class="flex justify-between items-center py-2 px-3"
                                :class="productoEscaneado('{{ $producto->codigo }}') ? 'bg-green-50' : ''">
                                <div class="flex-1">
                                    <p class="font-semibold">{{ $producto->codigo }}</p>
                                    <p class="text-gray-600">{{ $producto->nombre }}</p>
                                </div>
                                <div class="text-right ml-2">
                                    <span x-show="productoEscaneado('{{ $producto->codigo }}')"
                                        class="inline-block px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-[10px] font-semibold">OK</span>
                                    <span x-show="!productoEscaneado('{{ $producto->codigo }}')"
                                        class="inline-block px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800 text-[10px] font-semibold">Pend.</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="px-4 py-3" x-show="sospechosos.length">
                        <h3 class="text-sm font-semibold text-red-600 mb-1">Productos inesperados:</h3>
                        <ul class="list-disc list-inside text-xs text-red-700 space-y-0.5">
                            <template x-for="codigo in sospechosos" :key="codigo">
                                <li x-text="codigo"></li>
                            </template>
                        </ul>
                    </div>

                    <div class="flex justify-end gap-3 px-4 pb-4">
                        <button type="button"
                            class="bg-red-600 hover:bg-red-700 text-white font-semibold px-3 py-1.5 rounded-md text-xs shadow"
                            x-on:click="reportarErrores()">
                            Reportar errores
                        </button>

                    </div>
                </div>
            @endforeach
        @endforeach

    </div>
</x-app-layout>
