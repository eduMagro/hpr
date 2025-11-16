<x-app-layout>
    <x-slot name="title">Elementos - {{ config('app.name') }}</x-slot>
    @php
        $filtrosActivos = [];

        if (request('buscar')) {
            $filtrosActivos[] = 'contiene <strong>“' . request('buscar') . '”</strong>';
        }

        if (request('id')) {
            $filtrosActivos[] = 'ID: <strong>' . request('id') . '</strong>';
        }

        if (request('codigo')) {
            $filtrosActivos[] = 'Código de elemento: <strong>' . request('codigo') . '</strong>';
        }
        if (request('codigo_planilla')) {
            $filtrosActivos[] = 'Código de planilla: <strong>' . request('codigo_planilla') . '</strong>';
        }

        if (request('usuario1')) {
            $filtrosActivos[] = 'Operario 1: <strong>' . request('usuario1') . '</strong>';
        }

        if (request('usuario2')) {
            $filtrosActivos[] = 'Operario 2: <strong>' . request('usuario2') . '</strong>';
        }

        if (request('etiqueta')) {
            $filtrosActivos[] = 'Etiqueta ID: <strong>' . request('etiqueta') . '</strong>';
        }

        if (request('subetiqueta')) {
            $filtrosActivos[] = 'Subetiqueta: <strong>' . request('subetiqueta') . '</strong>';
        }

        if (request('maquina')) {
            $filtrosActivos[] = 'Máquina 1: <strong>' . request('maquina') . '</strong>';
        }

        if (request('maquina_2')) {
            $filtrosActivos[] = 'Máquina 2: <strong>' . request('maquina_2') . '</strong>';
        }

        if (request('maquina3')) {
            $filtrosActivos[] = 'Máquina 3: <strong>' . request('maquina3') . '</strong>';
        }

        if (request('producto1')) {
            $filtrosActivos[] = 'Materia Prima 1: <strong>' . request('producto1') . '</strong>';
        }

        if (request('producto2')) {
            $filtrosActivos[] = 'Materia Prima 2: <strong>' . request('producto2') . '</strong>';
        }

        if (request('producto3')) {
            $filtrosActivos[] = 'Materia Prima 3: <strong>' . request('producto3') . '</strong>';
        }

        if (request('figura')) {
            $filtrosActivos[] = 'Figura: <strong>' . request('figura') . '</strong>';
        }

        if (request('estado')) {
            $estados = [
                'pendiente' => 'Pendiente',
                'fabricando' => 'Fabricando',
                'fabricado' => 'Fabricado',
                'montaje' => 'En Montaje',
            ];
            $filtrosActivos[] = 'Estado: <strong>' . ($estados[request('estado')] ?? request('estado')) . '</strong>';
        }

        if (request('fecha_inicio')) {
            $filtrosActivos[] = 'Desde: <strong>' . request('fecha_inicio') . '</strong>';
        }

        if (request('fecha_finalizacion')) {
            $filtrosActivos[] = 'Hasta: <strong>' . request('fecha_finalizacion') . '</strong>';
        }

        if (request('sort_by')) {
            $sorts = [
                'created_at' => 'Fecha de creación',
                'id' => 'ID',
                'figura' => 'Figura',
                'subetiqueta' => 'Subetiqueta',
            ];
            $orden = request('order') == 'desc' ? 'descendente' : 'ascendente';
            $filtrosActivos[] =
                'Ordenado por <strong>' .
                ($sorts[request('sort_by')] ?? request('sort_by')) .
                "</strong> en orden <strong>$orden</strong>";
        }
    @endphp
    <div class="w-full p-4 sm:p-2">
        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

        <!-- Banner de revisión de planilla -->
        @if ($planilla && !$planilla->revisada)
            <div class="mb-4 bg-red-100 border-l-4 border-red-500 p-4 rounded-r-lg shadow">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">⚠️</span>
                        <div>
                            <h3 class="text-lg font-bold text-red-800">
                                Planilla {{ $planilla->codigo }} SIN REVISAR
                            </h3>
                            <p class="text-sm text-red-700">
                                Esta planilla aparece en <strong>GRIS</strong> en el calendario. Revisa las máquinas
                                asignadas y marca como revisada cuando esté correcta.
                            </p>
                        </div>
                    </div>
                    <form action="{{ route('planillas.marcarRevisada', $planilla->id) }}" method="POST"
                        onsubmit="return confirm('¿Marcar esta planilla como revisada?\n\nAparecerá en color normal en el calendario de producción.');">
                        @csrf
                        <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded transition-colors whitespace-nowrap">
                            ✅ Marcar como revisada
                        </button>
                    </form>
                </div>
            </div>
        @endif

        @if ($planilla && $planilla->revisada)
            <div class="mb-4 bg-green-100 border-l-4 border-green-500 p-4 rounded-r-lg shadow">
                <div class="flex items-center gap-3">
                    <span class="text-3xl">✅</span>
                    <div>
                        <h3 class="text-lg font-bold text-green-800">
                            Planilla {{ $planilla->codigo }} REVISADA
                        </h3>
                        <p class="text-sm text-green-700">
                            Revisada por <strong>{{ $planilla->revisor->name ?? 'N/A' }}</strong>
                            el {{ $planilla->revisada_at?->format('d/m/Y H:i') ?? 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Tabla de elementos Livewire -->
        @livewire('elementos-table')

        <!-- Modal -->
        <div id="modal-dibujo" class="hidden fixed inset-0 flex justify-end items-center pr-96 pointer-events-none">
            <div
                class="bg-white rounded-lg p-5 w-3/4 max-w-lg relative pointer-events-auto shadow-lg border border-gray-300">
                <button id="cerrar-modal" class="absolute top-2 right-2 text-red-600 hover:bg-red-100">✖</button>
                <h2 class="text-lg font-semibold mb-3" id="modal-titulo">Elemento</h2>
                <canvas id="canvas-dibujo" class="border border-gray-300 w-full h-[300px]"></canvas>
            </div>
        </div>

    </div>

    <!-- Vite: elementos-bundle -->
    @vite(['resources/js/elementosJs/elementos-bundle.js'])
    <!-- <script src="{{ asset('js/elementosJs/guardarCambios.js') }}" defer></script>
    <script src="{{ asset('js/elementosJs/figuraElemento.js') }}" defer></script> -->
    <script>
        function actualizarCampoElemento(input) {
            const id = input.dataset.id;
            const campo = input.dataset.field;
            const valor = input.value;

            // Guardar el valor original para poder revertir si hay error
            const valorOriginal = input.dataset.originalValue || input.defaultValue || '';
            if (!input.dataset.originalValue) {
                input.dataset.originalValue = valorOriginal;
            }

            console.log(`Actualizando elemento ${id}, campo ${campo}, valor: "${valor}"`);

            fetch(`/elementos/${id}/actualizar-campo`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        campo: campo,
                        valor: valor
                    })
                })
                .then(res => res.json().then(data => {
                    if (!res.ok) {
                        const err = new Error(data.error || 'Error al guardar');
                        err.responseJson = data; // 👉 guardamos la respuesta completa
                        throw err;
                    }
                    return data;
                }))
                .then(data => {
                    console.log(`Elemento #${id} actualizado: ${campo} = ${valor}`);
                    input.dataset.originalValue = valor;

                    if (data.swal) {
                        Swal.fire(data.swal); // 👈 dispara el swal de éxito si lo enviaste
                    }
                })
                .catch(error => {
                    console.error('Error completo:', error);

                    // intenta leer la última respuesta json guardada en el error
                    let mensaje = error.message || 'Error al guardar dato';

                    // si en la respuesta vino un swal, úsalo directamente
                    if (error.responseJson && error.responseJson.swal) {
                        Swal.fire(error.responseJson.swal);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: mensaje
                        });
                    }

                    // revertir al valor original
                    if (input.dataset.originalValue) {
                        input.value = input.dataset.originalValue;
                    }
                });
        }

        // Inicializar valores originales al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const selects = document.querySelectorAll('select[data-field]');
            selects.forEach(select => {
                select.dataset.originalValue = select.value;
            });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const modal = document.getElementById("modal-dibujo");
            const titulo = document.getElementById("modal-titulo");
            const canvas = document.getElementById("canvas-dibujo");
            const cerrar = document.getElementById("cerrar-modal");

            let timeoutCerrar = null;

            function abrirModal(ojo) {
                // Cancelar cualquier cierre pendiente
                if (timeoutCerrar) {
                    clearTimeout(timeoutCerrar);
                    timeoutCerrar = null;
                }

                const id = ojo.dataset.id;
                const codigo = ojo.dataset.codigo;
                const dimensiones = ojo.dataset.dimensiones;
                const peso = ojo.dataset.peso;

                if (titulo) titulo.textContent = `${codigo}`;

                // Actualizamos datos
                window.elementoData = {
                    id,
                    dimensiones,
                    peso
                };

                modal.classList.remove("hidden");
                // ⚠️ Forzar redibujado
                if (typeof window.dibujarFiguraElemento === 'function') {
                    window.dibujarFiguraElemento("canvas-dibujo", dimensiones, peso);
                }
            }

            function cerrarModal() {
                // Retrasar el cierre para evitar parpadeos
                timeoutCerrar = setTimeout(() => {
                    modal.classList.add("hidden");
                }, 100);
            }

            function mantenerModalAbierto() {
                // Cancelar el cierre si el cursor está sobre el modal
                if (timeoutCerrar) {
                    clearTimeout(timeoutCerrar);
                    timeoutCerrar = null;
                }
            }

            document.querySelectorAll(".abrir-modal-dibujo").forEach(ojo => {
                ojo.addEventListener("mouseenter", () => abrirModal(ojo));
                ojo.addEventListener("mouseleave", cerrarModal);
                ojo.addEventListener("click", e => e.preventDefault());
            });

            // Mantener el modal abierto cuando el cursor está sobre él
            if (modal) {
                modal.addEventListener("mouseenter", mantenerModalAbierto);
                modal.addEventListener("mouseleave", cerrarModal);
            }

            if (cerrar) {
                cerrar.addEventListener("click", () => {
                    if (timeoutCerrar) {
                        clearTimeout(timeoutCerrar);
                        timeoutCerrar = null;
                    }
                    modal.classList.add("hidden");
                });
            }
        });
    </script>
    <script>
        @if (isset($elemento))
            window.elementoData = @json($elemento);
        @else
            window.elementoData = null;
        @endif
    </script>
</x-app-layout>
