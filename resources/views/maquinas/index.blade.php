<x-app-layout>
    <x-slot name="title">Máquinas - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Máquinas') }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <!-- Botón para crear una nueva maquina con estilo Bootstrap -->
        <div class="mb-4">
            <a href="{{ route('maquinas.create') }}" class="btn btn-primary">
                Crear Nueva Máquina
            </a>
        </div>
        <!-- FORMULARIO DE BUSQUEDA -->
        <form method="GET" action="{{ route('maquinas.index') }}" class="form-inline mt-3 mb-3">
            <input type="text" name="nombre" class="form-control mb-3" placeholder="Buscar por código de máquina"
                value="{{ request('nombre') }}">
            <button type="submit" class="btn btn-info ml-2">
                <i class="fas fa-search"></i> Buscar
            </button>
        </form>

        <!-- Usamos una estructura de tarjetas para mostrar las ubicaciones -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

            @if (isset($registrosMaquina) &&
                    $registrosMaquina instanceof \Illuminate\Pagination\LengthAwarePaginator &&
                    $registrosMaquina->isNotEmpty())
                @forelse ($registrosMaquina as $maquina)
                    <div class="bg-white border p-4 shadow-md rounded-lg">
                        <h3 class="font-bold text-xl break-words">{{ $maquina->codigo }}</h3>
                        <p><strong>Nombre Máquina:</strong> {{ $maquina->nombre }}</p>
                        <p>

                            <button
                                onclick="generateAndPrintQR('{{ $maquina->id }}', '{{ $maquina->nombre }}', 'MÁQUINA')"
                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                QR
                            </button>

                        </p>
                        <div id="qrCanvas" style="display:none;"></div>

                        <p><strong>Diámetros aceptados:
                            </strong>{{ $maquina->diametro_min . ' - ' . $maquina->diametro_max }}</p>
                        <p><strong>Estado: </strong>
                            @php
                                $enProduccion =
                                    $maquina->tipo == 'ensambladora'
                                        ? $maquina->elementos_ensambladora > 0
                                        : $maquina->elementos_count > 0;
                            @endphp
                            <span class="{{ $enProduccion ? 'text-success' : 'text-danger' }}">
                                {{ $enProduccion ? 'En producción' : 'Sin trabajo' }}
                            </span>
                        </p>

                        <!-- Mostrar los productos que contiene esta ubicación -->
                        <h4 class="mt-4 font-semibold">Productos en máquina:</h4>
                        @if ($maquina->productos->isEmpty())
                            <p>No hay productos en esta máquina.</p>
                        @else
                            <ul class="list-disc pl-6 break-words">
                                @foreach ($maquina->productos->sortBy([['diametro', 'asc'], ['peso_stock', 'asc']]) as $producto)
                                    <li class="mb-2 flex items-center justify-between">
                                        <div style="display: flex; align-items: center; gap: 10px; width: 100%;">


                                            <!-- Cuadro de progreso -->
                                            @if ($producto->tipo == 'ENCARRETADO')
                                                <div
                                                    style="width: 100px; height: 100px; background-color: #ddd; position: relative; overflow: hidden; border-radius: 8px;">
                                                    <div class="cuadro verde"
                                                        style="width: 100%; 
                               height: {{ ($producto->peso_stock / $producto->peso_inicial) * 100 }}%; 
                               background-color: green; 
                               position: absolute; 
                               bottom: 0;">
                                                    </div>
                                                    <span
                                                        style="position: absolute; top: 10px; left: 10px; color: white;">
                                                        {{ $producto->peso_stock }} / {{ $producto->peso_inicial }} kg
                                                    </span>
                                                </div>
                                                <!-- Información del producto -->
                                                <div>
                                                    <p><strong>ID:</strong> {{ $producto->id }}</p>
                                                    <p><strong>Diámetro:</strong> {{ $producto->diametro_mm }}</p>

                                                </div>
                                            @elseif ($producto->tipo == 'BARRA')
                                                <!-- Información del producto -->

                                                <div
                                                    style="width: 200px; height: 30px; background-color: #ddd; position: relative; overflow: hidden; border-radius: 8px;">
                                                    <div class="barra verde"
                                                        style="width: {{ ($producto->peso_stock / $producto->peso_inicial) * 100 }}%; 
                               height: 100%; 
                               background-color: green; 
                               position: absolute; 
                               right: 0;">
                                                    </div>
                                                    <span
                                                        style="position: absolute; top: 50%; left: 10px; transform: translateY(-50%); color: white;">
                                                        {{ $producto->peso_stock }} / {{ $producto->peso_inicial }} kg
                                                    </span>
                                                </div>
                                                <div>
                                                    <p><strong>ID:</strong> {{ $producto->id }}</p>
                                                    <p><strong>Diámetro:</strong> {{ $producto->diametro_mm }}</p>
                                                    @if ($producto->tipo == 'barras')
                                                        <p><strong>Longitud:</strong> {{ $producto->longitud_metros }}
                                                        </p>
                                                    @endif
                                                </div>
                                            @endif


                                            <!-- Botón "Ver" alineado a la derecha -->
                                            <a href="{{ route('productos.show', $producto->id) }}"
                                                class="btn btn-sm btn-primary" style="margin-left: auto;">Ver</a>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <hr style="border: 1px solid #ccc; margin: 10px 0;">

                        <div class="mt-4 flex justify-between items-center">
                            {{-- sweet alert para eliminar --}}
                            <x-boton-eliminar :action="route('maquinas.destroy', $maquina->id)" />

                            <!-- Enlace para editar -->
                            <a href="{{ route('maquinas.edit', $maquina->id) }}"
                                class="text-blue-500 hover:text-blue-700 text-sm">Editar</a>
                            {{-- Enlace para ver --}}
                            <a href="javascript:void(0);" onclick="seleccionarCompañero({{ $maquina->id }})"
                                class="text-blue-500 hover:text-blue-700 text-sm">Iniciar Sesión</a>

                        </div>
                    </div>
                @empty
                    <p>No hay máquinas disponibles.</p> <!-- Mensaje si no hay datos -->
                @endforelse
            @endif
        </div>
        <div class="mt-4 flex justify-center">
            {{ $registrosMaquina->onEachSide(2)->links('vendor.pagination.bootstrap-5') }}
        </div>
        <!-- SCRIPT PARA IMPRIMIR QR -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script src="{{ asset('js/imprimirQr.js') }}"></script>
        <script>
            const usuarios = @json($usuarios);
            const csrfToken = '{{ csrf_token() }}';

            window.rutas = {
                guardarSesion: '{{ route('maquinas.sesion.guardar') }}',
                base: '{{ url('/') }}' // esta es la clave para que sea flexible
            };
        </script>

        </script>
        <script src="{{ asset('js/maquinaJS/seleccionarCompa.js') }}" defer></script>
</x-app-layout>
