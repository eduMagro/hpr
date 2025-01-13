<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
<<<<<<< HEAD
            {{ __('Importar Planillas') }}
        </h2>
    </x-slot>

    <!-- Mostrar errores de validación -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Mostrar mensajes de éxito o error -->
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="container mx-auto px-4 py-6">
            <!-- Botón para crear una nueva entrada con estilo Bootstrap -->
            <div class="mb-4">
                <a href="{{ route('planillas.create') }}" class="btn btn-primary">
                    Importar Planilla
                </a>
            </div>

=======
            {{ __('Detalles Materia Prima') }}
        </h2>
    </x-slot>
    <div class="container mx-auto px-4 py-6">
        <!-- Tarjetas de productos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

            @if (isset($detalles_producto))
                <div class="bg-white shadow-md rounded-lg p-4">

                    <h3 class="font-bold text-lg text-gray-700">ID Producto: {{ $detalles_producto->id }}</h3>
                    <p><strong>Fabricante:</strong> {{ $detalles_producto->fabricante }}</p>
                    <p><strong>Nombre:</strong> {{ $detalles_producto->nombre }}</p>
                    <p><strong>Tipo:</strong> {{ $detalles_producto->tipo }}</p>
                    <p><strong>Diámetro:</strong> {{ $detalles_producto->diametro }}</p>
                    <p><strong>Longitud:</strong> {{ $detalles_producto->longitud ?? 'N/A' }}</p>
                    <p><strong>Nº Colada:</strong> {{ $detalles_producto->n_colada }}</p>
                    <p><strong>Nº Paquete:</strong> {{ $detalles_producto->n_paquete }}</p>
                    <p><strong>Peso Inicial:</strong> {{ $detalles_producto->peso_inicial }} kg</p>
                    <p><strong>Peso Stock:</strong> {{ $detalles_producto->peso_stock }} kg</p>
                    <p><strong>Estado:</strong> {{ $detalles_producto->estado }}</p>
                    <p><strong>Otros:</strong> {{ $detalles_producto->otros ?? 'N/A' }}</p>
                    <p>
                        <button onclick="generateAndPrintQR('{{ $detalles_producto->id }}')" class="btn btn-primary">Imprimir
                            QR</button>
                    </p>
                    <div id="qrCanvas{{ $detalles_producto->id }}" style="display:none;"></div>

                    <hr class="m-2 border-gray-300">

                    <!-- Detalles de Ubicación o Máquina -->
                    @if (isset($detalles_producto->ubicacion->descripcion))
                        <p class="font-bold text-lg text-gray-800 break-words">
                            {{ $detalles_producto->ubicacion->descripcion }}</p>
                    @elseif (isset($detalles_producto->maquina->nombre))
                        <p class="font-bold text-lg text-gray-800 break-words">{{ $detalles_producto->maquina->nombre }}
                        </p>
                    @else
                        <p class="font-bold text-lg text-gray-800 break-words">No está ubicada</p>
                    @endif
                    <p class="text-gray-600 mt-2">{{ $detalles_producto->created_at->format('d/m/Y H:i') }}</p>

                    <hr class="my-2 border-gray-300">

                    <div class="mt-2 flex justify-between">
                         <!-- Enlace para editar -->
                         <a href="{{ route('productos.edit', $detalles_producto->id) }}"
                            class="text-blue-500 hover:text-blue-700 text-sm">Editar</a>
                            <!-- Formulario para eliminar -->
                        <form action="{{ route('productos.destroy', $detalles_producto->id) }}" method="POST"
                            onsubmit="return confirm('¿Estás seguro de querer eliminar este producto?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500">Eliminar</button>
                        </form>
                    </div>
                </div>
            @else
                <div class="col-span-3 text-center py-4">No hay productos disponibles.</div>
         

            @endif
        </div>
      
>>>>>>> 6fea693 (primercommit)
    </div>
</x-app-layout>