<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Entradas de Material') }}
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
    <style>
        .accordion-button {
            font-weight: bold;
        }

        .accordion-button::after {
            background-image: none;
        }

        .accordion-button.collapsed::after {
            background-image: none;
        }

        .accordion-item {
            border: none;
        }

        .accordion-body {
            background-color: #f8f9fa;
        }

        input[disabled] {
            background-color: #e9ecef;
        }

        /* Estilo para los títulos de los productos */
        .product-title {
            font-weight: bold;
            font-size: 1.5rem;
            /* Aumenta el tamaño de la fuente */
            color: #2C3E50;
            /* Un color oscuro para resaltar el texto */
            text-transform: uppercase;
            /* Hace que el texto esté en mayúsculas */
            letter-spacing: 2px;
            /* Espaciado entre las letras */
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            /* Sombra sutil para dar profundidad */
            margin: 12px 0;
            /* Espacio debajo del título */
            padding: 8px;
            /* Relleno alrededor del texto */
            background-color: #9fcfff;
            /* Fondo de color claro */
        }
    </style>

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
                        <form action="{{ route('entradas.store') }}" method="POST">
                            @csrf

                            <!-- Albarán -->
                            <div class="form-group">
                                <label for="albaran">Albarán</label>
                                <input type="text" name="albaran" id="albaran" class="form-control" required>
                            </div>

                            <!-- Peso Total -->
                            <div class="form-group">
                                <label for="peso_total">Peso TOTAL</label>
                                <input type="text" name="peso_total" id="peso_total" class="form-control" required>
                            </div>

                            <!-- Número de productos -->
                            <div class="form-group">
                                <label for="cantidad_productos">Cantidad de Productos</label>
                                <input type="number" name="cantidad_productos" id="cantidad_productos"
                                    class="form-control" min="1" required>
                            </div>

                            <!-- Contenedor para productos -->
                            <div id="productos_container">
                                <!-- Los productos se agregarán aquí dinámicamente -->
                            </div>

                            <button type="submit" class="btn btn-success mt-3">Registrar Entrada</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Fabricante 2 -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        GETAFE
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo"
                    data-bs-parent="#fabricantesAccordion">
                    <div class="accordion-body">
                        <label for="fabricante-2">Fabricante:</label>
                        <input type="text" id="fabricante-2" value="Fabricante 2" class="form-control mb-3" disabled>
                        <p>Información adicional sobre el Fabricante 2...</p>
                    </div>
                </div>
            </div>

            <!-- Fabricante 3 -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        NERVADUCTIL
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree"
                    data-bs-parent="#fabricantesAccordion">
                    <div class="accordion-body">
                        <label for="fabricante-3">Fabricante:</label>
                        <input type="text" id="fabricante-3" value="Fabricante 3" class="form-control mb-3" disabled>
                        <p>Información adicional sobre el Fabricante 3...</p>
                    </div>
                </div>
            </div>

            <!-- Fabricante 4 -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFour">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                        SIDERURGIA SEVILLANA
                    </button>
                </h2>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour"
                    data-bs-parent="#fabricantesAccordion">
                    <div class="accordion-body">
                        <label for="fabricante-4">Fabricante:</label>
                        <input type="text" id="fabricante-4" value="Fabricante 4" class="form-control mb-3" disabled>
                        <p>Información adicional sobre el Fabricante 4...</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Incluir el JS de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Creamos un objeto para almacenar los datos de los productos en memoria
        let productosData = [];

        document.getElementById('cantidad_productos').addEventListener('input', function() {
            let cantidad = this.value;
            let container = document.getElementById('productos_container');
            let currentProductForms = container.querySelectorAll(
                '.product-form'); // Obtener los formularios existentes

            // Si la cantidad de productos es mayor, agregamos nuevos formularios
            if (cantidad > currentProductForms.length) {
                // Crear formularios adicionales
                for (let i = currentProductForms.length; i < cantidad; i++) {
                    let div = document.createElement('div');
                    div.classList.add('product-form');

                    div.innerHTML = `
                    
                        <h4 class="product-title">Producto ${i + 1}</h4>
                        <div class="form-group" style="position: absolute; left: -9999px;">
                            <label for="fabricante${i + 1}">Fabricante</label>
                            <input type="text" name="fabricante[]" id="fabricante${i + 1}" class="form-control"  value="MEGASA" readonly>
                        </div>
                        <div class="form-group" style="position: absolute; left: -9999px;">
                            <label for="producto_nombre_${i + 1}">Nombre del Producto</label>
                            <input type="text" name="producto_nombre[]" id="producto_nombre_${i + 1}" class="form-control" value="Corrugado" readonly>
                        </div>
                        <!-- Tipo de Producto -->
                        <div class="form-group">
                            <label for="tipo_producto_${i + 1}">Tipo de Producto</label><br>
                            <select name="tipo_producto[]" id="tipo_producto_${i + 1}" required>
                                <option value="" disabled selected>Seleccione el tipo de producto</option>
                                <option value="encarretado">Encarretado</option>
                                <option value="barra">Barra</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="producto_codigo_${i + 1}">Código del Producto</label>
                            <input type="text" name="producto_codigo[]" id="producto_codigo_${i + 1}" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="producto_peso_${i + 1}">Peso Inicial</label>
                            <input type="number" name="producto_peso[]" id="producto_peso_${i + 1}" class="form-control" step="0.01">
                        </div>
                        <div class="form-group">
                            <label for="producto_otros_${i + 1}">Otros detalles</label>
                            <input type="text" name="producto_otros[]" id="producto_otros_${i + 1}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="ubicacion_id_${i + 1}">Ubicación</label><br>
                            <select name="ubicacion_id[]" id="ubicacion_id_${i + 1}" required>
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
            // Si la cantidad de productos es menor, eliminamos los formularios sobrantes
            else if (cantidad < currentProductForms.length) {
                // Eliminar los formularios sobrantes
                for (let i = currentProductForms.length - 1; i >= cantidad; i--) {
                    currentProductForms[i].remove();
                }
            }

            // Restaurar los datos de los productos
            currentProductForms = container.querySelectorAll('.product-form');
            currentProductForms.forEach((form, index) => {
                let nombreInput = form.querySelector(`#producto_nombre_${index + 1}`);
                let codigoInput = form.querySelector(`#producto_codigo_${index + 1}`);
                let pesoInput = form.querySelector(`#producto_peso_${index + 1}`);
                let otrosInput = form.querySelector(`#producto_otros_${index + 1}`);
                let ubicacionSelect = form.querySelector(`#ubicacion_id_${index + 1}`);

                // Restaurar los valores de cada campo
                if (productosData[index]) {
                    nombreInput.value = productosData[index].nombre || '';
                    codigoInput.value = productosData[index].codigo || '';
                    pesoInput.value = productosData[index].peso || '';
                    otrosInput.value = productosData[index].otros || '';
                    ubicacionSelect.value = productosData[index].ubicacion || '';
                }
            });
        });

        // Guardar los datos de los productos en memoria cuando el usuario cambie los valores
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('input', function() {
                let index = Array.from(this.closest('.product-form').parentElement.children).indexOf(this
                    .closest('.product-form'));

                // Si no existe en productosData, lo creamos
                if (!productosData[index]) {
                    productosData[index] = {};
                }

                // Guardar el valor del campo en productosData
                productosData[index][this.name] = this.value;
            });
        });
    </script>


</x-app-layout>
