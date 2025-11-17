<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Ubicaciones') }} wire:navigate
        </h2>
    </x-slot>

    <div class="max-w-xl mx-auto mt-10 bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-center text-lg sm:text-xl font-semibold text-gray-800 mb-6">Crear Ubicación</h2>

        <form action="{{ route('ubicaciones.store') }}" method="POST" class="space-y-4">
            @csrf

            {{-- Almacén --}}
            <x-tabla.select name="almacen" :options="['0A' => '0A', '0B' => '0B', '0C' => '0C']" label="Almacén" placeholder="Selecciona el almacén" />

            {{-- Sector --}}
            <x-tabla.select name="sector" :options="collect(range(1, 20))
                ->mapWithKeys(fn($i) => [str_pad($i, 2, '0', STR_PAD_LEFT) => str_pad($i, 2, '0', STR_PAD_LEFT)])
                ->toArray()" label="Sector" placeholder="Selecciona el sector" />

            {{-- Ubicación --}}
            <x-tabla.select name="ubicacion" :options="collect(range(1, 100))
                ->mapWithKeys(fn($i) => [str_pad($i, 2, '0', STR_PAD_LEFT) => str_pad($i, 2, '0', STR_PAD_LEFT)])
                ->toArray()" label="Ubicación"
                placeholder="Selecciona la ubicación" />

            {{-- Descripción --}}
            <x-tabla.input name="descripcion" label="Descripción" placeholder="Ej. Zona de materiales largos" />

            {{-- Botón --}}
            <div class="text-center pt-4">
                <x-tabla.boton-azul>
                    Crear Ubicación
                </x-tabla.boton-azul>
            </div>
        </form>
    </div>
</x-app-layout>
