<x-app-layout>
    <x-slot name="title">Elementos - {{ config('app.name') }}</x-slot>
    <x-menu.planillas />
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

        <!-- Tabla de elementos con scroll horizontal -->
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[1000px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white text-10">
                    <tr class="text-center text-xs uppercase">
                        <th class="p-2 border">{!! $ordenables['id'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['codigo'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['codigo_planilla'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['etiqueta'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['subetiqueta'] !!}</th>
                        <th class="p-2 border">Dimensiones</th>
                        <th class="p-2 border">{!! $ordenables['maquina'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['maquina_2'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['maquina3'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['producto1'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['producto2'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['producto3'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['figura'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['peso'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['diametro'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['longitud'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['estado'] !!}</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>

                    <tr class="text-center text-xs uppercase">
                        <form method="GET" action="{{ route('elementos.index') }}">
                            @foreach (['id', 'codigo', 'codigo_planilla', 'etiqueta', 'subetiqueta', 'dimensiones', 'maquina', 'maquina_2', 'maquina3', 'producto1', 'producto2', 'producto3', 'figura', 'peso', 'diametro', 'longitud'] as $campo)
                                <th class="p-1 border">
                                    <x-tabla.input name="{{ $campo }}" value="{{ request($campo) }}" />
                                </th>
                            @endforeach

                            <th class="p-1 border">
                                <x-tabla.select name="estado" :options="[
                                    'pendiente' => 'Pendiente',
                                    'fabricando' => 'Fabricando',
                                    'fabricado' => 'Fabricado',
                                    'montaje' => 'Montaje',
                                ]" :selected="request('estado')" empty="Todos" />
                            </th>

                            <x-tabla.botones-filtro ruta="elementos.index" />
                        </form>
                    </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">
                    @forelse ($elementos as $elemento)
                        <tr tabindex="0" x-data="{
                            editando: false,
                            seleccionada: false,
                            elemento: @js($elemento),
                            original: JSON.parse(JSON.stringify(@js($elemento)))
                        }"
                            @dblclick="if(!$event.target.closest('input')) {
        if(!editando) {
            editando = true;
        } else {
            elemento = JSON.parse(JSON.stringify(original));
            editando = false;
        }
    }"
                            @keydown.enter.stop="guardarCambios(elemento); editando = false"
                            @click="seleccionada = !seleccionada"
                            :class="{
                                'bg-yellow-100': editando,
                                'bg-blue-100': seleccionada,
                                'hover:bg-blue-200': !seleccionada && !editando
                            }"
                            class="border-b odd:bg-gray-100 even:bg-gray-50 cursor-pointer text-xs uppercase transition-colors">

                            <!-- ID -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.id"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.id"
                                    class="form-control form-control-sm">
                            </td>
                            <!-- CODIGO -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.codigo"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.codigo"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- PLANILLA -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('planillas.index', ['planilla_id' => $elemento->planilla->id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->planilla->codigo_limpio }}
                                    </a>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.planilla.codigo_limpio"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- ETIQUETA -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('etiquetas.index', ['id' => $elemento->etiquetaRelacion?->id ?? '#']) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->etiquetaRelacion?->id ?? 'N/A' }}
                                    </a>

                                </template>
                                <input x-show="editando" type="text" x-model="elemento.etiquetaRelacion.id"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- SUBETIQUETA -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('etiquetas.index', ['id' => $elemento->etiquetaRelacion?->id ?? '#']) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->subetiqueta ?? 'N/A' }}
                                    </a>

                                </template>
                                <input x-show="editando" type="text" x-model="elemento.subetiquetas"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- DIMENSIONES -->
                            <td class="px-1 py-3 text-center border">

                                <span> {{ $elemento->dimensiones ?? 'N/A' }}</span>

                            </td>

                            <!-- MAQUINA 1 -->
                            <td class="px-1 py-3 text-center border">
                                <select class="text-xs border rounded px-1 py-0.5" data-id="{{ $elemento->id }}"
                                    data-field="maquina_id" onchange="actualizarCampoElemento(this)">
                                    <option value="">N/A</option>
                                    @foreach ($maquinas->whereIn('tipo', ['cortadora_dobladora', 'estribadora', 'cortadora manual']) as $maquina)
                                        <option value="{{ $maquina->id }}" @selected($elemento->maquina_id === $maquina->id)>
                                            {{ $maquina->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <!-- MAQUINA 2 -->
                            <td class="px-1 py-3 text-center border">
                                <select class="text-xs border rounded px-1 py-0.5" data-id="{{ $elemento->id }}"
                                    data-field="maquina_id_2" onchange="actualizarCampoElemento(this)">
                                    <option value="">N/A</option>
                                    @foreach ($maquinas->whereIn('tipo', ['cortadora_dobladora', 'estribadora', 'cortadora_manual', 'dobladora_manual', 'soldadora']) as $maquina)
                                        <option value="{{ $maquina->id }}" @selected($elemento->maquina_id_2 === $maquina->id)>
                                            {{ $maquina->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <!-- MAQUINA 3 -->
                            <td class="px-1 py-3 text-center border">
                                <select class="text-xs border rounded px-1 py-0.5" data-id="{{ $elemento->id }}"
                                    data-field="maquina_id_3" onchange="actualizarCampoElemento(this)">
                                    <option value="">N/A</option>
                                    @foreach ($maquinas->whereIn('tipo', ['soldadora', 'ensambladora']) as $maquina)
                                        <option value="{{ $maquina->id }}" @selected($elemento->maquina_id_3 === $maquina->id)>
                                            {{ $maquina->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <!-- PRODUCTO 1 -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('productos.index', ['id' => $elemento->producto_id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->producto_id ?? 'N/A' }}
                                    </a>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.producto_id"
                                    class="form-control form-control-sm">
                            </td>
                            <!-- PRODUCTO 2 -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('productos.index', ['id' => $elemento->producto_id_2]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->producto_id_2 ?? 'N/A' }}
                                    </a>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.producto_id_2"
                                    class="form-control form-control-sm">
                            </td>
                            <!-- PRODUCTO 3 -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('productos.index', ['id' => $elemento->producto_id_3]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $elemento->producto_id_3 ?? 'N/A' }}
                                    </a>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.producto_id_3"
                                    class="form-control form-control-sm">
                            </td>
                            <!-- FIGURA -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.figura"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="elemento.figura"
                                    class="form-control form-control-sm">
                            </td>
                            <!-- PESO_KG -->
                            <td class="px-1 py-3 text-center border">
                                <!-- Muestra el peso formateado en modo vista -->
                                <template x-if="!editando">
                                    <span x-text="elemento.peso_kg"></span>
                                </template>
                                <!-- Edita el valor original -->
                                <input x-show="editando" type="number" x-model="elemento.peso"
                                    class="form-control form-control-sm">

                            </td>
                            <!-- DIAMETRO_MM -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.diametro_mm"></span>
                                </template>
                                <input x-show="editando" type="number" x-model="elemento.diametro"
                                    class="form-control form-control-sm">
                            </td>
                            <!-- LONGITUD_M -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.longitud_m"></span>
                                </template>
                                <input x-show="editando" type="number" x-model="elemento.longitud"
                                    class="form-control form-control-sm">
                            </td>
                            <!-- ESTADO -->
                            <td class="px-1 py-3 text-center border">
                                <template x-if="!editando">
                                    <span x-text="elemento.estado"></span>
                                </template>
                                <select x-show="editando" x-model="elemento.estado" class="form-select w-full">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="fabricando">Fabricando</option>
                                    <option value="fabricado">Fabricado</option>
                                </select>
                            </td>
                            <!-- Botones -->

                            <td class="px-1 py-2 border text-xs font-bold">
                                <div class="flex items-center space-x-2 justify-center">
                                    <!-- Mostrar solo en modo edición -->
                                    <x-tabla.boton-guardar x-show="editando"
                                        @click="guardarCambios(elemento); editando = false" />
                                    <x-tabla.boton-cancelar-edicion @click="editando = false" x-show="editando" />

                                    <!-- Mostrar solo cuando NO está en modo edición -->
                                    <template x-if="!editando">
                                        <div class="flex items-center space-x-2">
                                            <x-tabla.boton-editar @click="editando = true" x-show="!editando" />
                                            <a href="#"
                                                class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center abrir-modal-dibujo"
                                                data-id="{{ $elemento->id }}" data-codigo="{{ $elemento->codigo }}"
                                                data-dimensiones="{{ $elemento->dimensiones }}"
                                                data-peso="{{ $elemento->peso_kg }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7s-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>

                                            <x-tabla.boton-eliminar :action="route('elementos.destroy', $elemento->id)" />

                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="25" class="text-center py-4 text-gray-500">No hay elementos registrados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-gradient-to-r from-blue-50 to-blue-100 border-t border-blue-300">
                        <td colspan="100%" class="px-6 py-3" style="padding: 0" colspan="999">
                            <div class="flex justify-end items-center gap-4 px-6 py-3 text-sm text-gray-700">
                                <span class="font-semibold">Total peso filtrado:</span>
                                <span class="text-base font-bold text-blue-800">
                                    {{ number_format($totalPesoFiltrado, 2, ',', '.') }} kg
                                </span>
                            </div>
                        </td>
                    </tr>
                </tfoot>

            </table>
        </div>

        <x-tabla.paginacion :paginador="$elementos" />
        <!-- Modal -->
        <div id="modal-dibujo" class="hidden fixed inset-0 flex justify-center items-center pointer-events-none">
            <div
                class="bg-white rounded-lg p-5 w-3/4 max-w-lg relative pointer-events-auto shadow-lg border border-gray-300">
                <button id="cerrar-modal" class="absolute top-2 right-2 text-red-600 hover:bg-red-100">✖</button>
                <h2 class="text-lg font-semibold mb-3" id="modal-titulo">Elemento</h2>
                <canvas id="canvas-dibujo" class="border border-gray-300 w-full h-[300px]"></canvas>
            </div>
        </div>

    </div>

    <script src="{{ asset('js/elementosJs/guardarCambios.js') }}" defer></script>
    <script src="{{ asset('js/elementosJs/figuraElemento.js') }}" defer></script>
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

            function abrirModal(ojo) {
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
                modal.classList.add("hidden");
            }

            document.querySelectorAll(".abrir-modal-dibujo").forEach(ojo => {
                ojo.addEventListener("mouseenter", () => abrirModal(ojo));
                ojo.addEventListener("mouseleave", cerrarModal);
                ojo.addEventListener("click", e => e.preventDefault());
            });

            if (cerrar) cerrar.addEventListener("click", cerrarModal);
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
