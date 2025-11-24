<div>
    <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

    <x-tabla.wrapper minWidth="1000px">
        <x-tabla.header>
            {{-- Fila de encabezados --}}
            <x-tabla.header-row>
                <x-tabla.encabezado-ordenable campo="id" :sortActual="$sort" :orderActual="$order" texto="ID" />
                <x-tabla.encabezado-ordenable campo="entrada_id" :sortActual="$sort" :orderActual="$order" texto="Albarán" />
                <x-tabla.encabezado-ordenable campo="codigo" :sortActual="$sort" :orderActual="$order" texto="Código" />
                <x-tabla.encabezado-ordenable campo="nave" :sortActual="$sort" :orderActual="$order" texto="Nave" />
                <x-tabla.encabezado-ordenable campo="fabricante" :sortActual="$sort" :orderActual="$order" texto="Fabricante" />
                <x-tabla.encabezado-ordenable campo="tipo" :sortActual="$sort" :orderActual="$order" texto="Tipo" />
                <x-tabla.encabezado-ordenable campo="diametro" :sortActual="$sort" :orderActual="$order" texto="Diámetro" />
                <x-tabla.encabezado-ordenable campo="longitud" :sortActual="$sort" :orderActual="$order" texto="Longitud" />
                <x-tabla.encabezado-ordenable campo="n_colada" :sortActual="$sort" :orderActual="$order" texto="N° Colada" />
                <x-tabla.encabezado-ordenable campo="n_paquete" :sortActual="$sort" :orderActual="$order" texto="N° Paquete" />
                <x-tabla.encabezado-ordenable campo="peso_inicial" :sortActual="$sort" :orderActual="$order" texto="Peso Inicial" />
                <x-tabla.encabezado-ordenable campo="peso_stock" :sortActual="$sort" :orderActual="$order" texto="Peso Stock" />
                <x-tabla.encabezado-ordenable campo="estado" :sortActual="$sort" :orderActual="$order" texto="Estado" />
                <x-tabla.encabezado-ordenable campo="ubicacion" :sortActual="$sort" :orderActual="$order" texto="Ubicación" />
                <x-tabla.encabezado-ordenable campo="created_at" :sortActual="$sort" :orderActual="$order" texto="Creado" />
                <th class="p-2 border">Acciones</th>
            </x-tabla.header-row>

            {{-- Fila de filtros --}}
            <x-tabla.filtro-row>
                <x-tabla.filtro-input model="id" placeholder="ID" />
                <x-tabla.filtro-input model="albaran" placeholder="Albarán" />
                <x-tabla.filtro-input model="codigo" placeholder="Código" />

                <x-tabla.filtro-select model="nave_id" placeholder="Todas">
                    @foreach($naves as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </x-tabla.filtro-select>

                <x-tabla.filtro-input model="fabricante" placeholder="Fabricante" />
                <x-tabla.filtro-input model="tipo" placeholder="Tipo" />
                <x-tabla.filtro-input model="diametro" placeholder="Ø" />
                <x-tabla.filtro-input model="longitud" placeholder="Long." />
                <x-tabla.filtro-input model="n_colada" placeholder="N° Colada" />
                <x-tabla.filtro-input model="n_paquete" placeholder="N° Paquete" />
                <x-tabla.filtro-vacio />
                <x-tabla.filtro-vacio />

                <x-tabla.filtro-select model="estado" placeholder="Todos">
                    <option value="almacenado">Almacenado</option>
                    <option value="fabricando">Fabricando</option>
                    <option value="consumido">Consumido</option>
                </x-tabla.filtro-select>

                <x-tabla.filtro-input model="ubicacion" placeholder="Ubicación" />
                <x-tabla.filtro-vacio />
                <x-tabla.filtro-acciones />
            </x-tabla.filtro-row>
        </x-tabla.header>

        <x-tabla.body>
            @forelse($productos as $producto)
                <x-tabla.row>
                    <x-tabla.cell>{{ $producto->id }}</x-tabla.cell>

                    <x-tabla.cell>
                        @if($producto->entrada)
                            <a href="{{ route('entradas.index', ['albaran' => $producto->entrada->albaran]) }}" class="text-blue-600 hover:underline">
                                {{ $producto->entrada->albaran }}
                            </a>
                        @else
                            —
                        @endif
                    </x-tabla.cell>

                    <x-tabla.cell>{{ $producto->codigo ?? 'N/A' }}</x-tabla.cell>
                    <x-tabla.cell>{{ $producto->obra->obra ?? '—' }}</x-tabla.cell>
                    <x-tabla.cell>{{ $producto->fabricante->nombre ?? '—' }}</x-tabla.cell>
                    <x-tabla.cell>{{ ucfirst($producto->productoBase->tipo ?? '—') }}</x-tabla.cell>
                    <x-tabla.cell>{{ $producto->productoBase->diametro ?? '—' }}</x-tabla.cell>
                    <x-tabla.cell>{{ $producto->productoBase->longitud ?? '—' }}</x-tabla.cell>
                    <x-tabla.cell>{{ $producto->n_colada }}</x-tabla.cell>
                    <x-tabla.cell>{{ $producto->n_paquete }}</x-tabla.cell>
                    <x-tabla.cell>{{ $producto->peso_inicial }} kg</x-tabla.cell>
                    <x-tabla.cell>{{ $producto->peso_stock }} kg</x-tabla.cell>

                    <x-tabla.cell>
                        @if($producto->estado === 'consumido')
                            <div class="relative group inline-block">
                                <span class="cursor-help">{{ $producto->estado }}</span>
                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                                    <div class="font-semibold mb-1">Información de consumo</div>
                                    @if($producto->fecha_consumido)
                                        <div>📅 {{ \Carbon\Carbon::parse($producto->fecha_consumido)->format('d/m/Y H:i') }}</div>
                                    @endif
                                    @if($producto->consumidoPor)
                                        <div>👤 {{ $producto->consumidoPor->nombre_completo }}</div>
                                    @endif
                                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
                                </div>
                            </div>
                        @else
                            {{ $producto->estado }}
                        @endif
                    </x-tabla.cell>

                    <x-tabla.cell>
                        @if($producto->ubicacion)
                            {{ $producto->ubicacion->nombre }}
                        @elseif($producto->maquina)
                            {{ $producto->maquina->nombre }}
                        @else
                            No está ubicada
                        @endif
                    </x-tabla.cell>

                    <x-tabla.cell>{{ $producto->created_at->format('d/m/Y') }}</x-tabla.cell>

                    <x-tabla.cell>
                        <div class="flex items-center space-x-2 justify-center">
                            <a href="{{ route('productos.edit', $producto->id) }}" class="w-6 h-6 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 flex items-center justify-center" title="Editar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                </svg>
                            </a>
                            <a href="{{ route('productos.show', $producto->id) }}" class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center" title="Ver">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                            <button type="button" onclick="abrirModalMovimientoLibre('{{ $producto->codigo }}')" class="w-6 h-6 bg-green-100 text-green-600 rounded hover:bg-green-200 flex items-center justify-center" title="Mover producto">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M8.5 2A1.5 1.5 0 0 1 10 3.5V10h.5V4A1.5 1.5 0 0 1 13 4v6h.5V5.5a1.5 1.5 0 0 1 3 0V10h.5V7a1.5 1.5 0 0 1 3 0v9.5a3.5 3.5 0 0 1-7 0V18h-2a3 3 0 0 1-3-3v-4H8V3.5A1.5 1.5 0 0 1 8.5 2z" />
                                </svg>
                            </button>
                            <a href="{{ route('productos.editarConsumir', $producto->id) }}" data-consumir="{{ route('productos.editarConsumir', $producto->id) }}" class="btn-consumir w-6 h-6 bg-red-100 text-red-600 rounded hover:bg-red-200 flex items-center justify-center" title="Consumir">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M13.5 3.5c-2 2-1.5 4-3 5.5s-4 1-4 5a6 6 0 0012 0c0-2-1-3.5-2-4.5s-1-3-3-6z" />
                                </svg>
                            </a>
                            <x-tabla.boton-eliminar :action="route('productos.destroy', $producto->id)" />
                        </div>
                    </x-tabla.cell>
                </x-tabla.row>
            @empty
                <x-tabla.empty-state colspan="16" mensaje="No hay productos con esa descripción." />
            @endforelse
        </x-tabla.body>

        <x-tabla.footer-total
            colspan="16"
            label="Total peso filtrado"
            :value="number_format($totalPesoInicial, 2, ',', '.') . ' kg'"
        />
    </x-tabla.wrapper>

    {{-- Paginación --}}
    <div class="mt-4">
        {{ $productos->links('vendor.livewire.tailwind') }}
    </div>

    {{-- Script para botón consumir con SweetAlert --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Delegación de eventos para botones "Consumir"
            document.body.addEventListener('click', async (e) => {
                const btn = e.target.closest('.btn-consumir');
                if (!btn) return;

                e.preventDefault();

                const url = btn.dataset.consumir || btn.getAttribute('href');

                const { value: opcion } = await Swal.fire({
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
                    // Consumir completo
                    if (opcion === true) {
                        window.location.href = url + '?modo=total';
                    }
                } else if (opcion === false) {
                    // Consumir por kilos
                    const { value: kilos } = await Swal.fire({
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
                                Swal.showValidationMessage('Debes indicar un número válido mayor que 0');
                                return false;
                            }
                            return value;
                        }
                    });

                    if (kilos) {
                        window.location.href = url + '?modo=parcial&kgs=' + kilos;
                    }
                }
            });
        });
    </script>
</div>
