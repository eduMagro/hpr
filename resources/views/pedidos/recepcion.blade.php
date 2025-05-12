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

                    {{-- CABECERA --}}
                    <h4 class="text-md font-semibold text-gray-800">
                        {{ ucfirst($producto->tipo) }} / {{ $producto->diametro }} mm —
                        {{ number_format($producto->pendiente, 2, ',', '.') }} kg restantes
                    </h4>

                    {{-- LISTADO DE PAQUETES RECEPCIONADOS --}}
                    @if ($productosRecepcionados->count())
                        <div>
                            <p class="font-semibold text-gray-700 mb-2">Paquetes recepcionados:</p>
                            <div class="flex flex-wrap gap-3">
                                @foreach ($productosRecepcionados as $p)
                                    <div class="flex items-center gap-2 bg-gray-100 px-3 py-2 rounded shadow-sm">
                                        <span class="text-sm text-gray-800 font-medium">
                                            {{ number_format($p->peso_inicial, 2, ',', '.') }} kg
                                            @if ($p->n_paquete)
                                                — Paquete {{ $p->n_paquete }}
                                            @endif
                                        </span>

                                        <button
                                            onclick="generateAndPrintQR(
                                                '{{ $p->id }}',
                                                '{{ addslashes($p->n_paquete ?? 'SIN-PAQUETE') }}',
                                                'MATERIA PRIMA'
                                            )"
                                            class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                            QR
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- FORMULARIO DE NUEVO PAQUETE --}}
                    <form action="{{ route('pedidos.recepcion.guardar', $pedido->id) }}" method="POST"
                        class="space-y-4">
                        @csrf
                        <input type="hidden" name="pedido_id" value="{{ $pedido->id }}">
                        <input type="hidden" name="producto_id" value="{{ $producto->id }}">

                        <div class="flex flex-col gap-3 bg-gray-100 p-4 rounded-lg border border-gray-300 shadow-sm">
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
    <script src="{{ asset('js/imprimirQrAndroid.js') }}"></script>
</x-app-layout>
