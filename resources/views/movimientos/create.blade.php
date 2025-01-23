<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Movimientos') }}
        </h2>
    </x-slot>


    <script>
        function handleConfirm(confirmed) {
            document.getElementById('customConfirm').style.display = 'none';
            if (confirmed) {
                // Crear y enviar el formulario para confirmar la acción
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = "{{ route('movimientos.store') }}";

                // CSRF Token
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = "{{ csrf_token() }}";
                form.appendChild(csrfInput);

                // Campos necesarios
                const confirmarInput = document.createElement('input');
                confirmarInput.type = 'hidden';
                confirmarInput.name = 'confirmar';
                confirmarInput.value = true;
                form.appendChild(confirmarInput);

                const productoIdInput = document.createElement('input');
                productoIdInput.type = 'hidden';
                productoIdInput.name = 'producto_id';
                productoIdInput.value = "{{ session('producto_id') }}";
                form.appendChild(productoIdInput);

                const ubicacionDestinoInput = document.createElement('input');
                ubicacionDestinoInput.type = 'hidden';
                ubicacionDestinoInput.name = 'ubicacion_destino';
                ubicacionDestinoInput.value = "{{ session('ubicacion_destino') }}";
                form.appendChild(ubicacionDestinoInput);

                const maquinaIdInput = document.createElement('input');
                maquinaIdInput.type = 'hidden';
                maquinaIdInput.name = 'maquina_id';
                maquinaIdInput.value = "{{ session('maquina_id') }}";
                form.appendChild(maquinaIdInput);

                document.body.appendChild(form);
                form.submit();
            }
        }

        function handleConsumo(confirmed) {
            document.getElementById('customConfirmConsumo').style.display = 'none';
            if (confirmed) {
                // Crear y enviar el formulario para confirmar el consumo
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = "{{ route('movimientos.store') }}";

                // CSRF Token
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = "{{ csrf_token() }}";
                form.appendChild(csrfInput);

                // Campos necesarios
                const confirmarInput = document.createElement('input');
                confirmarInput.type = 'hidden';
                confirmarInput.name = 'confirmar';
                confirmarInput.value = true;
                form.appendChild(confirmarInput);

                const confirmarConsumoInput = document.createElement('input');
                confirmarConsumoInput.type = 'hidden';
                confirmarConsumoInput.name = 'confirmar_consumo';
                confirmarConsumoInput.value = 1;
                form.appendChild(confirmarConsumoInput);

                const productoIdInput = document.createElement('input');
                productoIdInput.type = 'hidden';
                productoIdInput.name = 'producto_id';
                productoIdInput.value = "{{ session('producto_id') }}";
                form.appendChild(productoIdInput);

                const ubicacionDestinoInput = document.createElement('input');
                ubicacionDestinoInput.type = 'hidden';
                ubicacionDestinoInput.name = 'ubicacion_destino';
                ubicacionDestinoInput.value = "{{ session('ubicacion_destino') }}";
                form.appendChild(ubicacionDestinoInput);

                const maquinaIdInput = document.createElement('input');
                maquinaIdInput.type = 'hidden';
                maquinaIdInput.name = 'maquina_id';
                maquinaIdInput.value = "{{ session('maquina_id') }}";
                form.appendChild(maquinaIdInput);

                document.body.appendChild(form);
                form.submit();
            } else {
                // Crear y enviar el formulario para confirmar el consumo
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = "{{ route('movimientos.store') }}";

                // CSRF Token
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = "{{ csrf_token() }}";
                form.appendChild(csrfInput);

                // Campos necesarios
                const confirmarInput = document.createElement('input');
                confirmarInput.type = 'hidden';
                confirmarInput.name = 'confirmar';
                confirmarInput.value = true;
                form.appendChild(confirmarInput);

                const confirmarConsumoInput = document.createElement('input');
                confirmarConsumoInput.type = 'hidden';
                confirmarConsumoInput.name = 'confirmar_mantenerlo';
                confirmarConsumoInput.value = 1;
                form.appendChild(confirmarConsumoInput);

                const productoIdInput = document.createElement('input');
                productoIdInput.type = 'hidden';
                productoIdInput.name = 'producto_id';
                productoIdInput.value = "{{ session('producto_id') }}";
                form.appendChild(productoIdInput);

                const ubicacionDestinoInput = document.createElement('input');
                ubicacionDestinoInput.type = 'hidden';
                ubicacionDestinoInput.name = 'ubicacion_destino';
                ubicacionDestinoInput.value = "{{ session('ubicacion_destino') }}";
                form.appendChild(ubicacionDestinoInput);

                const maquinaIdInput = document.createElement('input');
                maquinaIdInput.type = 'hidden';
                maquinaIdInput.name = 'maquina_id';
                maquinaIdInput.value = "{{ session('maquina_id') }}";
                form.appendChild(maquinaIdInput);

                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>

    <!-- Mostrar errores de validación -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <!-- Mostrar mensajes de éxito o error -->
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

    @if (session('confirm'))
        <div id="customConfirm" class="overlay" style="display: flex;">
            <div class="dialog">
                <p>{{ session('confirm') }}</p>
                <button class="accept" onclick="handleConfirm(true)">Aceptar</button>
                <button class="cancel" onclick="handleConfirm(false)">Cancelar</button>
            </div>
        </div>
    @endif

    @if (session('confirm_consumo'))
        <div id="customConfirmConsumo" class="overlay" style="display: flex;">
            <div class="dialog">
                <p>{{ session('confirm_consumo') }}</p>
                <button class="accept" onclick="handleConsumo(true)">Chatarra</button>
                <button class="accept" onclick="handleConsumo(false)">Mantenerlo en la máquina</button>
            </div>
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

                                {{-- BUSCAR POR SELECT --}}
                                {{-- <select id="producto_id" name="producto_id" class="form-control form-control-lg" required>
                                    <option value="">Seleccione un producto</option>
                                    @foreach ($productos as $producto)
                                    <option value="{{ $producto->id }}">
                                        {{ $producto->qr }} - 
                                        (Origen: 
                                        @if ($producto->ubicacion)
                                            {{ $producto->ubicacion->descripcion }}
                                        @elseif ($producto->maquina)
                                            Máquina: {{ $producto->maquina->nombre }}
                                        @else
                                            Sin origen
                                        @endif)
                                    </option>
                                @endforeach
                                </select> --}}
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
    <script>
        $(document).on("submit", "#form-material", function(e) {
            e.preventDefault(); // Evita el envío normal del formulario

            let form = $(this);
            let formData = form.serialize();

            $.ajax({
                url: form.attr("action"),
                type: form.attr("method"),
                data: formData,
                success: function(response) {
                    if (response.status === "error") {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: response.message,
                        });
                    } else if (response.status === "confirm") {
                        Swal.fire({
                            title: "¿Está seguro?",
                            text: response.message,
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonText: "Sí, continuar",
                            cancelButtonText: "Cancelar",
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Si confirma, vuelve a enviar el formulario sin preguntar
                                $.ajax({
                                    url: form.attr("action"),
                                    type: form.attr("method"),
                                    data: formData +
                                    "&confirm=true", // Agrega un indicador de confirmación
                                    success: function(response) {
                                        Swal.fire({
                                            icon: "success",
                                            title: "Éxito",
                                            text: "El proceso se completó correctamente.",
                                        }).then(() => {
                                            location
                                        .reload(); // Recargar la página si es necesario
                                        });
                                    },
                                });
                            }
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Ocurrió un problema en el servidor.",
                    });
                },
            });
        });
    </script>

</x-app-layout>
