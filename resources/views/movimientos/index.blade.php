<x-app-layout>
    <x-slot name="title">Movimientos - {{ config('app.name') }}</x-slot>

    <div class="w-full md:p-4 sm:p-2">
        <!-- Desktop: tabla Livewire -->
        <div class="hidden md:block">
            @livewire('movimientos-table')
        </div>

        <!-- Móvil: tarjetas -->
        <div class="block md:hidden space-y-2" x-data="{ filtrosAbiertos: false }">
            <div>
                <div class="bg-gradient-to-tr from-blue-700 to-blue-600 text-white rounded-t-2xl p-4 shadow-lg cursor-pointer"
                    :class="filtrosAbiertos ? 'rounded-t-xl' : 'rounded-xl'" @click="filtrosAbiertos = !filtrosAbiertos">
                    <div class="flex items-center justify-start gap-2">
                        <div class="text-white">
                            <svg x-show="!filtrosAbiertos" class="w-5 h-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                            <svg x-show="filtrosAbiertos" class="w-5 h-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 15l7-7 7 7" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-gray-300">Movimientos</p>
                        </div>
                    </div>
                </div>

                <div x-show="filtrosAbiertos" x-collapse
                    class="bg-white border border-gray-200 rounded-b-2xl shadow-sm p-3">
                    <form method="GET" action="{{ route('movimientos.index') }}" class="space-y-2">
                        @php
                            $tiposMovimientos = \App\Models\Movimiento::query()
                                ->whereNotNull('tipo')
                                ->select('tipo')
                                ->distinct()
                                ->orderBy('tipo')
                                ->pluck('tipo');
                        @endphp
                        <div class="grid grid-cols-2 gap-2">
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-semibold text-gray-700">Tipo</label>
                                <select name="tipo"
                                    class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700">
                                    <option value="">Todos</option>
                                    @foreach ($tiposMovimientos as $tipoOption)
                                        <option value="{{ $tipoOption }}" @selected(request('tipo') === $tipoOption)>
                                            {{ $tipoOption }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-semibold text-gray-700">Estado</label>
                                <select name="estado"
                                    class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700">
                                    <option value="">Todos</option>
                                    <option value="pendiente" @selected(request('estado') === 'pendiente')>Pendiente</option>
                                    <option value="completado" @selected(request('estado') === 'completado')>Completado</option>
                                    <option value="cancelado" @selected(request('estado') === 'cancelado')>Cancelado</option>
                                </select>
                            </div>
                            <div class="flex flex-col gap-1 col-span-2">
                                <label class="text-[10px] font-semibold text-gray-700">Producto / Descripción</label>
                                <input type="text" name="descripcion" value="{{ request('descripcion') }}"
                                    placeholder="Buscar..."
                                    class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-700" />
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-2">
                            @include('components.tabla.limpiar-filtros', [
                                'href' => route('movimientos.index'),
                            ])
                            <button type="submit"
                                class="bg-blue-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow hover:bg-blue-700">
                                Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @php
                $mobilePage = max(1, (int) request('mpage', 1));
                $perPage = 10;

                $query = \App\Models\Movimiento::with([
                    'productoBase',
                    'nave',
                    'solicitadoPor',
                    'ejecutadoPor',
                    'ubicacionOrigen',
                    'ubicacionDestino',
                    'producto',
                    'paquete',
                ]);

                if (request('tipo')) {
                    $query->where('tipo', 'like', '%' . request('tipo') . '%');
                }
                if (request('estado')) {
                    $query->where('estado', request('estado'));
                }
                if (request('descripcion')) {
                    $query->where(function ($q) {
                        $q->where('descripcion', 'like', '%' . request('descripcion') . '%')->orWhere(
                            'pedido_producto_id',
                            'like',
                            '%' . request('descripcion') . '%',
                        );
                    });
                }

                $totalQuery = clone $query;
                $totalResultados = $totalQuery->count();
                $totalPaginas = $totalResultados > 0 ? (int) ceil($totalResultados / $perPage) : 1;
                $mobilePage = min($mobilePage, $totalPaginas);

                $movimientosMobile = $query
                    ->latest()
                    ->skip(($mobilePage - 1) * $perPage)
                    ->take($perPage + 1)
                    ->get();

                $hayMasMovimientos = $movimientosMobile->count() > $perPage;
                if ($hayMasMovimientos) {
                    $movimientosMobile = $movimientosMobile->take($perPage);
                }

                $firstItem = ($mobilePage - 1) * $perPage + 1;
                $lastItem = min($mobilePage * $perPage, $totalResultados);
            @endphp

            <div class="space-y-2">
                @forelse ($movimientosMobile as $movimiento)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div
                            class="bg-gradient-to-tr from-blue-700 to-blue-600 text-white px-3 py-2 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <h3 class="text-sm font-semibold tracking-tight truncate">
                                    {{ ucfirst($movimiento->tipo ?? 'N/A') }}</h3>
                                <p class="text-xs uppercase tracking-wide text-gray-200">
                                    #{{ $movimiento->id }}</p>
                            </div>
                            <div class="flex-shrink-0">
                                <x-tabla.badge-estado :estado="$movimiento->estado" />
                            </div>
                        </div>

                        <div class="p-2.5 space-y-2 text-xs text-gray-700">
                            <div class="flex flex-wrap justify-between gap-2 text-[10px]">
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Producto base</p>
                                    <p class="font-semibold text-gray-900">
                                        {{ $movimiento->productoBase ? ucfirst(strtolower($movimiento->productoBase->tipo)) : 'N/A' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Nave</p>
                                    <p class="font-semibold text-gray-900">
                                        {{ optional($movimiento->nave)->obra ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Solicitado por</p>
                                    <p class="font-semibold text-gray-900 truncate">
                                        {{ optional($movimiento->solicitadoPor)->nombre_completo ?? 'N/A' }}</p>
                                </div>

                                <div>
                                    <p class="text-[9px] uppercase tracking-wide text-gray-500">Producto / Paquete</p>
                                    <p class="font-semibold text-gray-900">
                                        @if ($movimiento->producto)
                                            <a href="{{ route('productos.index', ['id' => $movimiento->producto->id]) }}"
                                                class="text-blue-500 hover:underline">
                                                {{ $movimiento->producto->codigo }}
                                            </a>
                                        @elseif ($movimiento->paquete)
                                            <a href="{{ route('paquetes.index', ['id' => $movimiento->paquete->id]) }}"
                                                class="text-blue-500 hover:underline">
                                                {{ $movimiento->paquete->codigo }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </p>
                                </div>
                            </div>


                            <div class="flex justify-start items-center gap-2 text-[10px]">
                                @if (
                                    ($movimiento->ubicacionOrigen || $movimiento->maquinaOrigen) &&
                                        ($movimiento->ubicacionDestino || $movimiento->maquinaDestino))
                                    <p class="font-semibold text-orange-400">
                                        {{ optional($movimiento->ubicacionOrigen)->nombre ?? (optional($movimiento->maquinaOrigen)->nombre ?? '—') }}
                                    </p>
                                    <span class="text-gray-500 font-bold"><svg viewBox="0 0 24 24" fill="none"
                                            class="w-4 h-4" xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round"
                                                stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <path d="M6 12H18M18 12L13 7M18 12L13 17" stroke="#000000"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                </path>
                                            </g>
                                        </svg></span>
                                    <p class="font-semibold text-blue-600">
                                        {{ optional($movimiento->ubicacionDestino)->nombre ?? (optional($movimiento->maquinaDestino)->nombre ?? '—') }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-2 text-[10px] text-gray-500">
                                <span>Solicitado: {{ $movimiento->fecha_solicitud ?? '—' }}</span>
                                <span>Ejecución: {{ $movimiento->fecha_ejecucion ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div
                        class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 text-center text-xs text-gray-600">
                        No hay movimientos disponibles.
                    </div>
                @endforelse

                <x-tabla.paginacion-mobile :currentPage="$mobilePage" :totalPages="$totalPaginas" :totalResults="$totalResultados" :firstItem="$firstItem"
                    :lastItem="$lastItem" route="movimientos.index" :requestParams="request()->except('mpage')" />
            </div>
        </div>
    </div>

    <!-- SCRIPT PARA IMPRIMIR QR -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        function generateAndPrintQR(data) {
            const safeData = data.replace(/_/g, '%5F');
            const qrContainer = document.getElementById('qrCanvas');
            if (qrContainer) {
                qrContainer.innerHTML = '';
                const qrCode = new QRCode(qrContainer, {
                    text: safeData,
                    width: 200,
                    height: 200,
                });

                setTimeout(() => {
                    const qrImg = qrContainer.querySelector('img');
                    if (!qrImg) {
                        alert("Error al generar el QR. Intenta nuevamente.");
                        return;
                    }

                    const printWindow = window.open('', '_blank');
                    printWindow.document.write(`
                      <html>
                          <head><title>Imprimir QR</title></head>
                          <body>
                              <img src="${qrImg.src}" alt="Código QR" style="width:100px">
                          <script>window.print();<\/script>
                          </body>
                      </html>
                  `);
                    printWindow.document.close();
                }, 500);
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.getElementById('miFormulario');

            if (!form) {
                return;
            }

            form.addEventListener("submit", function(event) {
                event.preventDefault();

                fetch(form.action, {
                        method: form.method,
                        body: new FormData(form),
                        headers: {
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.confirm) {
                            Swal.fire({
                                title: 'Material en fabricación',
                                text: data.message,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Sí, continuar',
                                cancelButtonText: 'Cancelar'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    form.submit();
                                }
                            });
                        } else {
                            form.submit();
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        });
    </script>
</x-app-layout>
