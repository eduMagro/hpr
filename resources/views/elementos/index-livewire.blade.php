<x-app-layout>
    <x-slot name="title">Elementos - {{ config('app.name') }}</x-slot>

    <div class="w-full sm:p-2">
        @livewire('elementos-table')
    </div>
</x-app-layout>
