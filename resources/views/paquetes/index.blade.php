<x-app-layout>
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

        <!-- Encabezado -->
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-700">Listado de Paquetes</h3>
        </div>

        <!-- Contenedor de la tabla -->
        <div class="w-full overflow-x-auto bg-white shadow-md rounded-lg">
            <table class="w-full min-w-[800px] border-collapse">
                <thead class="bg-gray-800 text-white">
                    <tr class="text-left text-sm uppercase">
                        <th class="px-4 py-2">ID</th>
                        <th class="px-4 py-2">Planilla</th>
                        <th class="px-4 py-2">Ubicación</th>
                        <th class="px-4 py-2">Cantidad de Etiquetas</th>
                        <th class="px-4 py-2">Etiquetas</th>
                        <th class="px-4 py-2">Creación Paquete</th>
                        <th class="px-4 py-2">Fecha Estimada Reparto</th>
                        <th class="px-4 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse ($paquetes as $paquete)
                        <tr class="border-b hover:bg-gray-100">
                            <td class="px-4 py-2">{{ $paquete->id }}</td>
                            <td class="px-4 py-2">
                                <a href="{{ route('planillas.index', ['id' => $paquete->planilla->id]) }}"
                                    class="text-blue-500 hover:underline">
                                    {{ $paquete->planilla->codigo_limpio }}
                                </a>
                            </td>

                            <td class="px-4 py-2">{{ $paquete->ubicacion->nombre ?? 'Sin ubicación' }}</td>
                            <td class="px-4 py-2">{{ $paquete->etiquetas->count() }}</td>
                            <td class="px-4 py-2">
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
                                    ID {{ $elemento->id }} - FIGURA {{ $elemento->figura }}
                                </a>

                                <!-- Subpaquetes dentro de los elementos -->
                                @if ($elemento->subpaquetes->isNotEmpty())
                                    <ul class="list-disc pl-8 text-red-500 text-sm">
                                        @foreach ($elemento->subpaquetes as $subpaquete)
                                            <li>
                                                <a href="#" class="text-red-500 hover:underline">
                                                    Subpaquete #{{ $subpaquete->id }} - Peso: {{ $subpaquete->peso }} kg
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <ul class="pl-6 text-gray-500 text-sm">
                        <li>Sin elementos</li>
                    </ul>
                @endif
            @endforeach
        </ul>
    @endif

    <!-- Mostrar elementos sin etiquetas -->
    @if ($paquete->elementos->isNotEmpty())
        <ul class="list-disc pl-4 text-green-600 text-sm">
            @foreach ($paquete->elementos as $elemento)
                <li>
                    <a href="{{ route('elementos.index', ['id' => $elemento->id]) }}"
                        class="text-green-500 hover:underline">
                        ID {{ $elemento->id }} - FIGURA {{ $elemento->figura }}
                    </a>

                    <!-- Subpaquetes dentro de los elementos sin etiquetas -->
                    @if ($elemento->subpaquetes->isNotEmpty())
                        <ul class="list-disc pl-8 text-red-500 text-sm">
                            @foreach ($elemento->subpaquetes as $subpaquete)
                                <li>
                                    <a href="#" class="text-red-500 hover:underline">
                                        Subpaquete #{{ $subpaquete->id }} - Peso: {{ $subpaquete->peso }} kg
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <!-- Mensaje si no hay etiquetas ni elementos -->
    @if ($paquete->etiquetas->isEmpty() && $paquete->elementos->isEmpty())
        <span class="text-gray-500">Sin etiquetas ni elementos</span>
    @endif
</td>

                            <td class="px-4 py-2">{{ $paquete->created_at->format('d/m/Y H:i') }}</td>


                            <td class="px-4 py-2">
                                {{ optional($paquete->planilla->fecha_estimada_reparto)->format('d/m/Y') ?? 'No disponible' }}
                            </td>


                            <td class="px-4 py-2 flex space-x-2">
                                <a href="{{ route('paquetes.show', $paquete->id) }}"
                                    class="text-blue-500 hover:underline">Ver</a>
                                <a href="{{ route('paquetes.edit', $paquete->id) }}"
                                    class="text-yellow-500 hover:underline">Editar</a>
                                <form action="{{ route('paquetes.destroy', $paquete->id) }}" method="POST"
                                    class="inline"
                                    onsubmit="return confirm('¿Seguro que deseas eliminar este paquete?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-gray-500">No hay paquetes registrados</td>
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
</x-app-layout>
