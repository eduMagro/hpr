<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detalles Maquina') }}
        </h2>
    </x-slot>
    <div class="container mx-auto px-4 py-6">
        <!-- Tarjetas de productos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @if (isset($maquina))
                <div class="bg-white border p-4 shadow-md rounded-lg">
                    <h3 class="font-bold text-xl break-words">{{ $maquina->codigo }}</h3>
                    <p><strong>Nombre Máquina:</strong> {{ $maquina->nombre }}</p>
                    <p>
                        <button id="generateQR" onclick="generateAndPrintQR('{{ $maquina->codigo }}')" 
                            class="btn btn-primary">QR</button>
                    </p>
                    <div id="qrCanvas" style="display:none;"></div>
                    <p><strong>Diámetros aceptados:</strong> {{ $maquina->diametro_min . " - " . $maquina->diametro_max }}</p>
                    <p><strong>Pesos bobinas:</strong> 
                        {{ ($maquina->peso_min && $maquina->peso_max) ? ($maquina->peso_min . ' - ' . $maquina->peso_max) : 'Barras' }}
                    </p>
                    
                    <!-- Mostrar los productos que contiene esta ubicación -->
                    <h4 class="mt-4 font-semibold">Productos en máquina:</h4>
                    @if ($maquina->productos->isEmpty())
                        <p>No hay productos en esta máquina.</p>
                    @else
                        <ul class="list-disc pl-6 break-words">
                            @foreach ($maquina->productos as $producto)
                                <li class="mb-2 flex items-center justify-between">
                                    <span>
                                        ID{{ $producto->id }} - Tipo: {{ $producto->tipo }} - D{{ $producto->diametro }} 
                                        - L{{ $producto->longitud ?? '??' }}
                                    </span>
                                    <a href="{{ route('productos.show', $producto->id) }}" class="btn btn-sm btn-primary">Ver</a>
                                    @if ($producto->tipo == 'encarretado')
                                        <div style="width: 100px; height: 100px; background-color: #ddd; position: relative; overflow: hidden;">
                                            <div class="cuadro verde" 
                                                style="width: 100%; 
                                                       height: {{ ($producto->peso_stock / $producto->peso_inicial) * 100 }}%; 
                                                       background-color: green; 
                                                       position: absolute; 
                                                       bottom: 0;">
                                            </div>
                                            <span style="position: absolute; top: 10px; left: 10px; color: white;">
                                                {{ $producto->peso_stock }} / {{ $producto->peso_inicial }} kg
                                            </span>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

