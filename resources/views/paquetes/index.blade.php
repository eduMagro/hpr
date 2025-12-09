<x-app-layout>
    <x-slot name="title">Paquetes - {{ config('app.name') }}</x-slot>
    {{-- BOTÓN PARA IR AL MAPA DE LOCALIZACIONES --}}
    <div class="mb-4 flex justify-end">
        <a href="{{ route('mapa.paquetes', ['obra' => request('nave')]) }}" wire:navigate="false"
            class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-tr from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold rounded-lg shadow-sm transition-all duration-150">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            <span class="text-sm">Ver mapa de localizaciones</span>
        </a>
    </div>
    @livewire('paquetes-table')
</x-app-layout>
