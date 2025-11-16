<x-app-layout>
    <x-slot name="title">Etiquetas - {{ config('app.name') }}</x-slot>

    <div class="w-full p-4 sm:p-2">
        @livewire('etiquetas-table')
    </div>
</x-app-layout>
