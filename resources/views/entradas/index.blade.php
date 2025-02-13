<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Entradas de Material') }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">

        <!-- Botón para crear una nueva entrada con estilo Bootstrap -->
        <div class="mb-4">
            <a href="{{ route('entradas.create') }}" class="btn btn-primary">
                Crear Nueva Entrada
            </a>
        </div>

        <!-- FORMULARIO DE BUSQUEDA -->
        <form method="GET" action="{{ route('entradas.index') }}" class="form-inline mt-3 mb-3">
            <input type="text" name="albaran" class="form-control mb-3" placeholder="Buscar por albarán"
                value="{{ request('albaran') }}">

            <input type="text" name="fecha" class="form-control mb-3" placeholder="Buscar por fecha"
                value="{{ request('fecha') }}">

            <button type="submit" class="btn btn-info ml-2">
                <i class="fas fa-search"></i> Buscar
            </button>
        </form>
        <!-- Usamos una estructura de tarjetas para dispositivos móviles -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($entradas as $entrada)
                <div class="bg-white border p-4 shadow-md rounded-lg">
                    <h3 class="font-bold text-xl">{{ $entrada->albaran }}</h3>
                    <p><strong>Fecha:</strong> {{ $entrada->created_at }}</p>
                    <!-- Sección de Fabricantes -->
                    <p><strong>Fabricante:</strong>
                        @php
                            // Obtener los fabricantes únicos de los productos asociados a esta entrada
                            $fabricantes = $entrada->productos->pluck('fabricante')->unique();
                        @endphp

                        @if ($fabricantes->isNotEmpty())
                            @foreach ($fabricantes as $fabricante)
                                {{ $fabricante }}@if (!$loop->last)
                                    ,
                                @endif
                            @endforeach
                        @else
                            No hay fabricantes disponibles.
                        @endif
                    </p>

                    <h4 class="mt-4 font-semibold">Productos Asociados: {{ $entrada->productos->count() }}</h4>
                    <hr style="border: 1px solid #ccc; margin: 10px 0;">
                    <ul>
                        @foreach ($entrada->productos as $producto)
                            <li class="mt-2">
                                <p><strong>ID:</strong> {{ $producto->id }}</p>
                                <a href="{{ route('productos.show', $producto->id) }}"
                                    class="btn btn-sm btn-primary">Ver</a>
                                <p><strong>Producto:</strong> {{ $producto->nombre }} / {{ $producto->tipo }}</p>
                                <p><strong>Diámetro:</strong> {{ $producto->diametro }}</p>
                                <p><strong>Longitud:</strong> {{ $producto->longitud }}</p>
                                <!-- Lista desordenada con los detalles del producto -->
                                @if (isset($producto->ubicacion->nombre))
                                    <p><strong>Ubicación:</strong>
                                        {{ $producto->ubicacion->nombre }}</p>
                                @elseif (isset($producto->maquina->nombre))
                                    <p><strong>Máquina:</strong>
                                        {{ $producto->maquina->nombre }}
                                    </p>
                                @else
                                    <p class="font-bold text-lg text-gray-800 break-words">No está ubicada</p>
                                @endif
                                <p><strong>Otros:</strong> {{ $producto->otros ?? 'N/A' }}</p>
                                <p>
                                    <button
                                        onclick="generateAndPrintQR('{{ $producto->id }}', '{{ $producto->n_colada }}', 'MATERIA PRIMA')"
                                        class="btn btn-primary btn-sm">QR</button>
                                </p>
                                <div id="qrCanvas" style="display:none;"></div>
                            </li>
                            <hr style="border: 1px solid #ccc; margin: 10px 0;">
                        @endforeach
                        <p><small><strong>Usuario: </strong> {{ $entrada->user->name }} </small></p>
                        <hr style="border: 1px solid #ccc; margin: 10px 0;">
                    </ul>

                    <div class="mt-4 flex justify-between">
                        <a href="{{ route('entradas.edit', $entrada->id) }}"
                            class="text-blue-600 hover:text-blue-900">Editar</a>
                        <x-boton-eliminar :action="route('entradas.destroy', $entrada->id)" />
                    </div>
                </div>
                @empty
                    <p>No hay entradas de material disponibles.</p> <!-- Mensaje si no hay datos -->
                @endforelse
            </div>

            <div class="flex justify-center mt-4">
                {{ $entradas->appends(request()->except('page'))->links() }}
            </div>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script src="{{ asset('js/imprimirQr.js') }}"></script>
    </x-app-layout>
