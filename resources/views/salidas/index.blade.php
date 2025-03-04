<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ __('Listado de Salidas') }}
        </h2>
    </x-slot>

    <div class="container mx-auto p-6">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h1 class="text-2xl font-bold mb-4 text-black">Listado de Salidas</h1>

            <div class="space-y-4">
                @foreach ($salidas as $salida)
                    <div class="bg-gray-100 p-4 rounded-lg">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                <label class="font-semibold text-lg text-gray-800">
                                    Salida #{{ $salida->id }}
                                </label>
                            </div>
                            <span class="text-sm text-gray-600">Fecha: {{ $salida->created_at }}</span>
                        </div>

                        <p class="text-gray-700">Camión: {{ $salida->camion->modelo }} -
                            {{ $salida->camion->matricula }}</p>

                        <h3 class="font-semibold text-md text-gray-800 mt-4">Paquetes Asociados:</h3>
                        <div class="space-y-2 mt-2">
                            @foreach ($salida->paquetes as $paquete)
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-700">Paquete #{{ $paquete->id }} - Peso:
                                        {{ $paquete->peso }} kg</span>
                                    <span class="text-gray-600">Código: {{ $paquete->codigo_limpio }}</span>
                                </div>
                            @endforeach
                        </div>

                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
