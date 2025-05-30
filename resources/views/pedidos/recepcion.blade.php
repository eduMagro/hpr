<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('pedidos.index') }}" class="text-blue-600">
                {{ __('Pedidos de Compra') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Recepción del Pedido ') }}{{ $pedido->codigo }}
        </h2>
    </x-slot>


    <div class="px-4 py-6">
        @foreach ($pedido->productos as $producto)
            @php
                $productoActivo = old('producto_id') == $producto->id;
            @endphp

            @if ($producto->pendiente > 0 || $productoActivo)
                @php
                    $productosRecepcionados = \App\Models\Producto::where('producto_base_id', $producto->id)
                        ->whereHas('entrada', fn($q) => $q->where('pedido_id', $pedido->id))
                        ->get();

                    $formData = session('form_data');
                @endphp

                <div class="mb-6 bg-white border border-gray-300 rounded shadow p-6 space-y-6 max-w-4xl mx-auto">
                    @php
                        $entradaAbierta = $pedido
                            ->entradas()
                            ->where('estado', 'abierto')
                            ->with('productos')
                            ->latest()
                            ->first();
                    @endphp

                    @if ($entradaAbierta)
                        <div class="bg-white border border-gray-300 rounded shadow p-6 mb-8 max-w-5xl mx-auto">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">
                                    Albarán abierto: <span class="text-blue-600">{{ $entradaAbierta->albaran }}</span>
                                </h3>

                                <form id="cerrar-albaran-form" method="POST"
                                    action="{{ route('entradas.cerrar', $entradaAbierta->id) }}" class="hidden">
                                    @csrf
                                    @method('PATCH')
                                </form>

                                <button onclick="confirmarCerrarAlbaran()"
                                    class="bg-red-600 text-white text-sm px-4 py-2 rounded hover:bg-red-700">
                                    Cerrar Albarán
                                </button>

                            </div>

                            <p class="text-gray-600 text-sm mb-2">Total recepcionado:
                                <strong>{{ number_format($entradaAbierta->peso_total, 2, ',', '.') }} kg</strong>
                            </p>

                            <div class="grid gap-3 md:grid-cols-2">
                                @foreach ($entradaAbierta->productos as $productoEntrada)
                                    <div
                                        class="flex items-center justify-between bg-gray-100 px-4 py-3 rounded shadow-sm">
                                        <div class="text-sm text-gray-800">
                                            <strong>{{ ucfirst($productoEntrada->productoBase->tipo ?? '-') }}</strong>
                                            /
                                            {{ $productoEntrada->productoBase->diametro ?? '-' }} mm —
                                            {{ number_format($productoEntrada->peso_inicial, 2, ',', '.') }} kg
                                            @if ($productoEntrada->n_paquete)
                                                — {{ $productoEntrada->codigo }}
                                            @endif
                                        </div>

                                    </div>
                                @endforeach

                            </div>
                        </div>
                    @endif

                    {{-- CABECERA --}}
                    <h4 class="text-md font-semibold text-gray-800">
                        {{ ucfirst($producto->tipo) }} / {{ $producto->diametro }} mm —
                        {{ number_format($producto->pendiente, 2, ',', '.') }} kg restantes
                    </h4>

                    {{-- FORMULARIO DE NUEVO PAQUETE --}}
                    <form action="{{ route('pedidos.recepcion.guardar', $pedido->id) }}" method="POST"
                        class="space-y-4">
                        @csrf
                        <input type="hidden" name="pedido_id" value="{{ $pedido->id }}">
                        <input type="hidden" name="producto_base_id" value="{{ $producto->id }}">

                        <div class="flex flex-col gap-3 bg-gray-100 p-4 rounded-lg border border-gray-300 shadow-sm">


                            <input type="text" id="codigo" name="codigo" value="{{ old('codigo') }}" required
                                placeholder="Escanee el código MP..." class="w-full px-3 py-2 border rounded-lg"
                                autofocus>


                            <input type="text" name="peso" placeholder="Peso del paquete"
                                value="{{ $productoActivo ? old('peso') ?? ($formData['peso'] ?? '') : '' }}"
                                class="border px-2 py-2 rounded w-full bg-white" required>

                            <input type="text" name="n_colada" placeholder="Nº colada"
                                value="{{ $productoActivo ? old('n_colada') ?? ($formData['n_colada'] ?? '') : '' }}"
                                class="border px-2 py-2 rounded w-full bg-white">

                            <input type="text" name="n_paquete" placeholder="Nº paquete"
                                value="{{ $productoActivo ? old('n_paquete') ?? ($formData['n_paquete'] ?? '') : '' }}"
                                class="border px-2 py-2 rounded w-full bg-white">

                            <input type="text" name="ubicacion_id" placeholder="Ubicación"
                                value="{{ $productoActivo ? old('ubicacion_id') ?? ($formData['ubicacion_id'] ?? '') : '' }}"
                                class="border px-2 py-2 rounded w-full bg-white">

                            <input type="text" name="otros" placeholder="Observaciones"
                                value="{{ $productoActivo ? old('otros') ?? ($formData['otros'] ?? '') : '' }}"
                                class="border px-2 py-2 rounded w-full bg-white">
                        </div>

                        <div class="text-right">
                            <button type="submit"
                                class="mt-2 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                                Confirmar paquete
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
