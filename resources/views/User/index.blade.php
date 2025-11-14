<x-app-layout>
    <x-slot name="title">Usuarios - {{ config('app.name') }}</x-slot>

    @livewire('users-table')
</x-app-layout>
