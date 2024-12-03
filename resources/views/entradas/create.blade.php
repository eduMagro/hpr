<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Entrada de Material') }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <!-- Formulario para crear una nueva entrada -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-semibold text-xl">Formulario de Entrada de Material</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('entradas.store') }}" method="POST">
                    @csrf
                
                    <!-- Datos de la entrada -->
                    <div class="form-group">
                        <label for="nombre_material">Nombre del Material</label>
                        <input type="text" name="nombre_material" id="nombre_material" class="form-control" required>
                    </div>
                
                    <div class="form-group">
                        <label for="descripcion_material">Descripción del Material</label>
                        <textarea name="descripcion_material" id="descripcion_material" class="form-control" required></textarea>
                    </div>
                
                    <div class="form-group">
                        <label for="ubicacion_id">Ubicación</label>
                        <select name="ubicacion_id" id="ubicacion_id" class="form-control" required>
                            @foreach ($ubicaciones as $ubicacion)
                                <option value="{{ $ubicacion->id }}">{{ $ubicacion->codigo }}</option>
                            @endforeach
                        </select>
                    </div>
                
                    <div class="form-group">
                        <label for="cantidad">Cantidad</label>
                        <input type="number" name="cantidad" id="cantidad" class="form-control" required>
                    </div>
                
                    <!-- Campos del Producto -->
                    <div class="form-group">
                        <label for="producto_nombre">Nombre del Producto</label>
                        <input type="text" name="producto_nombre" id="producto_nombre" class="form-control" required>
                    </div>
                
                    <div class="form-group">
                        <label for="producto_descripcion">Descripción del Producto</label>
                        <textarea name="producto_descripcion" id="producto_descripcion" class="form-control"></textarea>
                    </div>
                
                    <div class="form-group">
                        <label for="producto_precio">Precio del Producto</label>
                        <input type="number" name="producto_precio" id="producto_precio" class="form-control" step="0.01" required>
                    </div>
                
                    <div class="form-group">
                        <label for="producto_stock">Stock del Producto</label>
                        <input type="number" name="producto_stock" id="producto_stock" class="form-control" required>
                    </div>
                
                    <button type="submit" class="btn btn-success">Realizar Entrada</button>
                </form>
                
            </div>
        </div>
    </div>
</x-app-layout>
