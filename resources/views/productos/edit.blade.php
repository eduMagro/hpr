<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Detalles Materia Prima') }}
        </h2>
    </x-slot>

    <!-- Mostrar errores de validación -->
    @if ($errors->any())
        <div class="alert alert-danger mt-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Mostrar mensajes de éxito o error -->
    @if (session('error'))
        <div class="alert alert-danger mt-4">
            {{ session('error') }}
        </div>
    @endif
    @if (session('success'))
        <div class="alert alert-success mt-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="container mx-auto px-4 py-6">
        <div class="flex justify-center">
            <div class="w-full max-w-2xl">
                <div class="bg-white shadow-lg rounded-lg border-0">
                    <div class="bg-primary text-white text-center py-3 rounded-t-lg">
                        <h2 class="text-uppercase text-lg font-bold">Editar Materia Prima</h2>
                    </div>
                    <div class="p-6">
                        <form action="{{ route('productos.update', $producto->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            @foreach (['fabricante', 'nombre', 'tipo', 'diametro', 'longitud', 'n_colada', 'n_paquete', 'peso_inicial', 'peso_stock', 'ubicacion_id', 'maquina_id', 'estado', 'otros'] as $campo)
                                <div class="form-group mb-4">
                                    <label for="{{ $campo }}"
                                        class="form-label fw-bold text-uppercase">{{ str_replace('_', ' ', ucfirst($campo)) }}</label>
                                    <input type="text" id="{{ $campo }}" name="{{ $campo }}"
                                        class="form-control form-control-lg"
                                        value="{{ old($campo, $producto->$campo) }}">
                                </div>
                            @endforeach

                            <!-- Botón actualizar -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Actualizar Producto</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
