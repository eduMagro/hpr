<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Entradas de Material') }}
        </h2>
    </x-slot>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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

    <div class="container my-5">
        <div class="accordion" id="fabricantesAccordion">
            <!-- Fabricante 1 -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                        MEGASA
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne"
                    data-bs-parent="#fabricantesAccordion">
                    <div class="accordion-body">
                        <form action="{{ route('entradas.update', $entrada->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <!-- Albarán -->
                            <div class="form-group">
                                <label for="albaran">Albarán</label>
                                <input type="text" name="albaran" id="albaran" class="form-control" value="{{ old('albaran', $entrada->albaran) }}" required>
                            </div>

                            <!-- Número de productos -->
                            <div class="form-group">
                                <label for="cantidad_productos">Cantidad de Productos</label>
                                <input type="number" name="cantidad_productos" id="cantidad_productos"
                                    class="form-control" value="{{ old('cantidad_productos', $entrada->cantidad_productos) }}" min="1" required>
                            </div>

                            <!-- Contenedor para productos -->
                            <div id="productos_container">
                                <!-- Los productos se agregarán aquí dinámicamente -->
                                @foreach($entrada->productos as $index => $producto)
                                    <div class="product-form">
                                        <h4 class="product-title">Producto {{ $index + 1 }}</h4>

                                        <div class="form-group">
                                            <label for="fabricante{{ $index + 1 }}">Fabricante</label>
                                            <input type="text" name="fabricante[]" id="fabricante{{ $index + 1 }}" class="form-control" value="{{ $producto->fabricante }}" readonly>
                                        </div>

                                        <div class="form-group">
                                            <label for="producto_nombre_{{ $index + 1 }}">Nombre del Producto</label>
                                            <input type="text" name="producto_nombre[]" id="producto_nombre_{{ $index + 1 }}" class="form-control" value="{{ $producto->nombre }}" readonly>
                                        </div>

                                        <!-- Tipo de Producto -->
                                        <div class="form-group">
                                            <label for="tipo_producto">Tipo de Producto</label>
                                            <select name="tipo_producto[]" id="tipo_producto" class="form-control" required>
                                                <option value="encarretado" {{ $producto->tipo_producto == 'encarretado' ? 'selected' : '' }}>Encarretado</option>
                                                <option value="barra" {{ $producto->tipo_producto == 'barra' ? 'selected' : '' }}>Barra</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="producto_codigo_{{ $index + 1 }}">Código del Producto</label>
                                            <input type="text" name="producto_codigo[]" id="producto_codigo_{{ $index + 1 }}" class="form-control" value="{{ $producto->codigo }}" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="producto_peso_{{ $index + 1 }}">Peso Inicial</label>
                                            <input type="number" name="producto_peso[]" id="producto_peso_{{ $index + 1 }}" class="form-control" step="0.01" value="{{ $producto->peso }}">
                                        </div>

                                        <div class="form-group">
                                            <label for="producto_otros_{{ $index + 1 }}">Otros detalles</label>
                                            <input type="text" name="producto_otros[]" id="producto_otros_{{ $index + 1 }}" class="form-control" value="{{ $producto->otros }}">
                                        </div>

                                        <div class="form-group">
                                            <label for="ubicacion_id_{{ $index + 1 }}">Ubicación</label>
                                            <select name="ubicacion_id[]" id="ubicacion_id_{{ $index + 1 }}" class="form-control" required>
                                                <option value="">Seleccione una ubicación</option>
                                                @foreach ($ubicaciones as $ubicacion)
                                                    <option value="{{ $ubicacion->id }}" {{ $producto->ubicacion_id == $ubicacion->id ? 'selected' : '' }}>{{ $ubicacion->descripcion }}</option>
                                                @endforeach
                                            </select><br>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <button type="submit" class="btn btn-success mt-3">Actualizar Entrada</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Incluir el JS de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Al igual que en el create, aquí puedes mantener la funcionalidad de manejo dinámico de productos
    </script>

</x-app-layout>
