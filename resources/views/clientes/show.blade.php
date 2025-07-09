<x-app-layout>
    <x-slot name="title">Detalles de Cliente - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
            <a href="{{ route('clientes.index') }}" class="text-blue-600 hover:underline">
                {{ __('Clientes') }}
            </a>
            <span class="mx-2">/</span>
            {{ __('Cliente ') }} {{ $cliente->empresa }}
        </h2>
    </x-slot>

    <div x-data="{ modalObra: false }" class="container mx-auto px-4 py-6">

        <!-- Información del Cliente -->
        <div class="bg-white shadow-lg rounded-lg p-4 mb-6">
            <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 mb-4">Información del Cliente</h3>
            <div class="grid grid-cols-2 gap-4 text-sm text-gray-700">
                <div><span class="font-semibold">Empresa:</span> {{ $cliente->empresa }}</div>
                <div><span class="font-semibold">Teléfono:</span> {{ $cliente->contacto1_telefono }}</div>
                <div><span class="font-semibold">Email:</span> {{ $cliente->contacto1_email }}</div>
                <div><span class="font-semibold">Dirección:</span> {{ $cliente->direccion }}</div>
            </div>
        </div>

        <!-- Sección de Obras -->
        <div class="bg-white shadow-lg rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-700">Obras del Cliente</h3>
                <button @click="modalObra = true" class="btn btn-primary">➕ Nueva Obra</button>
            </div>

            @if ($cliente->obras->isEmpty())
                <p class="text-gray-500">No hay obras registradas para este cliente.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full border border-gray-300 rounded-lg text-sm">
                        <thead class="bg-blue-500 text-white">
                            <tr class="text-left uppercase">
                                <th class="py-3 px-2 border text-center">ID</th>
                                <th class="px-2 py-3 text-center border">Nombre</th>
                                <th class="px-2 py-3 text-center border">Código</th>
                                <th class="px-2 py-3 text-center border">Ciudad</th>
                                <th class="px-2 py-3 text-center border">Dirección</th>
                                <th class="px-2 py-3 text-center border">Latitud</th>
                                <th class="px-2 py-3 text-center border">Longitud</th>
                                <th class="px-2 py-3 text-center border">Radio</th>
                                <th class="px-2 py-3 text-center border">Fecha Inicio</th>
                                <th class="px-2 py-3 text-center border">Peso Entregado</th>
                                <th class="px-2 py-3 text-center border">Estado</th>
                                <th class="px-2 py-3 text-center border">Tipo</th>
                                <th class="px-2 py-3 text-center border">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700">
                            @foreach ($cliente->obras as $obra)
                                <tr tabindex="0" x-data="{
                                    editando: false,
                                    obra: @js($obra),
                                    original: JSON.parse(JSON.stringify(@js($obra)))
                                }"
                                    @dblclick="if(!$event.target.closest('input')) {
                                  if(!editando) {
                                    editando = true;
                                  } else {
                                    obra = JSON.parse(JSON.stringify(original));
                                    editando = false;
                                  }
                                }"
                                    @keydown.enter.stop="guardarCambios(obra); editando = false"
                                    :class="{ 'bg-yellow-100': editando }"
                                    class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer text-xs uppercase">

                                    <!-- ID -->
                                    <td class="p-2 text-center border">
                                        <template x-if="!editando">
                                            <span x-text="obra.id"></span>
                                        </template>
                                        <input x-show="editando" type="text" x-model="obra.id"
                                            class="form-input w-full">
                                    </td>
                                    <!-- Nombre -->
                                    <td class="p-2 text-center border">
                                        <template x-if="!editando">
                                            <span x-text="obra.obra"></span>
                                        </template>
                                        <input x-show="editando" type="text" x-model="obra.obra"
                                            class="form-input w-full">
                                    </td>
                                    <!-- Código -->
                                    <td class="p-2 text-center border">
                                        <template x-if="!editando">
                                            <span x-text="obra.cod_obra"></span>
                                        </template>
                                        <input x-show="editando" type="text" x-model="obra.cod_obra"
                                            class="form-input w-full">
                                    </td>
                                    <!-- Ciudad -->
                                    <td class="p-2 text-center border">
                                        <template x-if="!editando">
                                            <span x-text="obra.ciudad"></span>
                                        </template>
                                        <input x-show="editando" type="text" x-model="obra.ciudad"
                                            class="form-input w-full">
                                    </td>
                                    <!-- Dirección -->
                                    <td class="p-2 text-center border">
                                        <template x-if="!editando">
                                            <span x-text="obra.direccion"></span>
                                        </template>
                                        <input x-show="editando" type="text" x-model="obra.direccion"
                                            class="form-input w-full">
                                    </td>
                                    <!-- Latitud -->
                                    <td class="p-2 text-center border">
                                        <template x-if="!editando">
                                            <span x-text="obra.latitud"></span>
                                        </template>
                                        <input x-show="editando" type="text" x-model="obra.latitud"
                                            class="form-input w-full">
                                    </td>
                                    <!-- Longitud -->
                                    <td class="p-2 text-center border">
                                        <template x-if="!editando">
                                            <span x-text="obra.longitud"></span>
                                        </template>
                                        <input x-show="editando" type="text" x-model="obra.longitud"
                                            class="form-input w-full">
                                    </td>
                                    <!-- Radio -->
                                    <td class="p-2 text-center border">
                                        <template x-if="!editando">
                                            <span x-text="obra.distancia"></span>
                                        </template>
                                        <input x-show="editando" type="text" x-model="obra.distancia"
                                            class="form-input w-full">
                                    </td>
                                    <!-- Fecha Inicio -->
                                    <td class="p-2 text-center border">
                                        <template x-if="!editando">
                                            <span x-text="obra.fecha_inicio"></span>
                                        </template>
                                        <input x-show="editando" type="text" x-model="obra.fecha_inicio"
                                            class="form-input w-full">
                                    </td>
                                    <!-- Peso Entregado -->
                                    <td class="p-2 text-center border">
                                        <template x-if="!editando">
                                            <span x-text="obra.peso_entregado"></span>
                                        </template>
                                        <input x-show="editando" type="text" x-model="obra.peso_entregado"
                                            class="form-input w-full">
                                    </td>
                                    <!-- Estado -->
                                    <td class="p-2 text-center border">
                                        <template x-if="!editando">
                                            <span x-text="obra.estado"
                                                :class="{
                                                    'text-green-500': obra.estado === 'activa',
                                                    'text-blue-500': obra.estado === 'completada',
                                                    'text-red-500': obra.estado !== 'activa' && obra
                                                        .estado !== 'completada'
                                                }"></span>
                                        </template>

                                        <input x-show="editando" type="text" x-model="obra.estado"
                                            class="form-input w-full">
                                    </td>
                                    <!-- Tipo -->
                                    <td class="p-2 text-center border">
                                        <template x-if="!editando">
                                            <span x-text="obra.tipo"
                                                :class="{
                                                    'text-indigo-600': obra.tipo === 'montaje',
                                                    'text-teal-600': obra.tipo === 'suministro'
                                                }"></span>
                                        </template>

                                        <select x-show="editando" x-model="obra.tipo"
                                            class="form-select w-full text-xs">
                                            <option value="">Selecciona tipo</option>
                                            <option value="montaje">Montaje</option>
                                            <option value="suministro">Suministro</option>
                                        </select>
                                    </td>

                                    <td class="px-2 py-2 border text-xs font-bold">
                                        <div class="flex items-center space-x-2 justify-center">
                                            {{-- Botones en modo edición --}}
                                            <x-tabla.boton-guardar x-show="editando"
                                                @click="guardarCambios(obra); editando = false" />
                                            <x-tabla.boton-cancelar-edicion x-show="editando"
                                                @click="editando = false" />

                                            {{-- Botones cuando NO está editando --}}
                                            <template x-if="!editando">
                                                <div class="flex items-center space-x-2">
                                                    {{-- Enlace a Google Maps --}}
                                                    <a href="https://www.google.com/maps?q={{ $obra->latitud }},{{ $obra->longitud }}"
                                                        target="_blank"
                                                        class="w-6 h-6 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 flex items-center justify-center"
                                                        title="Ver en mapa">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round">
                                                            <!-- Mapa estilo pliegue -->
                                                            <polyline
                                                                points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21 3 6" />
                                                            <!-- Pin de ubicación -->
                                                            <circle cx="12" cy="10" r="2" />
                                                        </svg>
                                                    </a>

                                                    {{-- Editar --}}
                                                    <x-tabla.boton-editar @click="editando = true" />

                                                    {{-- Eliminar --}}
                                                    <x-tabla.boton-eliminar :action="route('obras.destroy', $obra->id)" />
                                                </div>
                                            </template>
                                        </div>
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- MODAL PARA CREAR OBRA -->
        <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50" x-show="modalObra"
            x-transition.opacity>
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto transform transition-all scale-95"
                x-show="modalObra" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-90">

                <h3 class="text-2xl font-bold mb-6 text-center text-gray-800">Crear Nueva Obra</h3>

                <form action="{{ route('obras.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">

                    <!-- Formulario en dos columnas -->
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-700 font-semibold">Nombre de la Obra</label>
                            <input type="text" name="obra" class="form-input w-full p-2 border rounded-lg"
                                required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold">Código</label>
                            <input type="text" name="cod_obra" class="form-input w-full p-2 border rounded-lg"
                                required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold">Ciudad</label>
                            <input type="text" name="ciudad" class="form-input w-full p-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold">Dirección</label>
                            <input type="text" name="direccion" class="form-input w-full p-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold">Latitud</label>
                            <input type="text" name="latitud" class="form-input w-full p-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold">Longitud</label>
                            <input type="text" name="longitud" class="form-input w-full p-2 border rounded-lg">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-gray-700 font-semibold">Radio</label>
                            <input type="text" name="radio" class="form-input w-full p-2 border rounded-lg">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-gray-700 font-semibold">Tipo de Obra</label>
                            <select name="tipo" class="form-select w-full p-2 border rounded-lg" required>
                                <option value="">Selecciona tipo</option>
                                <option value="montaje">Montaje</option>
                                <option value="suministro">Suministro</option>
                            </select>
                        </div>

                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button"
                            class="btn bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold py-2 px-4 rounded-lg"
                            @click="modalObra = false">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="btn bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    <script>
        function guardarCambios(obra) {
            fetch(`{{ route('obras.update', '') }}/${obra.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(obra)
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
