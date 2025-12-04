<x-app-layout>
    <x-slot name="title">Coladas - {{ config('app.name') }}</x-slot>

    <div class="w-full p-4 sm:p-2">
        @livewire('coladas-table')
    </div>
</x-app-layout>
