<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Recepción de Materiales') }}
        </h2>
    </x-slot>

    <div class="container mx-auto px-4 py-6">
        <form action="{{ route('entradas.store') }}" method="POST">
            @csrf

            <!-- Albarán -->
            <div class="form-group">
                <label for="albaran">Albarán</label>
                <input type="text" name="albaran" id="albaran" class="form-control" required>
            </div>

            <!-- Número de productos -->
            <div class="form-group">
                <label for="cantidad_productos">Cantidad de Productos</label>
                <input type="number" name="cantidad_productos" id="cantidad_productos" class="form-control"
                    min="1" required>
            </div>

            <!-- Contenedor para productos -->
            <div id="productos_container">
                <!-- Los productos se agregarán aquí dinámicamente -->
            </div>

            <button type="submit" class="btn btn-success mt-3">Registrar Entrada</button>
        </form>
    </div>

    <script>
     document.getElementById('cantidad_productos').addEventListener('input', function() {
    let cantidad = this.value;
    let container = document.getElementById('productos_container');
    let currentProductForms = container.querySelectorAll('.product-form');  // Obtener los formularios existentes

    // Si la cantidad de productos es mayor, agregamos nuevos formularios
    if (cantidad > currentProductForms.length) {
        // Crear formularios adicionales
        for (let i = currentProductForms.length + 1; i <= cantidad; i++) {
            let div = document.createElement('div');
            div.classList.add('product-form', `pastel-${(i - 1) % 10 + 1}`); // Asigna el color de forma cíclica

            div.innerHTML = `
                <h4 class="product-title">Producto ${i}</h4>
                <div class="form-group">
                    <label for="producto_nombre_${i}">Nombre del Producto</label>
                    <input type="text" name="producto_nombre[]" id="producto_nombre_${i}" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="producto_codigo_${i}">Código del Producto</label>
                    <input type="text" name="producto_codigo[]" id="producto_codigo_${i}" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="producto_peso_${i}">Peso del Producto</label>
                    <input type="number" name="producto_peso[]" id="producto_peso_${i}" class="form-control" step="0.01">
                </div>
                <div class="form-group">
                    <label for="producto_otros_${i}">Otros detalles</label>
                    <input type="text" name="producto_otros[]" id="producto_otros_${i}" class="form-control">
                </div>
            `;

            container.appendChild(div);
        }
    } 
    // Si la cantidad de productos es menor, eliminamos los formularios sobrantes
    else if (cantidad < currentProductForms.length) {
        // Eliminar los formularios sobrantes
        for (let i = currentProductForms.length; i > cantidad; i--) {
            currentProductForms[i - 1].remove();
        }
    }
});

    </script>
    <script>
        // Seleccionar el campo del código de barras
        const barcodeInput = document.getElementById('codigo_barras');

        // Agregar un listener para prevenir el Enter
        barcodeInput.addEventListener('keydown', function(event) {
            // Verificar si la tecla presionada es Enter (código de tecla 13)
            if (event.key === 'Enter') {
                event.preventDefault(); // Prevenir la acción predeterminada (enviar el formulario)

                // Aquí puedes agregar lo que deseas hacer al escanear el código (por ejemplo, procesar el código)
                console.log('Código de barras escaneado:', barcodeInput.value);

                // Si deseas limpiar el campo de entrada después de procesar el código
                barcodeInput.value = '';
            }
        });
    </script>

    <style>
        /* Estilo para los títulos de los productos */
        .product-title {
            font-weight: bold;
            /* Negrita */
            font-size: 1.2rem;
            /* Tamaño de fuente más grande */
            color: #2C3E50;
            /* Color oscuro para el texto */
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
            /* Sombra sutil */
            margin-bottom: 10px;
            /* Espacio inferior */
        }
    </style>

</x-app-layout>
