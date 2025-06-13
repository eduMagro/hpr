<x-app-layout>
    <x-slot name="title">Planillas - {{ config('app.name') }}</x-slot>
    @if (auth()->user()->rol !== 'operario')
        <x-menu.movimientos />
    @endif
    <div class="max-w-3xl mx-auto mt-10">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-blue-600 text-white text-center py-4 px-6">
                <h2 class="text-xl font-semibold">Crear Movimiento de Material</h2>
            </div>

            <div class="p-6">
                <form action="{{ route('movimientos.store') }}" method="POST" id="form-movimiento" class="space-y-6">
                    @csrf

                    {{-- Código Escaneado --}}
                    <x-tabla.input-movil name="codigo_general" id="codigo_general"
                        label="Código de Materia Prima o Paquete" placeholder="Escanear QR"
                        value="{{ old('codigo_general') }}" inputmode="none" autocomplete="off" />

                    {{-- Ubicación destino --}}
                    <x-tabla.input-movil name="ubicacion_destino" placeholder="Escanear ubicación"
                        value="{{ old('ubicacion_destino') }}" inputmode="none" autocomplete="off" />

                    {{-- Botón de envío --}}
                    <div class="pt-4">
                        <button type="submit" id="submit-btn"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
                            Registrar Movimiento
                        </button>

                    </div>
                </form>
            </div>

            <div class="bg-gray-100 text-center text-sm text-gray-600 py-3 px-6">
                El producto puede moverse a otra ubicación o a una máquina, pero no ambos.
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.getElementById("form-movimiento");
            const submitBtn = document.getElementById("submit-btn");

            const tipoMovimiento = document.getElementById("tipo_movimiento");
            const productoSection = document.getElementById("producto-section");
            const paqueteSection = document.getElementById("paquete-section");
            const maquinaSection = document.getElementById("maquina-section");

            function toggleFields() {
                const tipo = tipoMovimiento.value;

                if (tipo === "producto") {
                    productoSection.style.display = "block";
                    paqueteSection.style.display = "none";
                    maquinaSection.style.display = "block";
                } else if (tipo === "paquete") {
                    productoSection.style.display = "none";
                    paqueteSection.style.display = "block";
                    maquinaSection.style.display = "none";
                }
            }

            if (tipoMovimiento) {
                tipoMovimiento.addEventListener("change", toggleFields);
                toggleFields(); // Ejecutar una vez al cargar
            }

            if (form) {
                form.addEventListener("submit", function(event) {
                    event.preventDefault();
                    submitBtn.disabled = true;

                    const formData = new FormData(form);

                    fetch(form.action, {
                            method: "POST",
                            body: formData,
                            headers: {
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute("content"),
                                "Accept": "application/json"
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
                            submitBtn.disabled = false;
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
