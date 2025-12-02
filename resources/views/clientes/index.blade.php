<x-app-layout>
    <x-slot name="title">Clientes - {{ config('app.name') }}</x-slot>

    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Lista de Clientes') }}
        </h2>
    </x-slot>


    <div x-data="{ modalObra: false }">
        {{--  Nuevo cliente --}}
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <button @click="modalObra = true"
                class="inline-block text-white bg-blue-600 hover:bg-blue-700 font-semibold px-4 py-2 rounded shadow text-sm transition">
                ➕ Nuevo Cliente
            </button>

            <form method="GET" action="{{ route('clientes.index') }}" class="flex flex-wrap gap-2 items-center">
                <x-tabla.input name="codigo_obra" value="{{ request('cod_obra') }}" placeholder="Código de obra"
                    class="w-40" />
                <x-tabla.input name="obra" value="{{ request('obra') }}" placeholder="Nombre de obra"
                    class="w-52" />
                <x-tabla.botones-filtro ruta="clientes.index" />

            </form>
        </div>

        <!-- Modal nuevo cliente-->
        <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50" x-show="modalObra"
            x-transition x-cloak>
            <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-3xl">
                <h3 class="text-xl font-semibold mb-6 text-gray-800">Crear Nuevo Cliente</h3>

                <form action="{{ route('clientes.store') }}" method="POST" class="space-y-6">
                    @csrf

                    {{-- Contenedor en dos columnas --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="empresa" class="block text-sm font-semibold text-gray-700 mb-1">Nombre de la
                                Empresa</label>
                            <x-tabla.input name="empresa" required />
                        </div>

                        <div>
                            <label for="codigo" class="block text-sm font-semibold text-gray-700 mb-1">Código</label>
                            <x-tabla.input name="codigo" required />
                        </div>

                        <div>
                            <label for="telefono"
                                class="block text-sm font-semibold text-gray-700 mb-1">Teléfono</label>
                            <x-tabla.input name="telefono" />
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                            <x-tabla.input type="email" name="email" />
                        </div>

                        <div>
                            <label for="direccion"
                                class="block text-sm font-semibold text-gray-700 mb-1">Dirección</label>
                            <x-tabla.input name="direccion" />
                        </div>

                        <div>
                            <label for="ciudad" class="block text-sm font-semibold text-gray-700 mb-1">Ciudad</label>
                            <x-tabla.input name="ciudad" />
                        </div>

                        <div>
                            <label for="provincia"
                                class="block text-sm font-semibold text-gray-700 mb-1">Provincia</label>
                            <x-tabla.input name="provincia" />
                        </div>

                        <div>
                            <label for="pais" class="block text-sm font-semibold text-gray-700 mb-1">País</label>
                            <x-tabla.input name="pais" />
                        </div>

                        <div>
                            <label for="cif_nif" class="block text-sm font-semibold text-gray-700 mb-1">CIF/NIF</label>
                            <x-tabla.input name="cif_nif" />
                        </div>

                        <div>
                            <label for="activo" class="block text-sm font-semibold text-gray-700 mb-1">Activo</label>
                            <x-tabla.select name="activo" :options="['1' => 'Sí', '0' => 'No']" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" @click="modalObra = false"
                            class="inline-block text-white bg-gray-500 hover:bg-gray-600 font-semibold px-4 py-2 rounded shadow text-sm transition">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="inline-block text-white bg-blue-600 hover:bg-blue-700 font-semibold px-4 py-2 rounded shadow text-sm transition">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />
        <!-- TABLA DE CLIENTES -->
        <div class="w-full max-w-full overflow-x-auto bg-white shadow-lg rounded-lg mt-4">
            <table class="w-full border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="p-2 text-center">{!! $ordenables['id'] !!}</th>
                        <th class="p-2 text-center">{!! $ordenables['empresa'] !!}</th>
                        <th class="p-2 text-center">{!! $ordenables['codigo'] !!}</th>
                        <th class="p-2 text-center">{!! $ordenables['contacto1_telefono'] !!}</th>
                        <th class="p-2 text-center">{!! $ordenables['contacto1_email'] !!}</th>
                        <th class="p-2 text-center">Dirección</th>
                        <th class="p-2 text-center">{!! $ordenables['ciudad'] !!}</th>
                        <th class="p-2 text-center">{!! $ordenables['provincia'] !!}</th>
                        <th class="p-2 text-center">{!! $ordenables['pais'] !!}</th>
                        <th class="p-2 text-center">{!! $ordenables['cif_nif'] !!}</th>
                        <th class="p-2 text-center">{!! $ordenables['activo'] !!}</th>
                        <th class="p-2 text-center">Acciones</th>

                    </tr>
                    <tr class="text-left text-sm uppercase">
                        <form method="GET" action="{{ route('clientes.index') }}">
                            <th class="p-1 border">
                                <x-tabla.input name="id" value="{{ request('id') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="empresa" value="{{ request('empresa') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="codigo" value="{{ request('codigo') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="telefono" value="{{ request('telefono') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="email" value="{{ request('email') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="direccion" value="{{ request('direccion') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="ciudad" value="{{ request('ciudad') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="provincia" value="{{ request('provincia') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="pais" value="{{ request('pais') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.input name="cif_nif" value="{{ request('cif_nif') }}" />
                            </th>
                            <th class="p-1 border">
                                <x-tabla.select name="activo" :options="['1' => 'Sí', '0' => 'No']" :selected="request('activo')" empty="Todos" />
                            </th>
                            <x-tabla.botones-filtro ruta="clientes.index" />
                        </form>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($clientes as $cliente)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200 cursor-pointer"
                            x-data="{ editando: false, cliente: @js($cliente), original: JSON.parse(JSON.stringify(@js($cliente))) }"
                            @dblclick="if (!$event.target.closest('input')) {
                                editando = !editando;
                                if (!editando) cliente = JSON.parse(JSON.stringify(original));
                            }"
                            @keydown.enter.stop="guardarCambios(cliente); editando = false"
                            :class="{ 'bg-yellow-100': editando }">

                            <td class="px-2 py-2 text-center border" x-text="cliente.id"></td>
                            <td class="px-2 py-2 text-center border">
                                <template x-if="!editando"><span x-text="cliente.empresa"></span></template>
                                <x-tabla.input x-show="editando" x-model="cliente.empresa" />
                            </td>
                            <td class="px-2 py-2 text-center border">
                                <template x-if="!editando"><span x-text="cliente.codigo"></span></template>
                                <x-tabla.input x-show="editando" x-model="cliente.codigo" />
                            </td>
                            <td class="px-2 py-2 text-center border">
                                <template x-if="!editando"><span
                                        x-text="cliente.contacto1_telefono"></span></template>
                                <x-tabla.input x-show="editando" x-model="cliente.contacto1_telefono" />
                            </td>
                            <td class="px-2 py-2 text-center border">
                                <template x-if="!editando"><span x-text="cliente.contacto1_email"></span></template>
                                <x-tabla.input x-show="editando" x-model="cliente.contacto1_email" />
                            </td>
                            <td class="px-2 py-2 text-center border">
                                <template x-if="!editando"><span x-text="cliente.direccion"></span></template>
                                <x-tabla.input x-show="editando" x-model="cliente.direccion" />
                            </td>
                            <td class="px-2 py-2 text-center border">
                                <template x-if="!editando"><span x-text="cliente.ciudad"></span></template>
                                <x-tabla.input x-show="editando" x-model="cliente.ciudad" />
                            </td>
                            <td class="px-2 py-2 text-center border">
                                <template x-if="!editando"><span x-text="cliente.provincia"></span></template>
                                <x-tabla.input x-show="editando" x-model="cliente.provincia" />
                            </td>
                            <td class="px-2 py-2 text-center border">
                                <template x-if="!editando"><span x-text="cliente.pais"></span></template>
                                <x-tabla.input x-show="editando" x-model="cliente.pais" />
                            </td>
                            <td class="px-2 py-2 text-center border">
                                <template x-if="!editando"><span x-text="cliente.cif_nif"></span></template>
                                <x-tabla.input x-show="editando" x-model="cliente.cif_nif" />
                            </td>
                            <td class="px-2 py-2 text-center border">
                                <template x-if="!editando">
                                    <span :class="cliente.activo ? 'text-green-500' : 'text-red-500'"
                                        x-text="cliente.activo ? 'Activo' : 'Inactivo'"></span>
                                </template>

                            </td>
                            <td class="px-2 py-2 text-center border">
                                <div class="flex justify-center items-center space-x-2">
                                    <x-tabla.boton-guardar x-show="editando"
                                        @click="guardarCambios(cliente); editando = false" />
                                    <x-tabla.boton-cancelar-edicion x-show="editando" @click="editando = false" />
                                    <template x-if="!editando">
                                        <div class="flex items-center space-x-2">
                                            <x-tabla.boton-editar @click="editando = true" />
                                            <x-tabla.boton-ver :href="route('clientes.show', $cliente->id)" />
                                            <x-tabla.boton-eliminar :action="route('clientes.destroy', $cliente->id)" />
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center py-4 text-gray-500">No hay clientes
                                registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-tabla.paginacion :paginador="$clientes" />

    </div>



    <script>
        function guardarCambios(cliente) {
            console.log('hola');
            fetch(`/clientes/${cliente.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(cliente)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Cliente actualizado",
                            text: "Los cambios se han guardado exitosamente.",
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        let errores = Object.values(data.error).flat().join('\n'); // Convierte el objeto en texto
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de validación',
                            text: errores
                        });
                    }
                })
                .catch(error => {
                    console.error("❌ Error en la solicitud fetch:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Error de conexión",
                        text: "No se pudo actualizar el usuario. Inténtalo nuevamente.",
                        confirmButtonText: "OK"
                    });
                });
        }
    </script>
</x-app-layout>
