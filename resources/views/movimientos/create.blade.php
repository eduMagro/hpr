<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Movimientos') }}
        </h2>
    </x-slot>

    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: '¡Error!',
                text: "{{ session('error') }}",
                confirmButtonText: 'Aceptar'
            });
        </script>
    @endif
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: "{{ session('success') }}",
                confirmButtonText: 'Aceptar'
            });
        </script>
    @endif

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

                            <!-- Seleccionar Producto -->
                            <div class="form-group mb-4">
                                <label for="producto_id" class="form-label fw-bold">Materia Prima</label>
								@if(request('producto_id'))
								<input type="text" name="producto_id" class="form-control" value="{{ request('producto_id') }}" readonly>
@elseif(!request('producto_id'))
    <input type="text" name="producto_id" id="producto_id" class="form-control mb-3"
                                    placeholder="Buscar por QR" value="{{ old('producto_id') }}">
@endif

                                
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
                            <div class="form-group mb-4">
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
    </script>

</x-app-layout>
