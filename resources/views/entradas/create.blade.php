<x-app-layout>
    <x-slot name="header">
        <h2 class="tw-font-semibold tw-text-xl tw-text-gray-800 tw-leading-tight">
            {{ __('Crear Entradas de Material') }}
        </h2>
    </x-slot>

    <!-- Manejo de Errores y Mensajes de Sesión -->
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

    <!-- Estilos Personalizados -->
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
            color: #2C3E50;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            margin: 12px 0;
            padding: 8px;
            background-color: #9fcfff;
        }
    </style>

    <!-- Contenedor Principal -->
    <div class="container my-5">
        <div class="accordion" id="fabricantesAccordion">
            <!-- Repetir para cada fabricante -->
            @php
                $fabricantes = ['MEGASA', 'GETAFE', 'NERVADUCTIL', 'SIDERURGICA SEVILLANA'];
            @endphp

            @foreach ($fabricantes as $index => $fabricante)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading{{ $index }}">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapse{{ $index }}" aria-expanded="false"
                            aria-controls="collapse{{ $index }}">
                            {{ $fabricante }}
                        </button>
                    </h2>
                    <div id="collapse{{ $index }}" class="accordion-collapse collapse"
                        aria-labelledby="heading{{ $index }}" data-bs-parent="#fabricantesAccordion">
                        <div class="accordion-body">
                            <form action="{{ route('entradas.store') }}" method="POST" class="needs-validation"
                                novalidate>
                                @csrf

                                <!-- Albarán -->
                                <div class="form-group mb-3">
                                    <label class="label-form" for="albaran_{{ $index }}">Albarán</label>
                                    <input type="text" name="albaran" id="albaran_{{ $index }}"
                                        class="form-control" required maxlength="10" minlength="4">
                                    <div class="invalid-feedback">
                                        Por favor, ingresa el albarán con al menos 4 y máximo 10 caracteres.
                                    </div>
                                </div>

                                <!-- Peso Total -->
                                <div class="form-group mb-3">
                                    <label for="peso_total_{{ $index }}">Peso TOTAL</label>
                                    <input type="number" step="0.01" name="peso_total"
                                        id="peso_total_{{ $index }}" class="form-control" required
                                        min="1">
                                    <div class="invalid-feedback">
                                        Por favor, ingresa un peso total válido (mínimo 1).
                                    </div>
                                </div>

                                <!-- Número de productos -->
                                <div class="form-group mb-3">
                                    <label for="cantidad_productos_{{ $index }}">Cantidad de Productos</label>
                                    <input type="number" name="cantidad_productos"
                                        id="cantidad_productos_{{ $index }}" class="form-control" min="1"
                                        max="30" required>
                                    <div class="invalid-feedback">
                                        Por favor, ingresa una cantidad válida de productos (1-30).
                                    </div>
                                </div>

                                <!-- Fabricante -->
                                <div class="form-group" style="position: absolute; left: -9999px;">
                                    <label for="fabricante_{{ $index }}">Fabricante</label>
                                    <input type="text" name="fabricante" id="fabricante_{{ $index }}"
                                        class="form-control" value="{{ $fabricante }}" readonly>
                                </div>

                                <!-- Contenedor para productos -->
                                <div id="productos_container_{{ $index }}">
                                    <!-- Los productos se agregarán aquí dinámicamente -->
                                </div>

                                <button type="submit" class="btn btn-success mt-3">Registrar Entrada</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Incluir el JS de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script de Validación y Generación Dinámica de Campos -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Configuración específica de los campos a mostrar según el fabricante
            const fabricanteConfig = {
                MEGASA: {
                    campos: ['producto_nombre', 'tipo_producto', 'producto_codigo', 'producto_peso',
                        'producto_otros',
                        'ubicacion_id'
                    ],
                    producto_codigo: {
                        minlength: 80,
                        maxlength: 85 // Define el maxlength específico para MEGASA
                    },
                },

                GETAFE: {
                    campos: ['producto_nombre', 'tipo_producto', 'producto_codigo', 'diametro', 'longitud',
                        'n_colada', 'n_paquete',
                        'producto_peso', 'producto_otros',
                        'ubicacion_id'
                    ],
                    producto_codigo: {
                        minlength: 9,
                        maxlength: 9 // Define el maxlength específico para GETAFE
                    },
                },

                NERVADUCTIL: {
                    campos: ['producto_nombre', 'tipo_producto', 'producto_codigo', 'diametro', 'longitud',
                        'n_colada', 'n_paquete',
                        'producto_peso', 'producto_otros',
                        'ubicacion_id'
                    ],
                    producto_codigo: {
                        minlength: 10,
                        maxlength: 10 // Define el maxlength específico para NERVADUCTIL
                    },
                },
                "SIDERURGICA SEVILLANA": { // Debe estar entre comillas porque contiene un espacio
                    campos: ['producto_nombre', 'tipo_producto', 'producto_codigo', 'diametro', 'longitud',
                        'n_colada', 'producto_peso', 'producto_otros', 'ubicacion_id'
                    ],
                    producto_codigo: {
                        minlength: 100,
                        maxlength: 100 // Define el maxlength específico para SIDERURGICA SEVILLANA
                    },
                },
            };

            const fabricantesData = {}; // Para almacenar los datos de cada fabricante dinámicamente
            // Opciones permitidas para diametro y longitud
            const diametroOpciones = [8, 10, 12, 16, 20, 25, 32];
            const longitudOpciones = [6, 12, 14, 15, 16];

            // Iterar sobre cada formulario basado en el índice del fabricante
            @foreach ($fabricantes as $index => $fabricante)
                (function(idx, fab) {
                    const cantidadInput = document.getElementById(`cantidad_productos_${idx}`);
                    const productosContainer = document.getElementById(`productos_container_${idx}`);

                    cantidadInput.addEventListener('input', function() {
                        const config = fabricanteConfig[fab] || {};
                        const campos = config.campos || [];

                        let cantidad = parseInt(this.value) || 0;

                        // Limitar la cantidad máxima a 25
                        if (cantidad > 25) {
                            cantidad = 25;
                            this.value = 25;
                            alert("No puedes agregar más de 25 productos.");
                        }
                        let currentProductForms = productosContainer.querySelectorAll('.product-form');

                        // Inicializar datos del fabricante si no existen
                        if (!fabricantesData[fab]) {
                            fabricantesData[fab] = [];
                        }

                        // Agregar formularios si la cantidad aumenta
                        if (cantidad > currentProductForms.length) {
                            for (let i = currentProductForms.length; i < cantidad; i++) {
                                let div = document.createElement('div');
                                div.classList.add('product-form');

                                let html = `<h4 class="product-title">Producto ${i + 1}</h4>`;

                                // Generar inputs dinámicos según los campos configurados para el fabricante
                                campos.forEach(campo => {
                                    const campoId = `${campo}_${i + 1}_${idx}`;
                                    if (campo === 'producto_nombre') {
                                        html += `
                                            <div class="form-group mb-3">
                                                <label for="${campoId}">Nombre del Producto</label>
                                                <input type="text" name="producto_nombre[]" id="${campoId}" class="form-control" value="Corrugado" readonly>
                                            </div>
                                        `;
                                    } else if (campo === 'tipo_producto') {
                                        html += `
                                            <div class="form-group mb-3">
                                                <label for="${campoId}">Tipo de Producto</label>
                                                <select name="tipo_producto[]" id="${campoId}" class="form-control" required>
                                                    <option value="" disabled selected>Seleccione el tipo de producto</option>
                                                    <option value="encarretado">Encarretado</option>
                                                    <option value="barra">Barra</option>
                                                </select>
                                                <div class="invalid-feedback">
                                                    Por favor, selecciona un tipo de producto.
                                                </div>
                                            </div>
                                        `;
                                    } else if (campo === 'producto_codigo') {
                                        // Obtener las configuraciones específicas del fabricante
                                        const codigoConfig = config.producto_codigo || {};
                                        const minlength = codigoConfig.minlength ? codigoConfig
                                            .minlength : 10; // Valor por defecto
                                        const maxlength = codigoConfig.maxlength ? codigoConfig
                                            .maxlength : 255;
                                        html += `
                                            <div class="form-group mb-3">
                                                <label for="${campoId}">Código del Producto</label>
                                                <input type="text" name="producto_codigo[]" id="${campoId}" class="form-control" required maxlength="${config.producto_codigo.maxlength}">
                                                <div class="invalid-feedback">
                                                    Mínimo ${config.producto_codigo.minlength} y máximo ${config.producto_codigo.maxlength} caracteres.
                                                </div>
                                            </div>
                                        `;
                                    } else if (campo === 'diametro') {
                                        html += `
                                            <div class="form-group mb-3">
                                                <label for="${campoId}">Diámetro</label>
                                                <select name="diametro[]" id="${campoId}" class="form-control" required>
                                                    <option value="" disabled selected>Seleccione el diámetro</option>
                                                    ${diametroOpciones.map(op => `<option value="${op}">${op}</option>`).join('')}
                                                </select>
                                                <div class="invalid-feedback">
                                                    Por favor, selecciona un diámetro válido.
                                                </div>
                                            </div>
                                        `;
                                    } else if (campo === 'longitud') {
                                        html += `
                                            <div class="form-group mb-3">
                                                <label for="${campoId}">Longitud</label>
                                                <select name="longitud[]" id="${campoId}" class="form-control">
                                                    <option value="" disabled selected>Seleccione la longitud</option>
                                                    ${longitudOpciones.map(op => `<option value="${op}">${op}</option>`).join('')}
                                                </select>
                                                <div class="invalid-feedback">
                                                    Por favor, selecciona una longitud válida.
                                                </div>
                                            </div>
                                        `;
                                    } else if (campo === 'n_colada') {
                                        html += `
                                            <div class="form-group mb-3">
                                                <label for="${campoId}">Nº Colada</label>
                                                <input type="text" name="n_colada[]" id="${campoId}" class="form-control" required maxlength="255">
                                                <div class="invalid-feedback">
                                                    Por favor, ingresa el Nº Colada (máximo 255 caracteres).
                                                </div>
                                            </div>
                                        `;
                                    } else if (campo === 'n_paquete') {
                                        html += `
                                            <div class="form-group mb-3">
                                                <label for="${campoId}">Nº Paquete</label>
                                                <input type="text" name="n_paquete[]" id="${campoId}" class="form-control" required maxlength="255">
                                                <div class="invalid-feedback">
                                                    Por favor, ingresa el Nº Paquete (máximo 255 caracteres).
                                                </div>
                                            </div>
                                        `;
                                    } else if (campo === 'producto_peso') {
                                        html += `
                                            <div class="form-group mb-3">
                                                <label for="${campoId}">Peso Inicial</label>
                                                <input type="number" name="producto_peso[]" id="${campoId}" class="form-control" step="0.01" required min="0">
                                                <div class="invalid-feedback">
                                                    Por favor, ingresa un peso válido.
                                                </div>
                                            </div>
                                        `;
                                    } else if (campo === 'producto_otros') {
                                        html += `
                                            <div class="form-group mb-3">
                                                <label for="${campoId}">Otros Detalles</label>
                                                <input type="text" name="producto_otros[]" id="${campoId}" class="form-control" maxlength="255">
                                                <div class="invalid-feedback">
                                                    Máximo 255 caracteres para otros detalles.
                                                </div>
                                            </div>
                                        `;
                                    } else if (campo === 'ubicacion_id') {
                                        html += `
                                            <div class="form-group mb-3">
                                                <label for="${campoId}">Escanea QR ubicación</label>
                                                <input type="text" name="ubicacion_id[]" id="${campoId}" 
                                                    class="form-control" placeholder="Escanea el QR de la ubicación" required>
                                                <div class="invalid-feedback">
                                                    Por favor, escanea el QR de la ubicación.
                                                </div>
                                            </div>
                                        `;
                                    }
                                });

                                div.innerHTML = html;
                                productosContainer.appendChild(div);
                            }
                        }
                        // Si la cantidad de productos es menor, eliminar los formularios sobrantes
                        else if (cantidad < currentProductForms.length) {
                            for (let i = currentProductForms.length - 1; i >= cantidad; i--) {
                                currentProductForms[i].remove();
                            }
                        }
                    });
                })(@json($index), @json($fabricante));
            @endforeach

            // Validación de Bootstrap para todos los formularios
            var forms = document.querySelectorAll('.needs-validation');

            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        // Validación personalizada para peso_total
                        const pesoTotalInput = form.querySelector('input[name="peso_total"]');
                        const pesoTotal = parseFloat(pesoTotalInput.value) || 0;

                        // Obtener todos los producto_peso[] del formulario
                        const pesoProductos = Array.from(form.querySelectorAll(
                                'input[name="producto_peso[]"]'))
                            .map(input => parseFloat(input.value) || 0);

                        // Sumar los pesos de los productos
                        const sumaPesos = pesoProductos.reduce((acc, val) => acc + val, 0);

                        // Comparar con peso_total
                        if (sumaPesos !== pesoTotal) {
                            event.preventDefault();
                            event.stopPropagation();

                            // Mostrar un mensaje de error
                            alert(
                                `El peso total (${pesoTotal}) no coincide con la suma de los pesos de los productos (${sumaPesos}).`
                            );

                            // Opcional: Resaltar el campo peso_total
                            pesoTotalInput.classList.add('is-invalid');
                        } else {
                            // Remover cualquier clase de error si todo está bien
                            pesoTotalInput.classList.remove('is-invalid');
                        }

                        // Proceder con las validaciones de Bootstrap
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }

                        form.classList.add('was-validated');
                    }, false);
                });
        });
    </script>
</x-app-layout>
