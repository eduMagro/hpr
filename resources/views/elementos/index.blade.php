<x-app-layout>
    <x-slot name="title">Elementos - {{ config('app.name') }}</x-slot>

    <x-page-header
        title="Elementos de Producción"
        subtitle="Gestión de elementos y piezas fabricadas"
        icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>'
    />

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
        // Función global para actualizar campo (necesaria para llamadas onchange desde HTML)
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
                        err.responseJson = data;
                        throw err;
                    }
                    return data;
                }))
                .then(data => {
                    console.log(`Elemento #${id} actualizado: ${campo} = ${valor}`);
                    input.dataset.originalValue = valor;

                    if (data.swal) {
                        Swal.fire(data.swal);
                    }
                })
                .catch(error => {
                    console.error('Error completo:', error);

                    let mensaje = error.message || 'Error al guardar dato';

                    if (error.responseJson && error.responseJson.swal) {
                        Swal.fire(error.responseJson.swal);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: mensaje
                        });
                    }

                    if (input.dataset.originalValue) {
                        input.value = input.dataset.originalValue;
                    }
                });
        }

        // Inicialización de la página
        function initElementosPage() {
            // Prevenir doble inicialización
            if (document.body.dataset.elementosPageInit === 'true') return;

            console.log('🔍 Inicializando página de elementos...');

            // 1. Inicializar valores originales
            const selects = document.querySelectorAll('select[data-field]');
            selects.forEach(select => {
                select.dataset.originalValue = select.value;
            });

            // 2. Vincular modal de dibujo
            const modal = document.getElementById("modal-dibujo");
            const titulo = document.getElementById("modal-titulo");
            const canvas = document.getElementById("canvas-dibujo");
            const cerrar = document.getElementById("cerrar-modal");
            let timeoutCerrar = null;

            function abrirModal(ojo) {
                if (timeoutCerrar) {
                    clearTimeout(timeoutCerrar);
                    timeoutCerrar = null;
                }

                const id = ojo.dataset.id;
                const codigo = ojo.dataset.codigo;
                const dimensiones = ojo.dataset.dimensiones;
                const peso = ojo.dataset.peso;

                if (titulo) titulo.textContent = `${codigo}`;

                window.elementoData = {
                    id,
                    dimensiones,
                    peso
                };

                modal.classList.remove("hidden");
                // Forzar redibujado
                if (typeof window.dibujarFiguraElemento === 'function') {
                    window.dibujarFiguraElemento("canvas-dibujo", dimensiones, peso);
                }
            }

            function cerrarModal() {
                timeoutCerrar = setTimeout(() => {
                    modal.classList.add("hidden");
                }, 100);
            }

            function mantenerModalAbierto() {
                if (timeoutCerrar) {
                    clearTimeout(timeoutCerrar);
                    timeoutCerrar = null;
                }
            }

            document.querySelectorAll(".abrir-modal-dibujo").forEach(ojo => {
                // Clonar y reemplazar para eliminar listeners antiguos si existen
                const newOjo = ojo.cloneNode(true);
                ojo.replaceWith(newOjo);

                newOjo.addEventListener("mouseenter", () => abrirModal(newOjo));
                newOjo.addEventListener("mouseleave", cerrarModal);
                newOjo.addEventListener("click", e => e.preventDefault());
            });

            // Mantener el modal abierto cuando el cursor está sobre él
            if (modal) {
                // Clonar modal para limpiar listeners (cuidado porque tiene hijos interactivos como canvas)
                // En este caso, mejor solo volvemos a añadir si no tienen atributo data, 
                // pero ya estamos controlando con dataset.pageInit

                // NO clonamos el modal entero porque perderíamos referencias al canvas y botón cerrar internos si no las reasignamos.
                // Como tenemos proteção global pageInit, solo añadimos listeners.

                modal.removeEventListener("mouseenter", mantenerModalAbierto);
                modal.removeEventListener("mouseleave", cerrarModal);

                modal.addEventListener("mouseenter", mantenerModalAbierto);
                modal.addEventListener("mouseleave", cerrarModal);
            }

            if (cerrar) {
                const newCerrar = cerrar.cloneNode(true);
                cerrar.replaceWith(newCerrar);
                newCerrar.addEventListener("click", () => {
                    if (timeoutCerrar) {
                        clearTimeout(timeoutCerrar);
                        timeoutCerrar = null;
                    }
                    modal.classList.add("hidden");
                });
            }

            // Datos iniciales
            @if (isset($elemento))
                window.elementoData = @json($elemento);
            @else
                window.elementoData = null;
            @endif

            // Marcar como inicializado
            document.body.dataset.elementosPageInit = 'true';
        }

        // Registrar en el sistema global
        window.pageInitializers = window.pageInitializers || [];
        window.pageInitializers.push(initElementosPage);

        // Configurar listeners
        document.addEventListener('livewire:navigated', initElementosPage);
        document.addEventListener('DOMContentLoaded', initElementosPage);

        // Limpiar flag antes de navegar
        document.addEventListener('livewire:navigating', () => {
            document.body.dataset.elementosPageInit = 'false';
        });
    </script>

</x-app-layout>
