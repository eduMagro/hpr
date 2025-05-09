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
        <form action="{{ route('pedidos.recepcion.guardar', $pedido->id) }}" method="POST"
            class="bg-white shadow rounded p-6 space-y-6 max-w-4xl mx-auto">
            @csrf
            <input type="hidden" name="pedido_id" value="{{ $pedido->id }}">
            <table class="w-full text-sm border-collapse border text-center">
                <tbody>
                    @foreach ($pedido->productos as $producto)
                        @if ($producto->pendiente > 0)
                            <div class="mb-6 rounded bg-gray-300 p-4 shadow-sm grupo-producto"
                                data-producto-id="{{ $producto->id }}" x-data="{
                                    items: JSON.parse(localStorage.getItem('paquetes_{{ $producto->id }}')) || [{}],
                                    guardar() {
                                        localStorage.setItem('paquetes_{{ $producto->id }}', JSON.stringify(this.items));
                                    },
                                    agregar() {
                                        this.items.push({});
                                        this.guardar();
                                    },
                                    eliminar() {
                                        if (this.items.length > 1) {
                                            this.items.pop();
                                            this.guardar();
                                        }
                                    }
                                }">
                                <h4 class="text-md font-semibold mb-2">
                                    {{ ucfirst($producto->tipo) }} / {{ $producto->diametro }} mm —
                                    {{ number_format($producto->pendiente, 2, ',', '.') }} kg
                                </h4>

                                <template x-for="(item, index) in items" :key="index">
                                    <div class="border border-gray-300 bg-gray-100 p-4 rounded-lg shadow-sm mb-3">
                                        <div class="text-sm font-semibold text-gray-700 mb-2">
                                            Paquete <span x-text="index + 1"></span>
                                        </div>

                                        <div class="flex flex-col gap-3">
                                            <input type="hidden"
                                                name="lineas[{{ $producto->id }}][producto_base_id][]"
                                                value="{{ $producto->id }}">

                                            <input type="text" :value="item.peso"
                                                @input="item.peso = $event.target.value; guardar()"
                                                name="lineas[{{ $producto->id }}][peso][]" placeholder="Peso Paquete"
                                                class="border px-2 py-1 rounded bg-white w-full min-w-[120px]">

                                            <input type="text" :value="item.n_colada"
                                                @input="item.n_colada = $event.target.value; guardar()"
                                                name="lineas[{{ $producto->id }}][n_colada][]" placeholder="Nº colada"
                                                class="border px-2 py-1 rounded bg-white w-full min-w-[120px]">

                                            <input type="text" :value="item.n_paquete"
                                                @input="item.n_paquete = $event.target.value; guardar()"
                                                name="lineas[{{ $producto->id }}][n_paquete][]"
                                                placeholder="Nº paquete"
                                                class="border px-2 py-1 rounded bg-white w-full min-w-[120px]">

                                            <input type="text" :value="item.ubicacion_texto"
                                                @input="item.ubicacion_texto = $event.target.value; guardar()"
                                                name="lineas[{{ $producto->id }}][ubicacion_texto][]"
                                                placeholder="Escanea ubicación"
                                                class="border px-2 py-1 rounded bg-white w-full min-w-[120px]">

                                            <input type="text" :value="item.otros"
                                                @input="item.otros = $event.target.value; guardar()"
                                                name="lineas[{{ $producto->id }}][otros][]"
                                                placeholder="Observaciones (opcional)"
                                                class="border px-2 py-1 rounded bg-white w-full min-w-[120px]">
                                        </div>
                                    </div>
                                </template>

                                <div class="flex gap-4 mt-2">
                                    <button type="button" class="text-sm text-blue-600 hover:underline"
                                        @click="agregar()">
                                        + Añadir otro paquete
                                    </button>
                                    <button type="button" class="text-sm text-red-600 hover:underline"
                                        @click="eliminar()">
                                        – Eliminar último paquete
                                    </button>
                                </div>
                            </div>
                        @endif
                    @endforeach

                </tbody>
            </table>

            <div class="text-right">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Confirmar recepción
                </button>
            </div>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const productos = document.querySelectorAll('[data-producto-id]');

            productos.forEach(contenedor => {
                const productoId = contenedor.dataset.productoId;
                const itemsContainer = document.createElement('div');
                itemsContainer.classList.add('paquetes-container');

                // Cargar desde localStorage
                let items = JSON.parse(localStorage.getItem('paquetes_' + productoId)) || [{}];
                items.forEach((item, index) => itemsContainer.appendChild(crearFila(item, productoId,
                    index + 1)));


                contenedor.insertBefore(itemsContainer, contenedor.querySelector('.flex'));

                // Botones
                const btnAdd = contenedor.querySelector('button.text-blue-600');
                const btnRemove = contenedor.querySelector('button.text-red-600');

                btnAdd.addEventListener('click', () => {
                    items.push({});
                    guardar(productoId, items);
                    itemsContainer.appendChild(crearFila({}, productoId, items.length));

                });

                btnRemove.addEventListener('click', () => {
                    if (items.length > 1) {
                        items.pop();
                        guardar(productoId, items);
                        itemsContainer.lastChild.remove();
                    }
                });

                function guardar(id, items) {
                    localStorage.setItem('paquetes_' + id, JSON.stringify(items));
                }

                function crearFila(item, productoId, numero) {
                    const fila = document.createElement('div');
                    fila.className = "border border-gray-300 bg-gray-100 p-4 rounded-lg shadow-sm mb-3";

                    fila.innerHTML = `
        <div class="text-sm font-semibold text-gray-700 mb-2">
            Paquete ${numero}
        </div>
        <div class="grid grid-cols-6 gap-3">
            <input type="hidden" name="lineas[${productoId}][producto_base_id][]" value="${productoId}">
            <input type="text" data-campo="peso" class="input-dato border px-2 py-1 rounded w-full col-span-1 bg-white"
                name="lineas[${productoId}][peso][]" placeholder="Peso Paquete" value="${item.peso || ''}">
            <input type="text" data-campo="n_colada" class="input-dato border px-2 py-1 rounded w-full col-span-1 bg-white"
                name="lineas[${productoId}][n_colada][]" placeholder="Nº colada" value="${item.n_colada || ''}">
            <input type="text" data-campo="n_paquete" class="input-dato border px-2 py-1 rounded w-full col-span-1 bg-white"
                name="lineas[${productoId}][n_paquete][]" placeholder="Nº paquete" value="${item.n_paquete || ''}">
            <input type="text" data-campo="ubicacion_texto" class="input-dato border px-2 py-1 rounded w-full col-span-2 bg-white"
                name="lineas[${productoId}][ubicacion_texto][]" placeholder="Escanea ubicación" value="${item.ubicacion_texto || ''}">
            <input type="text" data-campo="otros" class="input-dato border px-2 py-1 rounded w-full col-span-6 bg-white"
                name="lineas[${productoId}][otros][]" placeholder="Observaciones (opcional)" value="${item.otros || ''}">
        </div>
    `;

                    // Escuchar cambios en todos los inputs de este paquete
                    const campos = fila.querySelectorAll('.input-dato');
                    campos.forEach((input, idx) => {
                        input.addEventListener('input', () => {
                            const contenedor = fila.parentElement;
                            const filas = contenedor.querySelectorAll('.input-dato');
                            const totalCampos =
                                5; // peso, n_colada, n_paquete, ubicacion_texto, otros
                            const nuevosItems = [];

                            for (let i = 0; i < filas.length; i += totalCampos) {
                                nuevosItems.push({
                                    peso: filas[i]?.value || '',
                                    n_colada: filas[i + 1]?.value || '',
                                    n_paquete: filas[i + 2]?.value || '',
                                    ubicacion_texto: filas[i + 3]?.value || '',
                                    otros: filas[i + 4]?.value || '',
                                });
                            }

                            localStorage.setItem('paquetes_' + productoId, JSON.stringify(
                                nuevosItems));
                        });
                    });

                    return fila;
                }

            });
        });
    </script>
    <style>
        input[type="text"],
        input[type="hidden"] {
            min-width: 120px;
        }
    </style>

</x-app-layout>
