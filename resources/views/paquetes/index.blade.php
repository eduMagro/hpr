<x-app-layout>
    <x-slot name="title">Paquetes - {{ config('app.name') }}</x-slot>

    @livewire('paquetes-table')
</x-app-layout>
