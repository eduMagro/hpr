<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
<<<<<<< HEAD
            {{ __('Editar Producto') }}
=======
            {{ __('Editar Detalles Materia Prima') }}
>>>>>>> 6fea693 (primercommit)
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
                        <h2 class="text-uppercase text-lg font-bold">Editar Producto</h2>
                    </div>
                    <div class="p-6">
                        <form action="{{ route('productos.update', $producto->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <!-- Fabricante -->
                            <div class="form-group mb-4">
                                <label for="fabricante" class="form-label fw-bold text-uppercase">Fabricante</label>
                                <input type="text" id="fabricante" name="fabricante" class="form-control form-control-lg"
                                    value="{{ old('fabricante', $producto->fabricante) }}" placeholder="Introduce el fabricante" required>
                            </div>

                            <!-- Nombre -->
                            <div class="form-group mb-4">
                                <label for="nombre" class="form-label fw-bold text-uppercase">Nombre del Producto</label>
                                <input type="text" id="nombre" name="nombre" class="form-control form-control-lg"
                                    value="{{ old('nombre', $producto->nombre) }}" placeholder="Introduce el nombre del producto" required>
                            </div>

                            <!-- Tipo -->
                            <div class="form-group mb-4">
                                <label for="tipo" class="form-label fw-bold text-uppercase">Tipo</label>
                                <input type="text" id="tipo" name="tipo" class="form-control form-control-lg"
                                    value="{{ old('tipo', $producto->tipo) }}" placeholder="Introduce el tipo de producto" required>
                            </div>

                            <!-- Diámetro -->
                            <div class="form-group mb-4">
                                <label for="diametro" class="form-label fw-bold text-uppercase">Diámetro</label>
                                <input type="number" step="0.01" id="diametro" name="diametro" class="form-control form-control-lg"
                                    value="{{ old('diametro', $producto->diametro) }}" placeholder="Introduce el diámetro" required>
                            </div>

                            <!-- Longitud (Opcional) -->
                            <div class="form-group mb-4">
                                <label for="longitud" class="form-label fw-bold text-uppercase">Longitud</label>
                                <input type="number" step="0.01" id="longitud" name="longitud" class="form-control form-control-lg"
                                    value="{{ old('longitud', $producto->longitud) }}" placeholder="Introduce la longitud (opcional)">
                            </div>

                            <!-- Nº Colada -->
                            <div class="form-group mb-4">
                                <label for="n_colada" class="form-label fw-bold text-uppercase">Nº Colada</label>
                                <input type="text" id="n_colada" name="n_colada" class="form-control form-control-lg"
                                    value="{{ old('n_colada', $producto->n_colada) }}" placeholder="Introduce el número de colada" required>
                            </div>

                            <!-- Nº Paquete -->
                            <div class="form-group mb-4">
                                <label for="n_paquete" class="form-label fw-bold text-uppercase">Nº Paquete</label>
                                <input type="text" id="n_paquete" name="n_paquete" class="form-control form-control-lg"
                                    value="{{ old('n_paquete', $producto->n_paquete) }}" placeholder="Introduce el número de paquete" required>
                            </div>

                            <!-- Peso Inicial -->
                            <div class="form-group mb-4">
                                <label for="peso_inicial" class="form-label fw-bold text-uppercase">Peso Inicial (kg)</label>
                                <input type="number" step="0.01" id="peso_inicial" name="peso_inicial" class="form-control form-control-lg"
                                    value="{{ old('peso_inicial', $producto->peso_inicial) }}" placeholder="Introduce el peso inicial" required>
                            </div>

                            <!-- Peso Stock -->
                            <div class="form-group mb-4">
                                <label for="peso_stock" class="form-label fw-bold text-uppercase">Peso Stock (kg)</label>
                                <input type="number" step="0.01" id="peso_stock" name="peso_stock" class="form-control form-control-lg"
                                    value="{{ old('peso_stock', $producto->peso_stock) }}" placeholder="Introduce el peso en stock" required>
                            </div>
                            
                            <!-- Otros (Opcional) -->
                            <div class="form-group mb-4">
                                <label for="otros" class="form-label fw-bold text-uppercase">Otros</label>
                                <textarea id="otros" name="otros" class="form-control form-control-lg" rows="3"
                                    placeholder="Información adicional (opcional)">{{ old('otros', $producto->otros) }}</textarea>
                            </div>

                            <!-- Botón para actualizar -->
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
