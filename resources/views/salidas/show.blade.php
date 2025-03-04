<x-app-layout>
    <x-slot name="title">Salida #{{ $salida->id }} - {{ config('app.name') }}</x-slot>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Detalles de la Salida') }} #{{ $salida->id }}
        </h2>
    </x-slot>

    <div class="container mx-auto p-6">
        <!-- Detalles de la salida -->
        <div class="bg-white shadow-lg rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center">
                    <label class="font-semibold text-lg text-gray-800">
                        Salida #{{ $salida->id }}
                    </label>
                </div>
                <span class="text-sm text-gray-600">Fecha: {{ $salida->created_at->format('d/m/Y H:i') }}</span>
            </div>

            <p class="text-gray-700 mt-2">Camión: <span class="font-semibold text-gray-800">{{ $salida->camion->modelo }}
                    - {{ $salida->camion->matricula }}</span></p>

            <h3 class="font-semibold text-md text-gray-800 mt-4">Paquetes Asociados:</h3>
            <div class="space-y-2 mt-2">
                @foreach ($salida->paquetes as $paquete)
                    <div class="flex items-center border-b pb-2">
                        <span class="text-gray-900 font-medium">{{ $paquete->planilla->codigo_limpio }} - </span>
                        <span class="text-gray-700">Paquete #{{ $paquete->id }} - Peso: {{ $paquete->peso }} kg</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Botón para regresar -->
        <div class="mt-6">
            <a href="{{ route('salidas.index') }}"
                class="bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition duration-300 ease-in-out">
                Regresar al listado de salidas
            </a>
        </div>
    </div>
</x-app-layout>
