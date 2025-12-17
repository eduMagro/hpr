<x-app-layout>
    <x-slot name="title">Planillas - {{ config('app.name') }}</x-slot>
    @if (auth()->user()->rol !== 'operario')
        @php
            $menu = \App\Services\MenuService::getContextMenu('movimientos');
        @endphp
        <x-navigation.context-menu :items="$menu['items']" :colorBase="$menu['config']['colorBase']" :style="$menu['config']['style']" :mobileLabel="$menu['config']['mobileLabel']"
            checkRole="no-operario" />
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
                        label="Código de Materia Prima o Paquete" placeholder="Escanear QR" :value="old('codigo_general', $codigoMateriaPrima ?? '')"
                        inputmode="none" autocomplete="off" />

                    <div id="qr_escaneados"></div>
                    <input type="hidden" name="lista_qrs" id="lista_qrs">


                    {{-- Ubicación destino --}}
                    <x-tabla.input-movil name="ubicacion_destino" placeholder="Escanear ubicación"
                        value="{{ old('ubicacion_destino') }}" />

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
        function initMovimientosCreatePage() {
            // Prevenir doble inicialización
            if (document.body.dataset.movimientosCreatePageInit === 'true') return;

            console.log('🔍 Inicializando página de crear movimiento...');

            const form = document.getElementById("form-movimiento");
            const submitBtn = document.getElementById("submit-btn");

            const tipoMovimiento = document.getElementById("tipo_movimiento");
            const productoSection = document.getElementById("producto-section");
            const paqueteSection = document.getElementById("paquete-section");
            const maquinaSection = document.getElementById("maquina-section");

            function toggleFields() {
                if (!tipoMovimiento) return;
                const tipo = tipoMovimiento.value;

                if (tipo === "producto") {
                    if (productoSection) productoSection.style.display = "block";
                    if (paqueteSection) paqueteSection.style.display = "none";
                    if (maquinaSection) maquinaSection.style.display = "block";
                } else if (tipo === "paquete") {
                    if (productoSection) productoSection.style.display = "none";
                    if (paqueteSection) paqueteSection.style.display = "block";
                    if (maquinaSection) maquinaSection.style.display = "none";
                }
            }

            if (tipoMovimiento) {
                // Remover listener previo clonando si fuera necesario, pero data-init protege
                tipoMovimiento.addEventListener("change", toggleFields);
                toggleFields();
            }

            if (form) {
                // Clonar form para limpieza de listeners es más seguro aquí
                // O mejor aún, usamos la protección de init para no tener que clonar

                form.addEventListener("submit", function(event) {
                    event.preventDefault();
                    if (submitBtn) submitBtn.disabled = true;

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
                        .then(async res => {
                            const data = await res.json();

                            if (!res.ok) {
                                // ⚠️ Errores de validación
                                if (data.errors) {
                                    let mensajes = Object.values(data.errors)
                                        .flat()
                                        .map(m => `<li>${m}</li>`)
                                        .join("");

                                    Swal.fire({
                                        icon: "error",
                                        title: "Errores de validación",
                                        html: `<ul style="text-align:left">${mensajes}</ul>`,
                                        confirmButtonText: "Aceptar"
                                    });
                                } else {
                                    Swal.fire({
                                        icon: "error",
                                        title: "Error",
                                        text: data.message ||
                                            "Ocurrió un error inesperado.",
                                        confirmButtonText: "Aceptar"
                                    });
                                }
                                if (submitBtn) submitBtn.disabled = false;
                                return;
                            }

                            // ✅ Éxito
                            if (data.success) {
                                Swal.fire({
                                    title: "¡Éxito!",
                                    text: data.message,
                                    icon: "success",
                                    confirmButtonText: "Aceptar"
                                }).then(() => {
                                    window.location.href = "{{ route('movimientos.index') }}";
                                });
                            }
                            if (submitBtn) submitBtn.disabled = false;
                        })
                        .catch(error => {
                            console.error("Error en fetch:", error);
                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: "Hubo un problema con la solicitud. Inténtelo otra vez.",
                                confirmButtonText: "Aceptar"
                            });
                            if (submitBtn) submitBtn.disabled = false;
                        });

                });
            }

            // Marcar como inicializado
            document.body.dataset.movimientosCreatePageInit = 'true';
        }

        // Registrar en el sistema global
        window.pageInitializers = window.pageInitializers || [];
        window.pageInitializers.push(initMovimientosCreatePage);

        // Configurar listeners
        document.addEventListener('livewire:navigated', initMovimientosCreatePage);
        document.addEventListener('DOMContentLoaded', initMovimientosCreatePage);

        // Limpiar flag antes de navegar
        document.addEventListener('livewire:navigating', () => {
            document.body.dataset.movimientosCreatePageInit = 'false';
        });
    </script>

    <script src="{{ asset('js/movimientos/anadir_qr_lista.js') }}"></script>
</x-app-layout>
