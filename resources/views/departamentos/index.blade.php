<x-app-layout>
    <x-slot name="title">Departamentos</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gestión de Departamentos') }}
        </h2>
    </x-slot>

    <div class="px-6 py-4 relative" x-data="{ openModal: false, openModalSecciones: false, openNuevoDepartamentoModal: false, openNuevaSeccionModal: false, departamentoId: null, usuariosMarcados: [] }">


        <!-- Tabla -->
        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="w-full table-auto border-collapse">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left">Nombre</th>
                        <th class="px-4 py-2 text-left">Descripción</th>
                        <th class="px-4 py-2 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departamentos as $departamento)
                        <tr class="border-t" x-data="{ open: false }">
                            <td class="px-4 py-2">
                                <button @click="open = !open"
                                    class="text-left font-semibold text-blue-600 hover:underline">
                                    {{ $departamento->nombre }}
                                </button>
                            </td>
                            <td class="px-4 py-2">{{ $departamento->descripcion }}</td>
                            <td class="px-4 py-2 text-right space-x-2">
                                <a href="{{ route('departamentos.edit', $departamento) }}"
                                    class="bg-yellow-400 hover:bg-yellow-500 text-white py-1 px-3 rounded text-sm">
                                    Editar
                                </a>
                                <form action="{{ route('departamentos.destroy', $departamento) }}" method="POST"
                                    class="inline-block" onsubmit="return confirm('¿Eliminar este departamento?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="bg-red-600 hover:bg-red-700 text-white py-1 px-3 rounded text-sm">
                                        Eliminar
                                    </button>
                                </form>
                                <button
                                    @click="openModal = true; departamentoId = {{ $departamento->id }}; usuariosMarcados = {{ $departamento->usuarios->pluck('id') }}"
                                    class="bg-green-600 hover:bg-green-700 text-white py-1 px-3 rounded text-sm">
                                    Asignar usuarios
                                </button>
                                <button @click="openModalSecciones = true; departamentoId = {{ $departamento->id }};"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white py-1 px-3 rounded text-sm">
                                    Asignar secciones
                                </button>

                            </td>
                        </tr>

                        <tr x-show="open" class="border-t bg-gray-50">
                            <td colspan="3" class="px-6 py-3 space-y-4">

                                {{-- Usuarios asignados --}}
                                <div>
                                    <h4 class="text-md font-semibold text-gray-700 mb-1">Usuarios asignados</h4>
                                    @if ($departamento->usuarios->isEmpty())
                                        <p class="text-gray-500">Sin usuarios asignados.</p>
                                    @else
                                        <ul class="list-disc pl-5 text-gray-800 space-y-1">
                                            @foreach ($departamento->usuarios as $usuario)
                                                <li>
                                                    {{ $usuario->name }}
                                                    <span class="text-sm text-gray-500">
                                                        ({{ $usuario->email }}{{ $usuario->pivot->rol_departamental ? ' – ' . $usuario->pivot->rol_departamental : '' }})
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>

                                {{-- Secciones asignadas --}}
                                <div>
                                    <h4 class="text-md font-semibold text-gray-700 mb-1">Secciones visibles</h4>
                                    @if ($departamento->secciones->isEmpty())
                                        <p class="text-gray-500">Sin secciones asignadas.</p>
                                    @else
                                        <ul class="grid grid-cols-2 md:grid-cols-3 gap-2 text-gray-800">
                                            @foreach ($departamento->secciones as $seccion)
                                                <li class="bg-white border rounded px-2 py-1 shadow-sm text-sm">
                                                    {{ $seccion->nombre }}
                                                    <span class="text-gray-500 text-xs">({{ $seccion->ruta }})</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-4 text-center text-gray-500">
                                No hay departamentos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Tabla resumen de todos los departamentos -->
        <!-- Botones para departamentos -->
        <div class="mt-12 flex justify-start items-center mb-4 gap-4">

            <h3 class="text-lg font-semibold text-gray-700">Departamentos</h3>
            <button @click="openNuevoDepartamentoModal = true"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow">
                + Nuevo Departamento
            </button>
        </div>

        <div class="w-full mt-8 overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[1000px] border border-gray-300 rounded-lg" id="tablaDepartamentos">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-center text-xs uppercase">
                        <th class="px-4 py-2 text-left">Nombre</th>
                        <th class="px-4 py-2 text-left">Descripción</th>
                        <th class="px-4 py-2 text-left">Usuarios asignados</th>
                        <th class="px-4 py-2 text-left">Secciones visibles</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($departamentos as $dep)
                        <tr x-data="{
                            editando: false,
                            departamento: @js($dep),
                            original: JSON.parse(JSON.stringify(@js($dep)))
                        }" @dblclick="editando = true"
                            @keydown.enter.stop.prevent="guardarDepartamento(departamento); editando = false"
                            @keydown.escape="departamento = JSON.parse(JSON.stringify(original)); editando = false"
                            :class="{ 'bg-yellow-100': editando }"
                            class="border-t cursor-pointer hover:bg-blue-50 focus:outline-none" tabindex="0">



                            <!-- Nombre -->
                            <td class="px-4 py-2 border">
                                <template x-if="!editando">
                                    <span x-text="departamento.nombre"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="departamento.nombre"
                                    class="form-input w-full">
                            </td>

                            <!-- Descripción -->
                            <td class="px-4 py-2 border">
                                <template x-if="!editando">
                                    <span x-text="departamento.descripcion ?? '—'"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="departamento.descripcion"
                                    class="form-input w-full">
                            </td>

                            <!-- Usuarios asignados -->
                            <td class="px-4 py-2 border text-center">
                                {{ $dep->usuarios->count() }} usuario{{ $dep->usuarios->count() === 1 ? '' : 's' }}
                            </td>

                            <!-- Secciones visibles -->
                            <td class="px-4 py-2 border text-center">
                                {{ $dep->secciones->count() }} sección{{ $dep->secciones->count() === 1 ? '' : 'es' }}
                            </td>

                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>
        <script>
            function guardarDepartamento(departamento) {
                fetch(`{{ url('/departamentos') }}/${departamento.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            nombre: departamento.nombre,
                            descripcion: departamento.descripcion
                        })
                    })
                    .then(async response => {
                        const contentType = response.headers.get('content-type');
                        let data = {};

                        if (contentType && contentType.includes('application/json')) {
                            data = await response.json();
                        } else {
                            const text = await response.text();
                            throw new Error("El servidor devolvió una respuesta inesperada: " + text.slice(0, 100));
                        }

                        if (response.ok && data.success) {
                            // Se actualizó correctamente
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
                        Swal.fire({
                            icon: "error",
                            title: "Error de conexión",
                            text: error.message || "No se pudo actualizar el departamento.",
                            confirmButtonText: "OK"
                        });
                    });
            }
        </script>

        <!-- Tabla resumen de todas las secciones -->
        <!-- Botones para secciones -->
        <div class="mt-12 flex justify-start items-center mb-4 gap-4">

            <h3 class="text-lg font-semibold text-gray-700">Secciones</h3>
            <button @click="openNuevaSeccionModal = true"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded shadow">
                + Nueva Sección
            </button>
        </div>

        <div class="w-full mt-8 overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[1000px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-center text-xs uppercase">
                        <th class="px-4 py-2 text-left">Nombre</th>
                        <th class="px-4 py-2 text-left">Ruta</th>
                        <th class="px-4 py-2 text-left">Icono</th>
                        <th class="px-4 py-2 text-left">Departamentos asociados</th>
                        <th class="px-4 py-2 text-left">Pagina Principal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($todasLasSecciones as $sec)
                        <tr x-data="{
                            editando: false,
                            seccion: @js($sec),
                            original: JSON.parse(JSON.stringify(@js($sec)))
                        }" @dblclick="editando = true"
                            @keydown.enter.stop.prevent="guardarSeccion(seccion); editando = false"
                            @keydown.escape="seccion = JSON.parse(JSON.stringify(original)); editando = false"
                            :class="{ 'bg-yellow-100': editando }"
                            class="border-t cursor-pointer hover:bg-blue-50 focus:outline-none" tabindex="0">

                            <!-- Nombre -->
                            <td class="px-4 py-2 border">
                                <template x-if="!editando">
                                    <span x-text="seccion.nombre"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="seccion.nombre"
                                    class="form-input w-full">
                            </td>

                            <!-- Ruta -->
                            <td class="px-4 py-2 border">
                                <template x-if="!editando">
                                    <span x-text="seccion.ruta"></span>
                                </template>
                                <input x-show="editando" type="text" x-model="seccion.ruta"
                                    class="form-input w-full">
                            </td>

                            <!-- Icono -->
                            <td class="px-4 py-2 border text-center">
                                <template x-if="!editando">
                                    <template x-if="seccion.icono">
                                        <img :src="'{{ asset('') }}' + seccion.icono" alt="Icono"
                                            class="h-6 mx-auto">
                                    </template>
                                    <template x-if="!seccion.icono">
                                        <span class="text-gray-400 italic">Sin icono</span>
                                    </template>
                                </template>
                                <input x-show="editando" type="text" x-model="seccion.icono"
                                    class="form-input w-full">
                            </td>

                            <!-- Departamentos asociados -->
                            <td class="px-4 py-2 border text-center">
                                {{ $sec->departamentos->pluck('nombre')->join(', ') ?: 'Ninguno' }}
                            </td>

                            <!-- Mostrar en dashboard -->
                            <td class="px-4 py-2 border text-center">
                                <input type="checkbox" :checked="seccion.mostrar_en_dashboard"
                                    @change="seccion.mostrar_en_dashboard = !seccion.mostrar_en_dashboard; guardarSeccion(seccion)"
                                    class="form-checkbox h-5 w-5 text-blue-600 cursor-pointer">

                            </td>


                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>
        <script>
            function guardarSeccion(seccion) {
                fetch(`{{ url('/secciones') }}/${seccion.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            nombre: seccion.nombre,
                            ruta: seccion.ruta,
                            icono: seccion.icono,
                            mostrar_en_dashboard: seccion.mostrar_en_dashboard ? 1 : 0
                        })
                    })
                    .then(async response => {
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            const data = await response.json();
                            if (!data.success) {
                                throw new Error(data.message || "Error al actualizar");
                            }
                        } else {
                            const text = await response.text();
                            throw new Error("El servidor devolvió una respuesta inesperada: " + text.slice(0, 100));
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: "error",
                            title: "Error de conexión",
                            text: error.message || "No se pudo actualizar la sección.",
                            confirmButtonText: "OK"
                        });
                    });
            }
        </script>

        <!-- Modal Nueva Sección -->
        <template x-if="openNuevaSeccionModal">
            <div class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md z-50">
                    <h3 class="text-lg font-semibold mb-4">Crear nueva sección</h3>

                    <form method="POST" action="{{ route('secciones.store') }}">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Nombre</label>
                            <input type="text" name="nombre" required
                                class="w-full p-2 border border-gray-300 rounded-lg">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Ruta (route name)</label>
                            <input type="text" name="ruta" required
                                class="w-full p-2 border border-gray-300 rounded-lg"
                                placeholder="ej: productos.index">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Ruta del icono</label>
                            <input type="text" name="icono" class="w-full p-2 border border-gray-300 rounded-lg"
                                placeholder="imagenes/iconos/nombre.png">
                        </div>

                        <div class="mt-4 flex justify-end space-x-2">
                            <button type="button" @click="openNuevaSeccionModal = false"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">
                                Crear
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- Modal Asignar Usuarios -->
        <template x-if="openModal">
            <div class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-lg z-50">
                    <h3 class="text-lg font-semibold mb-4">Asignar usuarios al departamento</h3>

                    <form method="POST" :action="'/departamentos/' + departamentoId + '/asignar-usuarios'">
                        @csrf

                        <div class="max-h-64 overflow-y-auto border rounded p-2 space-y-1">
                            @forelse ($usuariosOficina as $usuario)
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" name="usuarios[]" :value="'{{ $usuario->id }}'"
                                        :checked="usuariosMarcados.includes({{ $usuario->id }})"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm">
                                    <span>{{ $usuario->name }}
                                        <span class="text-sm text-gray-500">({{ $usuario->email }})</span>
                                    </span>
                                </label>
                            @empty
                                <p class="text-gray-500">No hay usuarios con rol oficina.</p>
                            @endforelse
                        </div>

                        <div class="mt-4 flex justify-end space-x-2">
                            <button type="button" @click="openModal = false"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                                Cancelar
                            </button>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                                Asignar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- Modal Nuevo Departamento -->
        <template x-if="openNuevoDepartamentoModal">
            <div class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md z-50">
                    <h3 class="text-lg font-semibold mb-4">Crear nuevo departamento</h3>

                    <form method="POST" action="{{ route('departamentos.store') }}">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Nombre</label>
                            <input type="text" name="nombre" required
                                class="w-full p-2 border border-gray-300 rounded-lg">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Descripción</label>
                            <textarea name="descripcion" rows="3" class="w-full p-2 border border-gray-300 rounded-lg"></textarea>
                        </div>

                        <div class="mt-4 flex justify-end space-x-2">
                            <button type="button" @click="openNuevoDepartamentoModal = false"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                                Cancelar
                            </button>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                                Crear
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
        <!-- Modal Asignar Secciones -->
        <template x-if="openModalSecciones">
            <div class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-lg z-50">
                    <h3 class="text-lg font-semibold mb-4">Asignar secciones al departamento</h3>

                    <form method="POST" :action="'/departamentos/' + departamentoId + '/asignar-secciones'">
                        @csrf

                        <div class="max-h-64 overflow-y-auto border rounded p-2 space-y-1">
                            @forelse ($todasLasSecciones as $seccion)
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" name="secciones[]" :value="'{{ $seccion->id }}'"
                                        :checked="{{ $seccion->departamentos->pluck('id') }}.includes(departamentoId)"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm">
                                    <span>{{ $seccion->nombre }}
                                        <span class="text-sm text-gray-500">({{ $seccion->ruta }})</span>
                                    </span>
                                </label>
                            @empty
                                <p class="text-gray-500">No hay secciones registradas.</p>
                            @endforelse
                        </div>

                        <div class="mt-4 flex justify-end space-x-2">
                            <button type="button" @click="openModalSecciones = false"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">
                                Asignar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

    </div>
</x-app-layout>
