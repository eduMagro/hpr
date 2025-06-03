<x-app-layout>
    <x-slot name="title">Planillas - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Lista de Planillas') }}

            <span class="mx-2">/</span>
            <a href="{{ route('paquetes.index') }}" class="text-blue-600">
                {{ __('Paquetes') }}
            </a>
            <span class="mx-2">/</span>
            <a href="{{ route('etiquetas.index') }}" class="text-blue-600">
                {{ __('Etiquetas') }}
            </a>
            <span class="mx-2">/</span>
            <a href="{{ route('elementos.index') }}" class="text-blue-600">
                {{ __('Elementos') }}
            </a>
        </h2>
    </x-slot>

    <div class="w-full px-6 py-4">

        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-4">
            <!-- Formulario de importación -->
            <form method="post" action="{{ route('planillas.import') }}" enctype="multipart/form-data"
                class="form-cargando flex items-center gap-x-2">
                @csrf
                <input type="file" name="file" id="file" class="form-control file:mr-2">

                <button type="submit" class="btn btn-primary btn-cargando flex items-center gap-x-2">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    <span class="texto">IMPORTAR</span>
                </button>
            </form>
        </div>
        @if (count($filtrosActivos))
            <div class="alert alert-info text-sm mt-2 mb-4 shadow-sm">
                <strong>Filtros aplicados:</strong> {!! implode(', ', $filtrosActivos) !!}
            </div>
        @endif

        <!-- TABLA DE PLANILLAS -->
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[1000px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white text-10">
                    <tr class="text-center text-xs uppercase">
                        <th class="p-2 border">{!! $ordenables['codigo'] !!}</th>
                        <th class="p-2 border">Codigo Cliente</th>
                        <th class="p-2 border">{!! $ordenables['cliente'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['cod_obra'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['nom_obra'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['seccion'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['descripcion'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['ensamblado'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['comentario'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['peso_fabricado'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['peso_total'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['estado'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['fecha_inicio'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['fecha_finalizacion'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['fecha_importacion'] ?? '' !!}</th>
                        <th class="p-2 border">{!! $ordenables['fecha_entrega'] !!}</th>
                        <th class="p-2 border">{!! $ordenables['name'] !!}</th>
                        <th class="p-2 border">Acciones</th>
                    </tr>

                    <tr class="text-center text-xs uppercase">
                        <form method="GET" action="{{ route('planillas.index') }}">
                            <th class="p-1 border">
                                <input type="text" name="codigo" value="{{ request('codigo') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="codigo_cliente" value="{{ request('codigo_cliente') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="cliente" value="{{ request('cliente') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="cod_obra" value="{{ request('cod_obra') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="nom_obra" value="{{ request('nom_obra') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <!-- Sección -->
                            <th class="p-1 border"> <input type="text" name="seccion"
                                    value="{{ request('seccion') }}" class="form-control form-control-sm" /></th>
                            <!-- Descripción -->
                            <th class="p-1 border">
                                <input type="text" name="descripcion" value="{{ request('descripcion') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border"> <input type="text" name="ensamblado"
                                    value="{{ request('ensamblado') }}" class="form-control form-control-sm" /></th>
                            <!-- Comentario -->
                            <th class="p-1 border"> <input type="text" name="comentario"
                                    value="{{ request('comentario') }}" class="form-control form-control-sm" /></th>
                            <th class="p-1 border"></th> <!-- Peso Fabricado -->
                            <th class="p-1 border"></th> <!-- Peso Total -->
                            <th class="p-1 border">
                                <select name="estado" class="form-control form-control-sm">
                                    <option value="">Todos</option>
                                    <option value="pendiente"
                                        {{ request('estado') == 'pendiente' ? 'selected' : '' }}>
                                        Pendiente</option>
                                    <option value="fabricando"
                                        {{ request('estado') == 'fabricando' ? 'selected' : '' }}>Fabricando</option>
                                    <option value="completada"
                                        {{ request('estado') == 'completada' ? 'selected' : '' }}>Completada</option>
                                    <option value="montaje" {{ request('estado') == 'montaje' ? 'selected' : '' }}>
                                        Montaje</option>
                                </select>
                            </th>
                            <th class="p-1 border">
                                <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="date" name="fecha_finalizacion"
                                    value="{{ request('fecha_finalizacion') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="date" name="fecha_importacion"
                                    value="{{ request('fecha_importacion') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="date" name="fecha_entrega" value="{{ request('fecha_entrega') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border">
                                <input type="text" name="name" value="{{ request('name') }}"
                                    class="form-control form-control-sm" />
                            </th>
                            <th class="p-1 border text-center">
                                <button type="submit" class="btn btn-sm btn-info px-2"><i
                                        class="fas fa-search"></i></button>
                                <a href="{{ route('planillas.index') }}" class="btn btn-sm btn-warning px-2"><i
                                        class="fas fa-undo"></i></a>
                            </th>
                        </form>
                    </tr>

                </thead>

                <tbody class="text-gray-700">
                    @forelse ($planillas as $planilla)
                        <tr tabindex="0" x-data="{
                            editando: false,
                            planilla: @js($planilla),
                            original: JSON.parse(JSON.stringify(@js($planilla)))
                        }"
                            @dblclick="if(!$event.target.closest('input')) {
                              if(!editando) {
                                editando = true;
                              } else {
                                planilla = JSON.parse(JSON.stringify(original));
                                editando = false;
                              }
                            }"
                            @keydown.enter.stop="guardarCambios(planilla); editando = false"
                            :class="{ 'bg-yellow-100': editando }"
                            class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer text-xs uppercase">

                            <!-- Código -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.codigo_limpio"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.codigo"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Código Cliente -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.cliente.codigo ?? 'N/A'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.cliente.codigo"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Cliente -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('clientes.index', ['id' => $planilla->cliente_id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $planilla->cliente->empresa ?? 'N/A' }}
                                    </a>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.cliente.empresa"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Código Obra -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.obra.cod_obra ?? 'N/A'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.obra.cod_obra"
                                    class="form-control form-control-sm">
                            </td>
                            <!-- Obra -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <a href="{{ route('clientes.show', ['cliente' => $planilla->cliente_id]) }}"
                                        class="text-blue-500 hover:underline">
                                        {{ $planilla->obra->obra ?? 'N/A' }}
                                    </a>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.obra.obra"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Sección -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.seccion ?? 'N/A'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.seccion"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Descripción -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.descripcion ?? 'Sin descripción'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.descripcion"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Ensamblado -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.ensamblado ?? 'Sin datos'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.ensamblado"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Comentario -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.comentario ?? ' '"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.comentario"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Peso Fabricado -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span
                                        x-text="new Intl.NumberFormat('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat(planilla.suma_peso_completados) || 0) + ' KG'"></span>

                                </template>
                                <input x-show="editando" type="text" x-model="planilla.suma_peso_completados"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Peso Total -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.peso_total_kg"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.peso_total_kg"
                                    class="form-control form-control-sm">
                            </td>

                            <!-- Estado -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.estado.toUpperCase()"></span>
                                </template>
                                <select x-show="editando" x-model="planilla.estado" class="form-select w-full">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="fabricando">Fabricando</option>
                                    <option value="completada">Completada</option>
                                </select>
                            </td>

                            <!-- Fecha Inicio -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.fecha_inicio"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.fecha_inicio"
                                    class="form-control form-control-sm" placeholder="DD/MM/YYYY HH:mm">
                            </td>

                            <!-- Fecha Finalización -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.fecha_finalizacion"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="planilla.fecha_finalizacion"
                                    class="form-control form-control-sm" placeholder="DD/MM/YYYY HH:mm">
                            </td>

                            <!-- Fecha Importación -->
                            <td class="p-2 text-center border">
                                <span x-text="new Date(planilla.created_at).toLocaleDateString()"></span>
                            </td>

                            <!-- Fecha Entrega -->
                            <td class="p-2 text-center border">
                                <template x-if="!editando">
                                    <span x-text="planilla.fecha_estimada_entrega"></span>
                                </template>

                                <input x-show="editando" type="text" x-model="planilla.fecha_estimada_entrega"
                                    class="form-control form-control-sm" placeholder="DD/MM/YYYY HH:mm">
                            </td>

                            <!-- Usuario -->
                            <td class="p-2 text-center border">
                                <span x-text="planilla.user?.name ?? 'Desconocido'"></span>
                            </td>

                            <!-- Acciones (solo se muestran las opciones de ver y eliminar) -->
                            <td class="p-2 text-center border">
                                <template x-if="editando">
                                    <button @click="guardarCambios(planilla); editando = false"
                                        class="bg-green-500 hover:bg-green-600 text-white text-xs px-2 py-1 rounded shadow">
                                        Guardar
                                    </button>
                                </template>
                                <a href="{{ route('planillas.show', $planilla->id) }}"
                                    class="text-green-500 hover:underline">Ver</a>
                                <br>
                                <x-boton-eliminar :action="route('planillas.destroy', $planilla->id)" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="text-center py-4 text-gray-500">No hay planillas disponibles.
                            </td>
                        </tr>
                    @endforelse
                </tbody>


            </table>
        </div>

        <div class="mt-4 flex justify-center">{{ $planillas->onEachSide(2)->links('vendor.pagination.bootstrap-5') }}
        </div>
        <script>
            function guardarCambios(planilla) {
                fetch(`{{ route('planillas.update', '') }}/${planilla.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(planilla)
                    })
                    .then(async response => {
                        const contentType = response.headers.get('content-type');
                        let data = {};

                        if (contentType && contentType.includes('application/json')) {
                            data = await response.json();
                        } else {
                            const text = await response.text();
                            throw new Error("El servidor devolvió una respuesta inesperada: " + text.slice(0,
                                100)); // corta para no saturar
                        }

                        if (response.ok && data.success) {
                            window.location.reload();
                        } else {
                            let errorMsg = data.message || "Ha ocurrido un error inesperado.";
                            if (data.errors) {
                                errorMsg = Object.values(data.errors).flat().join("<br>");
                            }

                            Swal.fire({
                                icon: "error",
                                title: "Error al actualizar",
                                html: errorMsg,
                                confirmButtonText: "OK",
                                showCancelButton: true,
                                cancelButtonText: "Reportar Error"
                            }).then((result) => {
                                if (result.dismiss === Swal.DismissReason.cancel) {
                                    notificarProgramador(errorMsg);
                                }
                            });
                        }
                    })
                    .catch(error => {
                        // Este catch ahora captura errores de red y errores de tipo (como HTML no válido)
                        Swal.fire({
                            icon: "error",
                            title: "Error de conexión",
                            text: error.message || "No se pudo actualizar la planilla. Inténtalo nuevamente.",
                            confirmButtonText: "OK"
                        });
                    });
            }
        </script>


</x-app-layout>
