<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Movimientos') }}
        </h2>
    </x-slot>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center">
                        <h2>Crear Movimiento de Material</h2>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('movimientos.store') }}" method="POST" id="form-movimiento">
                            @csrf
                            <!-- Seleccionar Producto o Paquete -->
                            <div class="form-group mb-4">
                                <label for="tipo_movimiento" class="form-label fw-bold">Tipo de Movimiento</label>
                                <select id="tipo_movimiento" name="tipo_movimiento" class="form-control">
                                    <option value="producto">Materia Prima</option>
                                    <option value="paquete">Paquete</option>
                                </select>
                            </div>

                            <!-- Seleccionar Producto (solo visible si se elige 'Materia Prima') -->
                            <div id="producto-section" class="form-group mb-4">
                                <label for="producto_id" class="form-label fw-bold">Materia Prima</label>
                                @if (request('producto_id'))
                                    <input type="text" name="producto_id" class="form-control"
                                        value="{{ request('producto_id') }}" readonly>
                                @elseif(!request('producto_id'))
                                    <input type="text" name="producto_id" id="producto_id" class="form-control mb-3"
                                        placeholder="QR Materia Prima" value="{{ old('producto_id') }}">
                                @endif
                            </div>

                            <!-- Seleccionar Paquete (solo visible si se elige 'Paquete') -->
                            <div id="paquete-section" class="form-group mb-4" style="display: none;">
                                <label for="paquete_id" class="form-label fw-bold">Paquete</label>
                                <input type="text" name="paquete_id" id="paquete_id" class="form-control mb-3"
                                    placeholder="QR Paquete" value="{{ old('paquete_id') }}">
                            </div>

                            <!-- Movimiento a Ubicación -->
                            <div class="form-group mb-4">
                                <label for="ubicacion_destino" class="form-label fw-bold">Ubicación Destino</label>
                                <select id="ubicacion_destino" name="ubicacion_destino" class="form-control">
                                    <option selected value="">Seleccione una nueva ubicación</option>
                                    @foreach ($ubicaciones as $ubicacion)
                                        <option value="{{ $ubicacion->id }}">{{ $ubicacion->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Movimiento a Máquina -->
                            <div id="maquina-section" class="form-group mb-4">
                                <label for="maquina_id" class="form-label fw-bold">Máquina Destino</label>
                                <select id="maquina_id" name="maquina_id" class="form-control">
                                    <option selected value="">Seleccione una máquina</option>
                                    @foreach ($maquinas as $maquina)
                                        <option value="{{ $maquina->id }}">{{ $maquina->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Botón de Envío -->
                            <div class="d-grid">
                                <button type="submit" id="submit-btn" class="btn btn-success btn-lg">Registrar
                                    Movimiento</button>
                            </div>
                        </form>

                    </div>
                    <div class="card-footer text-center text-muted">
                        <small>El producto puede moverse a otra ubicación o a una máquina, pero no ambos.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.getElementById("form-movimiento");
            const submitBtn = document.getElementById("submit-btn");

            if (form) {
                form.addEventListener("submit", function(event) {
                    event.preventDefault();
                    submitBtn.disabled = true; // Deshabilitar botón

                    let formData = new FormData(form);

                    fetch(form.action, {
                            method: "POST",
                            body: formData,
                            headers: {
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute("content")
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: "¡Éxito!",
                                    text: data.message,
                                    icon: "success",
                                    confirmButtonText: "Aceptar"
                                }).then(() => {
                                    window.location.href = "{{ route('movimientos.index') }}";
                                });
                            } else if (data.errors) {
                                let errorMessages = Object.values(data.errors).flat().join("\n");
                                Swal.fire({
                                    icon: "error",
                                    title: "Errores de validación",
                                    text: errorMessages,
                                    confirmButtonText: "Aceptar"
                                });
                            } else if (data.error) {
                                Swal.fire({
                                    icon: "error",
                                    title: "Error",
                                    text: data.error,
                                    confirmButtonText: "Aceptar"
                                });
                            }
                            submitBtn.disabled = false; // Rehabilitar el botón si hay errores
                        })
                        .catch(error => {
                            console.error("Error:", error);
                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: "Hubo un problema con la solicitud. Inténtelo otra vez.",
                                confirmButtonText: "Aceptar"
                            });
                            submitBtn.disabled = false;
                        });
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const tipoMovimiento = document.getElementById('tipo_movimiento');
            const productoSection = document.getElementById('producto-section');
            const paqueteSection = document.getElementById('paquete-section');
            const maquinaSection = document.getElementById('maquina-section'); // Corregido

            // Función para manejar la visibilidad de los campos
            function toggleFields() {
                if (tipoMovimiento.value === 'producto') {
                    productoSection.style.display = 'block'; // Mostrar campo de Producto
                    paqueteSection.style.display = 'none'; // Ocultar campo de Paquete
                    maquinaSection.style.display = 'block'; // Mostrar campo de Maquina
                } else if (tipoMovimiento.value === 'paquete') {
                    productoSection.style.display = 'none'; // Ocultar campo de Producto
                    maquinaSection.style.display = 'none'; // Ocultar campo de Maquina
                    paqueteSection.style.display = 'block'; // Mostrar campo de Paquete
                }
            }

            // Inicializar el estado de los campos según la selección actual
            toggleFields();

            // Cambiar visibilidad cuando se seleccione un tipo de movimiento
            tipoMovimiento.addEventListener('change', toggleFields);
        });
    </script>

</x-app-layout>
