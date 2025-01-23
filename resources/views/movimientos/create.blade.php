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
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center">
                        <h2>Crear Movimiento de Material</h2>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('movimientos.store') }}" method="POST">
                            @csrf

                            <!-- Seleccionar Producto -->
                            <div class="form-group mb-4">
                                <label for="producto_id" class="form-label fw-bold">Materia Prima</label>

                                {{-- BUSCAR POR QR --}}
                                <input type="text" name="producto_id" class="form-control mb-3"
                                    placeholder="Buscar por QR" value="{{ request('producto_id') }}">

                            </div>

                            <!-- Movimiento de una ubicación a otra -->
                            <div class="form-group mb-4">
                                <label for="ubicacion_destino" class="form-label fw-bold">Ubicación Destino</label>
                                <select id="ubicacion_destino" name="ubicacion_destino"
                                    class="form-control form-control-lg">
                                    <option selected value="">Seleccione una nueva ubicación</option>
                                    @foreach ($ubicaciones as $ubicacion)
                                        <option value="{{ $ubicacion->id }}">{{ $ubicacion->descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Movimiento hacia una máquina -->
                            <div class="form-group mb-4">
                                <label for="maquina_id" class="form-label fw-bold">Máquina Destino</label>
                                <select id="maquina_id" name="maquina_id" class="form-control form-control-lg">
                                    <option selected value="">Seleccione una máquina</option>
                                    @foreach ($maquinas as $maquina)
                                        <option value="{{ $maquina->id }}">{{ $maquina->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Botón para enviar -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">Registrar Movimiento</button>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let form = document.getElementById('miFormulario'); // Asegúrate de poner el ID correcto del formulario

            form.addEventListener("submit", function(event) {
                event.preventDefault(); // Evita el envío inmediato

                let formData = new FormData(form);

                fetch(form.action, {
                        method: form.method,
                        body: formData,
                        headers: {
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.confirm) {
                            Swal.fire({
                                title: 'Material en fabricación',
                                text: data.message,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Sí, continuar',
                                cancelButtonText: 'Cancelar'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    form
                                .submit(); // Si el usuario confirma, enviar el formulario
                                }
                            });
                        } else {
                            form.submit();
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        });
    </script>

</x-app-layout>
