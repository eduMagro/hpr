<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Entradas de Material') }}
        </h2>
    </x-slot>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (session('error'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '{{ session('error') }}',
                    confirmButtonColor: '#d33'
                });
            });
        </script>
    @endif
    @if (session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'success',
                    text: '{{ session('success') }}',
                    confirmButtonColor: '#28a745'
                });
            });
        </script>
    @endif

    <div class="w-full px-6 py-4">
        <div class="flex flex-wrap gap-4 mb-4">
            <a href="{{ route('entradas.create') }}" class="btn btn-primary">Crear Nueva Entrada</a>
        </div>

        <table class="w-full min-w-[1200px] border-collapse bg-white shadow-md rounded-lg">
            <thead class="bg-gray-800 text-white">
                <tr class="text-left text-sm uppercase">
                    <th class="px-6 py-3">Albarán</th>
                    <th class="px-6 py-3">Fecha</th>
                    <th class="px-6 py-3">Fabricantes</th>
                    <th class="px-6 py-3">Productos Asociados</th>
                    <th class="px-6 py-3">Usuario</th>
                    <th class="px-6 py-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 text-sm">
                @forelse ($entradas as $entrada)
                    <tr class="border-b hover:bg-gray-100">
                        <td class="px-6 py-4">{{ $entrada->albaran }}</td>
                        <td class="px-6 py-4">{{ $entrada->created_at }}</td>
                        <td class="px-6 py-4">
                            @php $fabricantes = $entrada->productos->pluck('fabricante')->unique(); @endphp
                            {{ $fabricantes->isNotEmpty() ? $fabricantes->join(', ') : 'No disponible' }}
                        </td>
                        <td class="px-6 py-4">
                            <ul>
                                @foreach ($entrada->productos as $producto)
                                    <li>
                                        <strong>ID:</strong>
                                        @if ($producto->id)
                                            <a href="{{ route('productos.show', $producto->id) }}"
                                                class="text-blue-500 hover:underline">
                                                {{ $producto->id }}
                                            </a>
                                        @endif
                                        -
                                        <strong>Producto:</strong> {{ $producto->nombre }} /
                                        {{ $producto->tipo }} -
                                        <strong>Ubicación:</strong>
                                        {{ $producto->ubicacion->nombre ?? ($producto->maquina->nombre ?? 'No ubicada') }}
                                        <button
                                            onclick="generateAndPrintQR('{{ $producto->id }}', '{{ $producto->n_colada }}', 'MATERIA PRIMA')"
                                            class="btn btn-primary btn-sm">QR</button>
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="px-6 py-4">{{ $entrada->user->name }}</td>
                        <td>
                            <a href="{{ route('entradas.edit', $entrada->id) }}"
                                class="text-blue-600 hover:text-blue-900">Editar</a>
                            <x-boton-eliminar :action="route('entradas.destroy', $entrada->id)" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center">No hay entradas de material disponibles.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="flex justify-center mt-4">
            {{ $entradas->appends(request()->except('page'))->links() }}
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="{{ asset('js/imprimirQr.js') }}"></script>
</x-app-layout>
