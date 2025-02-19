<x-app-layout>
    <x-slot name="title">Paquetes - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            <a href="{{ route('planillas.index') }}" class="text-blue-600">
                {{ __('Planillas') }}
            </a>
            <span class="mx-2">/</span>

            {{ __('Lista de Paquetes') }}

            <span class="mx-2">/</span>
            <a href="{{ route('etiquetas.index') }}" class="text-blue-600">
                {{ __('Etiquetas') }}
            </a>
            <span class="mx-2">/</span>
            <a href="{{ route('elementos.index') }}" class="text-blue-600">
                {{ __('Elementos') }}
            </a>
            <span class="mx-2">/</span>
            <a href="{{ route('subpaquetes.index') }}" class="text-blue-600">
                {{ __('Subpaquetes') }}
            </a>
        </h2>
    </x-slot>
    <div class="w-full px-6 py-4">

        <!-- Contenedor de la tabla -->
        <div class="w-full overflow-x-auto bg-white shadow-lg rounded-lg">
            <table class="w-full min-w-[800px] border border-gray-300 rounded-lg">
                <thead class="bg-blue-500 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="px-4 py-3 border">ID
                            <!-- Formulario de búsqueda por ID -->
                            <form method="GET" action="{{ route('paquetes.index') }}" class="mt-2 flex space-x-2">
                                <input type="text" name="id"
                                    class="w-20 px-2 py-1 text-gray-900 border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar">
                                <button type="submit"
                                    class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-600 hidden">
                                    Filtrar
                                </button>
                            </form>
                        </th>
                        <th class="px-4 py-3 border">Planilla</th>
                        <th class="px-4 py-3 border">Ubicación</th>
                        <th class="px-4 py-3 border">Cantidad de Etiquetas</th>
                        <th class="px-4 py-3 border">Etiquetas</th>
                        <th class="px-4 py-3 border">Creación Paquete</th>
                        <th class="px-4 py-3 border">Fecha Estimada Reparto</th>
                        <th class="px-4 py-3 border text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($paquetes as $paquete)
                        <tr class="border-b odd:bg-gray-100 even:bg-gray-50 hover:bg-blue-200">
                            <td class="px-4 py-3 text-center border">{{ $paquete->id }}</td>
                            <td class="px-4 py-3 text-center border">
                                <a href="{{ route('planillas.index', ['id' => $paquete->planilla->id]) }}"
                                    class="text-blue-500 hover:underline">
                                    {{ $paquete->planilla->codigo_limpio }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center border">
                                {{ $paquete->ubicacion->nombre ?? 'Sin ubicación' }}</td>
                            <td class="px-4 py-3 text-center border">{{ $paquete->etiquetas->count() }}</td>
                            <td class="px-4 py-3 text-center border">
                                @if ($paquete->etiquetas->isNotEmpty())
                                    <ul class="list-disc pl-4 text-blue-600 text-sm">
                                        @foreach ($paquete->etiquetas as $etiqueta)
                                            <li class="font-semibold">
                                                <a href="{{ route('etiquetas.index', ['id' => $etiqueta->id]) }}"
                                                    class="text-blue-500 hover:underline">
                                                    {{ $etiqueta->nombre }} (ID: {{ $etiqueta->id }})
                                                </a>
                                            </li>
                                            @if ($etiqueta->elementos->isNotEmpty())
                                                <ul class="list-disc pl-6 text-green-600 text-sm">
                                                    @foreach ($etiqueta->elementos as $elemento)
                                                        <li>
                                                            <a href="{{ route('elementos.index', ['id' => $elemento->id]) }}"
                                                                class="text-green-500 hover:underline">
                                                                ID {{ $elemento->id }} - FIGURA
                                                                {{ $elemento->figura }}
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        @endforeach
                                    </ul>
                                @elseif ($paquete->elementos->isNotEmpty())
                                    <ul class="list-disc pl-4 text-green-600 text-sm">
                                        @foreach ($paquete->elementos as $elemento)
                                            <li class="font-semibold">
                                                <a href="{{ route('elementos.index', ['id' => $elemento->id]) }}"
                                                    class="text-green-500 hover:underline">
                                                    ID {{ $elemento->id }} - FIGURA {{ $elemento->figura }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-gray-500">Vacío</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-center border">{{ $paquete->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-center border">
                                {{ optional($paquete->planilla->fecha_estimada_reparto)->format('d/m/Y') ?? 'No disponible' }}
                            </td>
                            <td class="px-4 py-3 text-center border">
                                <button
                                    onclick="generateAndPrintQR('{{ $paquete->id }}', '{{ $paquete->planilla->codigo_limpio }}', 'PAQUETE')"
                                    class="px-2 py-1 bg-green-500 text-white rounded hover:bg-green-600"><i
                                        class="fas fa-qrcode"></i>
                                </button>
                                <a href="{{ route('paquetes.show', $paquete->id) }}"
                                    class="text-blue-500 hover:underline">Ver</a>
                                <a href="{{ route('paquetes.edit', $paquete->id) }}"
                                    class="text-yellow-500 hover:underline">Editar</a>
                                <x-boton-eliminar :action="route('paquetes.destroy', $paquete->id)" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-gray-500">No hay paquetes registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="mt-4 flex justify-center">
            {{ $paquetes->links() }}
        </div>
    </div>
    <!-- SCRIPT PARA IMPRIMIR QR -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="{{ asset('js/imprimirQrS.js') }}"></script>
</x-app-layout>
