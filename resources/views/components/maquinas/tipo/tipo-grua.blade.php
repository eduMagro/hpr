<div class="w-full sm:col-span-8">
    {{-- 🟡 PENDIENTES --}}
    <div class="mb-4 flex justify-center">
        <button onclick="abrirModalMovimientoLibre()"
            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow">
            ➕ Crear Movimiento Libre
        </button>
    </div>

    <div class="bg-red-200 border border-red-400 rounded-lg p-4 mt-4">
        <h3 class="text-base sm:text-lg font-bold text-red-800 mb-3">📦 Movimientos Pendientes</h3>
        @if ($movimientosPendientes->isEmpty())
            <p class="text-gray-600 text-sm">No hay movimientos pendientes actualmente.</p>
        @else
            <ul class="space-y-3">
                @foreach ($movimientosPendientes as $mov)
                    <li class="p-3 border border-red-200 rounded shadow-sm bg-white text-sm">
                        <div class="flex flex-col gap-2">
                            <p><strong>Tipo:</strong> {{ ucfirst($mov->tipo) }}</p>
                            <p><strong>Descripción:</strong> {{ $mov->descripcion }}</p>
                            <p><strong>Solicitado por:</strong>
                                {{ optional($mov->solicitadoPor)->nombre_completo ?? 'N/A' }}</p>
                            <p><strong>Fecha:</strong> {{ $mov->created_at->format('d/m/Y H:i') }}</p>

                            @if (strtolower($mov->tipo) === 'bajada de paquete')
                                @php
                                    $datosMovimiento = [
                                        'id' => $mov->id,
                                        'paquete_id' => $mov->paquete_id,
                                        'ubicacion_origen' => $mov->ubicacion_origen,
                                        'descripcion' => $mov->descripcion,
                                    ];
                                @endphp

                                <button type="button" onclick='abrirModalBajadaPaquete(@json($datosMovimiento))'
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded w-full sm:w-auto">
                                    📦 Ejecutar bajada
                                </button>
                            @endif

                            @if (strtolower($mov->tipo) === 'recarga materia prima')
                                <button
                                    onclick='abrirModalRecargaMateriaPrima(
                                        @json($mov->id),
                                        @json($mov->tipo),
                                        @json(optional($mov->producto)->codigo),
                                        @json($mov->maquina_destino),
                                        @json($mov->producto_base_id),
                                        @json($ubicacionesDisponiblesPorProductoBase[$mov->producto_base_id] ?? []),
                                        @json(optional($mov->maquinaDestino)->nombre ?? 'Máquina desconocida'),
                                        @json(optional($mov->productoBase)->tipo ?? ''),
                                        @json(optional($mov->productoBase)->diametro ?? ''),
                                        @json(optional($mov->productoBase)->longitud ?? '')
                                    )'
                                    class="bg-green-600 hover:bg-green-700 text-white text-sm px-3 py-2 rounded mt-2 w-full sm:w-auto">
                                    ✅ Ejecutar recarga
                                </button>
                            @endif

                            @if (strtolower($mov->tipo) === 'entrada' && $mov->pedido)
                                <button onclick='abrirModalPedidoDesdeMovimiento(@json($mov))'
                                    style="background-color: orange; color: white;"
                                    class="text-sm px-3 py-2 rounded mt-2 w-full sm:w-auto border border-black">
                                    🏗️ Ver pedido
                                </button>
                            @endif

                            @if (strtolower($mov->tipo) === 'salida')
                                <button onclick='ejecutarSalida(@json($mov->id))'
                                    class="bg-purple-600 hover:bg-purple-700 text-white text-sm px-3 py-2 rounded mt-2 w-full sm:w-auto">
                                    🚛 Ejecutar salida
                                </button>
                            @endif

                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- 🟢 COMPLETADOS --}}
    <div class="bg-green-200 border border-green-300 rounded-lg p-4 mt-6" id="contenedor-movimientos-completados">
        <h3 class="text-base sm:text-lg font-bold text-green-800 mb-3">Movimientos Completados Recientemente</h3>

        @if ($movimientosCompletados->isEmpty())
            <p class="text-gray-600 text-sm">No hay movimientos completados.</p>
        @else
            <ul class="space-y-3">
                @foreach ($movimientosCompletados as $mov)
                    <li class="p-3 border border-green-200 rounded shadow-sm bg-white text-sm movimiento-completado">
                        <div class="flex flex-col gap-2">
                            <p><strong>Tipo:</strong> {{ ucfirst($mov->tipo) }}</p>
                            <p><strong>Descripción:</strong> {{ $mov->descripcion }}</p>
                            <p><strong>Solicitado por:</strong>
                                {{ optional($mov->solicitadoPor)->nombre_completo ?? 'N/A' }}</p>
                            <p><strong>Ejecutado por:</strong>
                                {{ optional($mov->ejecutadoPor)->nombre_completo ?? 'N/A' }}</p>
                            <p><strong>Fecha completado:</strong> {{ $mov->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="flex justify-end mt-2">
                            <x-tabla.boton-eliminar :action="route('movimientos.destroy', $mov->id)" />
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="mt-4 flex justify-center gap-2" id="paginador-movimientos-completados"></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const itemsPorPagina = 5;
        const items = Array.from(document.querySelectorAll('.movimiento-completado'));
        const paginador = document.getElementById('paginador-movimientos-completados');
        const totalPaginas = Math.ceil(items.length / itemsPorPagina);

        function mostrarPagina(pagina) {
            const inicio = (pagina - 1) * itemsPorPagina;
            const fin = inicio + itemsPorPagina;

            items.forEach((item, index) => {
                item.style.display = (index >= inicio && index < fin) ? 'block' : 'none';
            });

            actualizarPaginador(pagina);
        }

        function actualizarPaginador(paginaActual) {
            paginador.innerHTML = '';

            for (let i = 1; i <= totalPaginas; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.className = `px-3 py-1 rounded border text-sm ${
                    i === paginaActual
                        ? 'bg-green-600 text-white'
                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'
                }`;
                btn.onclick = () => mostrarPagina(i);
                paginador.appendChild(btn);
            }
        }

        if (items.length > 0) {
            mostrarPagina(1);
        }
    });
</script>
<script>
    function ejecutarSalida(movimientoId) {
        Swal.fire({
            title: '¿Ejecutar salida?',
            text: '¿Seguro que quieres marcar esta salida como ejecutada?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, ejecutar'
        }).then((result) => {
            if (result.isConfirmed) {
                // 👉 Llamada AJAX directamente aquí
                fetch(`/salidas/completar-desde-movimiento/${movimientoId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('', data.message, 'success');
                            // 👉 Recargar la página o quitar el elemento de la lista
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            Swal.fire('⚠️', data.message, 'warning');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('', 'Hubo un error al completar la salida.', 'error');
                    });
            }
        });
    }
</script>
