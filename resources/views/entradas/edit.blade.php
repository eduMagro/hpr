<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Recepción de Materiales') }}
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

    <div class="container my-5">
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
                <input type="number" name="cantidad_productos" id="cantidad_productos" class="form-control" min="1" value="{{ old('cantidad_productos', $entrada->productos->count()) }}" required>
            </div>

            <!-- Contenedor para productos -->
            <div id="productos_container">
                <!-- Los productos existentes -->
                @foreach ($entrada->productos as $index => $producto)
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
                        <div class="form-group">
                            <label for="producto_codigo_{{ $index + 1 }}">Código del Producto</label>
                            <input type="text" name="producto_codigo[]" id="producto_codigo_{{ $index + 1 }}" class="form-control" value="{{ $producto->qr }}" required>
                        </div>
                        <div class="form-group">
                            <label for="producto_peso_{{ $index + 1 }}">Nº Colada</label>
                            <input type="text" name="producto_peso[]" id="producto_peso_{{ $index + 1 }}" class="form-control" value="{{ $producto->n_colada }}">
                        </div>
                        <div class="form-group">
                            <label for="producto_otros_{{ $index + 1 }}">Nº Paquete</label>
                            <input type="text" name="producto_otros[]" id="producto_otros_{{ $index + 1 }}" class="form-control" value="{{ $producto->n_paquete }}">
                        </div>
                        <div class="form-group">
                            <label for="ubicacion_id_{{ $index + 1 }}">Ubicación</label>
                            <select name="ubicacion_id[]" id="ubicacion_id_{{ $index + 1 }}" class="form-control" required>
                                <option value="">Seleccione una ubicación</option>
                                @foreach ($ubicaciones as $ubicacion)
                                    <option value="{{ $ubicacion->id }}" @if ($ubicacion->id == $producto->ubicacion_id) selected @endif>
                                        {{ $ubicacion->descripcion }}
                                    </option>
                                @endforeach
                            </select><br>
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="submit" class="btn btn-success mt-3">Actualizar Entrada</button>
        </form>
    </div>

    <script>
        document.getElementById('cantidad_productos').addEventListener('input', function () {
            let cantidad = parseInt(this.value) || 0;
            let container = document.getElementById('productos_container');
            let currentCount = container.querySelectorAll('.product-form').length;

            // Añadir formularios si la cantidad es mayor
            if (cantidad > currentCount) {
                for (let i = currentCount; i < cantidad; i++) {
                    let div = document.createElement('div');
                    div.classList.add('product-form');
                    div.innerHTML = `
                        <h4 class="product-title">Producto ${i + 1}</h4>
                        <div class="form-group">
                            <label for="fabricante${i + 1}">Fabricante</label>
                            <input type="text" name="fabricante[]" id="fabricante${i + 1}" class="form-control" value="MEGASA" readonly>
                        </div>
                        <div class="form-group">
                            <label for="producto_nombre_${i + 1}">Nombre del Producto</label>
                            <input type="text" name="producto_nombre[]" id="producto_nombre_${i + 1}" class="form-control" value="Corrugado" readonly>
                        </div>
                        <div class="form-group">
                            <label for="producto_codigo_${i + 1}">Código del Producto</label>
                            <input type="text" name="producto_codigo[]" id="producto_codigo_${i + 1}" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="producto_peso_${i + 1}">Nº Colada</label>
                            <input type="text" name="producto_peso[]" id="producto_peso_${i + 1}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="producto_otros_${i + 1}">Nº Paquete</label>
                            <input type="text" name="producto_otros[]" id="producto_otros_${i + 1}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="ubicacion_id_${i + 1}">Ubicación</label>
                            <select name="ubicacion_id[]" id="ubicacion_id_${i + 1}" class="form-control" required>
                                <option value="">Seleccione una ubicación</option>
                                @foreach ($ubicaciones as $ubicacion)
                                    <option value="{{ $ubicacion->id }}">{{ $ubicacion->descripcion }}</option>
                                @endforeach
                            </select><br>
                        </div>
                    `;
                    container.appendChild(div);
                }
            }
            // Eliminar formularios si la cantidad es menor
            else if (cantidad < currentCount) {
                for (let i = currentCount - 1; i >= cantidad; i--) {
                    container.children[i].remove();
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</x-app-layout>
