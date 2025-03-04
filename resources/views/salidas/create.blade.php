<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Gestión de Salidas de Camiones') }}
        </h2>
    </x-slot>

    <div class="container mx-auto p-6">
        <form action="{{ route('salidas.store') }}" method="POST" id="form-crear-salida">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- SECCIÓN: PLANILLAS COMPLETADAS -->
                <div class="bg-white shadow-md rounded-lg p-6 lg:col-span-2">
                    <h1 class="text-2xl font-bold mb-4 text-black">Planillas Completadas</h1>
                    <div class="space-y-4">
                        @foreach ($planillasCompletadas as $planilla)
                            <div class="bg-gray-100 p-4 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center">
                                        <!-- Checkbox para seleccionar todos los paquetes de esta planilla -->
                                        <input type="checkbox" class="check-planilla mr-2"
                                            data-planilla-id="{{ $planilla->id }}"
                                            id="check-planilla-{{ $planilla->id }}">
                                        <label for="check-planilla-{{ $planilla->id }}"
                                            class="font-semibold text-lg text-gray-800">
                                            {{ $planilla->codigo_limpio }}
                                        </label>
                                    </div>
                                    <span class="text-sm text-gray-600">{{ $planilla->peso_total_kg }} kg</span>
                                    <span class="text-sm text-gray-600">Fecha Entrega:
                                        {{ $planilla->fecha_estimada_entrega }}</span>
                                </div>
                                <p class="text-gray-700">Cliente: {{ $planilla->cliente }}</p>
                                <p class="text-gray-700">Obra: {{ $planilla->obra }}</p>
                                <p class="text-gray-700">Sección: {{ $planilla->seccion }}</p>
                                <p class="text-gray-700">Descripción: {{ $planilla->descripcion }}</p>

                                <div class="mt-4">
                                    <h3 class="font-semibold text-md text-gray-800">Paquetes Asociados:</h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">
                                        @foreach ($planilla->paquetes as $paquete)
                                            <div class="p-4 bg-white shadow rounded-lg flex items-center">
                                                <input type="checkbox" class="check-paquete mr-2"
                                                    data-planilla-id="{{ $planilla->id }}" name="paquete_ids[]"
                                                    value="{{ $paquete->id }}" id="paquete-{{ $paquete->id }}">
                                                <label for="paquete-{{ $paquete->id }}" class="text-gray-800">
                                                    Paquete #{{ $paquete->id }}
                                                </label>
                                                <span class="text-gray-600 ml-auto">Peso: {{ $paquete->peso }}
                                                    kg</span>
                                                <button onclick="mostrarDibujo({{ $paquete->id }})"
                                                    class="text-blue-500 hover:underline ml-2">
                                                    Ver
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- SECCIÓN: Empresas y Camiones (fijo) -->
                <div class="bg-white shadow-md rounded-lg p-6 sticky top-0 lg:col-span-1 max-h-screen overflow-y-auto">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Empresas y Camiones</h2>

                    <div class="space-y-4">
                        @foreach ($empresas as $empresa)
                            <div class="p-4 bg-gray-100 rounded-lg">
                                <h3 class="font-semibold text-lg text-gray-800">{{ $empresa->nombre }}</h3>
                                <ul class="text-gray-700">
                                    @foreach ($empresa->camiones as $camion)
                                        <li class="flex items-center">
                                            <input type="radio" name="camion" class="camion-checkbox mr-2"
                                                data-camion-id="{{ $camion->id }}" id="camion-{{ $camion->id }}">
                                            <label for="camion-{{ $camion->id }}">
                                                {{ $camion->modelo }} - {{ $camion->matricula }}
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                    <!-- Botón para crear salida -->
                    <div class="mt-6 text-center">
                        <button id="btn-crear-salida"
                            class="bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600" disabled>
                            Crear Salida
                        </button>
                    </div>
                    <!-- Input para el camión seleccionado -->
                    <input type="hidden" name="camion_id" id="camion_id">

                    <!-- Input para las planillas seleccionadas -->
                    <input type="hidden" name="planillas_ids" id="planillas_ids">
                </div>
            </div>


        </form>
    </div>

    <!-- Modal -->
    <div id="modal-dibujo" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex justify-center items-center p-4">
        <div
            class="bg-white p-4 sm:p-6 rounded-lg w-full sm:w-auto max-w-[95vw] max-h-[90vh] flex flex-col shadow-lg relative">
            <button id="cerrar-modal" class="absolute top-2 right-2 text-red-600 hover:bg-red-100">
                ✖
            </button>

            <h2 class="text-xl font-semibold mb-4 text-center">Elementos del paquete</h2>

            <div class="overflow-y-auto flex-1 min-h-0" style="max-height: 75vh;">
                <canvas id="canvas-dibujo" width="800" height="600" class="border max-w-full h-auto"></canvas>
            </div>
        </div>
    </div>

    <script>
        window.paquetes = @json($paquetes);

        // Checkbox de cada planilla para seleccionar TODOS sus paquetes
        document.querySelectorAll('.check-planilla').forEach(planillaCheckbox => {
            planillaCheckbox.addEventListener('change', function() {
                let planillaId = this.getAttribute('data-planilla-id');
                let isChecked = this.checked;

                document.querySelectorAll(`.check-paquete[data-planilla-id="${planillaId}"]`).forEach(
                    paqueteCheckbox => {
                        paqueteCheckbox.checked = isChecked;
                    });
            });
        });

        // Solo permitir seleccionar un camión
        document.querySelectorAll('.camion-checkbox').forEach(camionCheckbox => {
            camionCheckbox.addEventListener('change', function() {
                // Deshabilitar el botón de crear salida si no se ha seleccionado un camión
                const btnCrearSalida = document.getElementById('btn-crear-salida');
                btnCrearSalida.disabled = !document.querySelector('.camion-checkbox:checked');

                // Al seleccionar un camión, actualizar el input oculto con el camion_id
                if (this.checked) {
                    document.getElementById('camion_id').value = this.getAttribute('data-camion-id');
                }
            });
        });

        // Recoger los paquetes seleccionados y las planillas
        document.getElementById('btn-crear-salida').addEventListener('click', function(event) {
            event.preventDefault();

            // Recoger los ids de las planillas seleccionadas
            let selectedPlanillas = [];
            document.querySelectorAll('.check-planilla:checked').forEach(checkbox => {
                selectedPlanillas.push(checkbox.getAttribute('data-planilla-id'));
            });

            // Asignar los ids de las planillas seleccionadas al input oculto
            document.getElementById('planillas_ids').value = selectedPlanillas.join(',');

            // Enviar el formulario
            document.getElementById('form-crear-salida').submit();
        });
    </script>

    <script src="{{ asset('js/paquetesJs/figurasPaquete.js') }}" defer></script>
</x-app-layout>
