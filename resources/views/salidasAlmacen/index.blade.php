<x-app-layout>
    <x-slot name="title">Salidas de Almacén - {{ config('app.name') }}</x-slot>
    <x-menu.salidas />
    <x-menu.salidas2 />
    <x-menu.salidasAlmacen />
    <div class="px-4 py-6">
        @if (auth()->user()->rol === 'oficina')
            <x-tabla.filtros-aplicados :filtros="$filtrosActivos" />

            <div class="overflow-x-auto bg-white shadow rounded-lg">
                <table class="w-full border-collapse text-sm text-center">
                    <thead class="bg-blue-500 text-white text-10">
                        <tr class="text-center text-xs uppercase">
                            <th class="p-2 border">{!! $ordenables['codigo'] ?? 'Código' !!}</th>
                            <th class="p-2 border">{!! $ordenables['codigo_sage'] ?? 'SAGE' !!}</th>
                            <th class="p-2 border">{!! $ordenables['matricula_texto'] ?? 'Matrícula' !!}</th>
                            <th class="p-2 border">{!! $ordenables['conductor'] ?? 'Conductor' !!}</th>
                            <th class="p-2 border">{!! $ordenables['fecha_salida'] ?? 'Fecha' !!}</th>
                            <th class="p-2 border">{!! $ordenables['estado'] ?? 'Estado' !!}</th>
                            <x-tabla.botones-filtro ruta="salidas-almacen.index" />
                        </tr>
                        <tr class="text-center text-xs uppercase">
                            <form method="GET" action="{{ route('salidas-almacen.index') }}">
                                <th class="p-1 border"><x-tabla.input name="codigo" :value="request('codigo')" /></th>
                                <th class="p-1 border"><x-tabla.input name="codigo_sage" :value="request('codigo_sage')" /></th>
                                <th class="p-1 border"><x-tabla.input name="matricula_texto" :value="request('matricula_texto')" /></th>
                                <th class="p-1 border"><x-tabla.input name="conductor" :value="request('conductor')" /></th>
                                <th class="p-1 border"><x-tabla.input name="fecha_salida" type="date"
                                        :value="request('fecha_salida')" /></th>
                                <th class="p-1 border">
                                    <x-tabla.select name="estado" :options="[
                                        'borrador' => 'Borrador',
                                        'activa' => 'Activa',
                                        'completada' => 'Completada',
                                        'cancelada' => 'Cancelada',
                                    ]" :selected="request('estado')" empty="Todos" />
                                </th>
                                <x-tabla.botones-filtro ruta="salidas-almacen.index" />
                            </form>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($salidas as $salida)
                            @php
                                $claseFondo = match ($salida->estado) {
                                    'completada' => 'bg-green-100',
                                    'activa' => 'bg-yellow-100',
                                    'cancelada' => 'bg-gray-200 text-gray-500',
                                    default => 'even:bg-gray-50 odd:bg-white',
                                };
                            @endphp
                            <tr class="text-xs {{ $claseFondo }}">
                                <td class="border px-2 py-1">{{ $salida->codigo }}</td>
                                <td class="border px-2 py-1">{{ $salida->codigo_sage ?? '—' }}</td>
                                <td class="border px-2 py-1">{{ $salida->matricula_texto ?? '—' }}</td>
                                <td class="border px-2 py-1">{{ $salida->conductor ?? '—' }}</td>
                                <td class="border px-2 py-1">{{ $salida->fecha_salida ?? '—' }}</td>
                                <td class="border px-2 py-1 capitalize">{{ $salida->estado }}</td>
                                <td class="border px-2 py-1">
                                    <div class="flex items-center justify-center gap-1">
                                        @if ($salida->estado === 'activa')
                                            <form method="POST"
                                                action="{{ route('salidas-almacen.desactivar', $salida->id) }}"
                                                onsubmit="return confirmarDesactivacion(event)" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 rounded px-2 py-1 text-xs">Desactivar</button>
                                            </form>
                                        @else
                                            <form method="POST"
                                                action="{{ route('salidas-almacen.activar', $salida->id) }}"
                                                onsubmit="return confirmarActivacion(event)" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="bg-yellow-100 hover:bg-yellow-200 text-yellow-700 rounded px-2 py-1 text-xs">Activar</button>
                                            </form>

                                            <form method="POST"
                                                action="{{ route('salidas-almacen.cancelar', $salida->id) }}"
                                                onsubmit="return confirmarCancelacion(event)" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 rounded px-2 py-1 text-xs">Cancelar</button>
                                            </form>

                                            <form method="POST"
                                                action="{{ route('salidas-almacen.destroy', $salida->id) }}"
                                                onsubmit="return confirmarEliminacion(event)" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="bg-red-100 hover:bg-red-200 text-red-700 rounded px-2 py-1 text-xs">Eliminar</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4 text-gray-500 italic">No hay salidas registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-tabla.paginacion :paginador="$salidas" />
        @endif
    </div>
    <script>
        function confirmarActivacion(e) {
            e.preventDefault();
            Swal.fire({
                title: '¿Activar salida?',
                text: 'La salida quedará activa y ya no se podrán modificar los datos.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, activar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#facc15',
                cancelButtonColor: '#e5e7eb',
                customClass: {
                    confirmButton: 'text-black',
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    e.target.closest('form').submit();

                }
            });
            return false;
        }

        function confirmarDesactivacion(e) {
            e.preventDefault();
            Swal.fire({
                title: '¿Desactivar salida?',
                text: 'Se permitirá modificar los datos de nuevo.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, desactivar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#9ca3af',
                cancelButtonColor: '#e5e7eb',
            }).then((result) => {
                if (result.isConfirmed) {
                    e.target.closest('form').submit();

                }
            });
            return false;
        }

        function confirmarCancelacion(e) {
            e.preventDefault();
            Swal.fire({
                title: '¿Cancelar salida?',
                text: 'Esta acción marcará la salida como cancelada y no se podrá completar.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'No',
                confirmButtonColor: '#9ca3af',
                cancelButtonColor: '#e5e7eb',
            }).then((result) => {
                if (result.isConfirmed) {
                    e.target.closest('form').submit();

                }
            });
            return false;
        }

        function confirmarEliminacion(e) {
            e.preventDefault();
            Swal.fire({
                title: '¿Eliminar salida?',
                text: 'Esta acción es irreversible. Se borrará la salida y sus relaciones.',
                icon: 'error',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'No',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#e5e7eb',
            }).then((result) => {
                if (result.isConfirmed) {
                    e.target.closest('form').submit();

                }
            });
            return false;
        }
    </script>

</x-app-layout>
