<x-app-layout>
    <x-slot name="title">Departamentos</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gestión de Departamentos') }}
        </h2>
    </x-slot>

    <div class="px-6 py-4 relative" x-data="{ openModal: false, openModalSecciones: false, openNuevoDepartamentoModal: false, openNuevaSeccionModal: false, departamentoId: null, usuariosMarcados: [] }">
        <!-- Botones -->
        <div class="flex justify-between items-center mb-4 space-x-2">
            <button @click="openNuevoDepartamentoModal = true"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow">
                + Nuevo Departamento
            </button>
            <button @click="openNuevaSeccionModal = true"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded shadow">
                + Nueva Sección
            </button>
        </div>

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

                                {{-- Botón para asignar secciones (en desarrollo futuro) --}}
                                {{-- <button class="mt-2 bg-indigo-600 hover:bg-indigo-700 text-white py-1 px-3 rounded text-sm">
            Asignar secciones
        </button> --}}
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
                                class="w-full p-2 border border-gray-300 rounded-lg" placeholder="ej: productos.index">
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
