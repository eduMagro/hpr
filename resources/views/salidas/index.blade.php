<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Salidas de Camiones') }}
        </h2>
    </x-slot>

    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4 text-black">Registrar Nueva Salida</h1>

        <!-- Formulario para crear una salida -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <form action="{{ route('salidas.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="camion" class="block text-black font-semibold">Camión</label>
                    <input type="text" name="camion" id="camion" class="w-full p-2 border rounded" placeholder="Ej: Camión ABC123" required>
                </div>

                <div class="mb-4">
                    <label class="block text-black font-semibold">Seleccionar Planillas Completadas</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2">
                        @foreach ($planillasCompletadas as $planilla)
                            <label class="flex items-center bg-gray-100 p-2 rounded-lg">
                                <input type="checkbox" name="planillas[]" value="{{ $planilla->id }}" class="mr-2">
                                <span class="text-gray-800">
                                    Planilla: <strong>{{ $planilla->codigo_limpio }}</strong> - Cliente: {{ $planilla->cliente }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Registrar Salida
                </button>
            </form>
        </div>

        <!-- Tabla de salidas registradas -->
        <h1 class="text-2xl font-bold mb-4 text-black">Historial de Salidas</h1>
        <div class="bg-white shadow-md rounded-lg p-6">
            <table class="w-full border-collapse">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Camión</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Planillas Transportadas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Fecha de Salida</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @foreach ($salidas as $salida)
                        <tr class="border-b hover:bg-gray-100">
                            <td class="px-6 py-4 text-black">{{ $salida->camion }}</td>
                            <td class="px-6 py-4 text-gray-800">
                                @foreach ($salida->planillas as $planilla)
                                    <span class="block">{{ $planilla->codigo_limpio }} - {{ $planilla->cliente }}</span>
                                @endforeach
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $salida->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
