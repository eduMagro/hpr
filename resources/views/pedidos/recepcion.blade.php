<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('pedidos.index') }}" class="text-blue-600">
                {{ __('Pedidos de Compra') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Recepción del ') }}{{ $pedido->codigo }}
        </h2>
    </x-slot>

    <div class="py-6">
        @foreach ($pedido->productos as $producto)
            @php
                $productoActivo = old('producto_id') == $producto->id;

                //  Valores por defecto para ESTE producto base
                $defecto = $ultimos[$producto->id] ?? null;
                $coladaPorDefecto = $defecto?->n_colada ?? null;
                $ubicacionPorDefecto = $defecto?->ubicacion_id ?? null;
            @endphp

            @if ($producto->pendiente > 0 || $productoActivo)
                @php
                    $productosRecepcionados = \App\Models\Producto::where('producto_base_id', $producto->id)
                        ->whereHas('entrada', fn($q) => $q->where('pedido_id', $pedido->id))
                        ->get();

                    $formData = session('form_data');
                @endphp
                @php
                    $entradaAbierta = $pedido
                        ->entradas()
                        ->where('estado', 'abierto')
                        ->with('productos')
                        ->latest()
                        ->first();
                @endphp
                @php
                    $productosDeEstaEntrada = \App\Models\Producto::where('entrada_id', $entradaAbierta?->id)
                        ->where('producto_base_id', $producto->id)
                        ->with('productoBase')
                        ->get();
                @endphp
                @if ($entradaAbierta && $productosDeEstaEntrada->isNotEmpty())
                    <div class="bg-white border rounded shadow p-4 mb-6 max-w-4xl mx-auto">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-base font-semibold text-gray-800">
                                Albarán abierto: <span class="text-blue-600">{{ $entradaAbierta->albaran }}</span>
                            </h3>

                            <form id="cerrar-albaran-form" method="POST"
                                action="{{ route('entradas.cerrar', $entradaAbierta->id) }}" class="hidden">
                                @csrf
                                @method('PATCH')
                            </form>

                            <button onclick="confirmarCerrarAlbaran()"
                                class="bg-red-600 text-white text-xs px-3 py-1 rounded hover:bg-red-700">
                                Cerrar Albarán
                            </button>
                        </div>

                        <p class="text-sm text-gray-600 mb-3">
                            Total recepcionado: <strong>{{ number_format($entradaAbierta->peso_total, 2, ',', '.') }}
                                kg</strong>
                        </p>

                        <ul class="divide-y text-sm text-gray-800">
                            @foreach ($productosDeEstaEntrada as $prod)
                                <li class="py-2 flex justify-between">
                                    <span class="font-semibold uppercase text-gray-900">{{ $prod->codigo }}</span>
                                    <span>
                                        {{ ucfirst($prod->productoBase->tipo ?? '-') }} /
                                        Ø{{ $prod->productoBase->diametro ?? '-' }} mm —
                                        {{ number_format($prod->peso_inicial, 2, ',', '.') }} kg
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif


                <div class="mb-6 bg-white border border-gray-300 rounded shadow p-6 space-y-6 max-w-4xl mx-auto"
                    x-data="{ paquetes: '1' }">

                    {{-- CABECERA --}}
                    <h4 class="text-md font-semibold text-gray-800">
                        {{ ucfirst($producto->tipo) }} / {{ $producto->diametro }} mm —
                        {{ number_format($producto->pendiente, 2, ',', '.') }} kg restantes
                    </h4>

                    {{-- FORMULARIO --}}
                    <form action="{{ route('pedidos.recepcion.guardar', $pedido->id) }}" method="POST"
                        class="space-y-4">
                        @csrf
                        <input type="hidden" name="pedido_id" value="{{ $pedido->id }}">
                        <input type="hidden" name="producto_base_id" value="{{ $producto->id }}">
                        <input type="hidden" name="cantidad_paquetes" :value="paquetes">

                        {{-- Selector de cantidad --}}
                        <div class="mb-4">
                            <label for="cantidad_paquetes" class="block text-gray-700 font-bold mb-2">
                                ¿Cuántos paquetes quieres recepcionar?
                            </label>
                            <select id="cantidad_paquetes" class="w-full px-3 py-2 border rounded-lg"
                                x-model="paquetes">
                                <option value="1">1 paquete</option>
                                <option value="2">2 paquetes</option>
                            </select>
                        </div>

                        {{-- Primer paquete --}}
                        <div class="flex flex-col gap-3 bg-gray-100 p-4 rounded-lg border border-gray-300 shadow-sm">
                            <h3 class="text-blue-700 font-semibold text-base">🧱 Primer paquete</h3>

                            <input type="text" name="codigo" placeholder="Código primer paquete" required
                                value="{{ old('codigo') }}" class="w-full px-3 py-2 border rounded-lg">

                            <input type="text" name="n_colada" value="{{ old('n_colada', $coladaPorDefecto) }}"
                                placeholder="Nº colada" required class="border px-2 py-2 rounded w-full bg-white">

                            <input type="text" name="n_paquete" placeholder="Nº paquete" required
                                value="{{ old('n_paquete') }}" class="border px-2 py-2 rounded w-full bg-white">
                        </div>

                        {{-- Segundo paquete (condicional) --}}
                        <template x-if="paquetes === '2'">
                            <div
                                class="flex flex-col gap-3 bg-blue-50 p-4 mt-4 rounded-lg border border-blue-200 shadow-sm">
                                <h3 class="text-blue-700 font-semibold text-base">🧱 Segundo paquete</h3>

                                <input type="text" name="codigo_2" placeholder="Código segundo paquete"
                                    value="{{ old('codigo_2') }}" class="w-full px-3 py-2 border rounded-lg">

                                <input type="text" name="n_colada_2" placeholder="Nº colada"
                                    value="{{ old('n_colada_2') }}" class="border px-2 py-2 rounded w-full bg-white">

                                <input type="text" name="n_paquete_2" placeholder="Nº paquete"
                                    value="{{ old('n_paquete_2') }}" class="border px-2 py-2 rounded w-full bg-white">
                            </div>
                        </template>

                        {{-- Peso y ubicación --}}
                        <div class="bg-white p-4 border rounded shadow-md">
                            <div class="mb-2">
                                <label for="peso" class="block text-gray-700">Peso total (kg):</label>
                                <input type="number" name="peso" min="1" step="0.01" required
                                    x-model="peso" value="{{ old('peso') }}"
                                    class="w-full px-3 py-2 border rounded-lg">
                                <p class="text-sm text-gray-500 mt-1" x-show="paquetes === '2'">
                                    Se dividirá en partes iguales entre los dos paquetes.
                                </p>
                            </div>

                            <div class="mt-4">
                                <label for="ubicacion_id" class="block text-gray-700">Ubicación:</label>
                                <select name="ubicacion_id" required class="w-full px-3 py-2 border rounded-lg">
                                    <option value="">Seleccione una ubicación</option>

                                    @foreach ($ubicaciones as $ubicacion)
                                        <option value="{{ $ubicacion->id }}"
                                            {{ old('ubicacion_id', $ubicacionPorDefecto) == $ubicacion->id ? 'selected' : '' }}>
                                            {{ $ubicacion->nombre_sin_prefijo }}
                                        </option>
                                    @endforeach
                                </select>

                            </div>

                            <div class="mt-4">
                                <label for="otros" class="block text-gray-700">Observaciones:</label>
                                <input type="text" name="otros" value="{{ old('otros') }}"
                                    class="w-full px-3 py-2 border rounded-lg">
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="submit"
                                class="mt-2 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                                Confirmar paquete(s)
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        @endforeach
    </div>

    <script>
        function confirmarCerrarAlbaran() {
            Swal.fire({
                title: '¿Cerrar albarán?',
                text: "No podrás volver a editarlo después.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e3342f',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, cerrar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('cerrar-albaran-form').submit();
                }
            });
        }
    </script>

</x-app-layout>
