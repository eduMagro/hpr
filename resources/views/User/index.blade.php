<x-app-layout>
    <x-slot name="title">Usuarios - {{ config('app.name') }}</x-slot>

    <x-page-header
        title="Gestión de Usuarios"
        subtitle="Administra los usuarios y sus permisos"
        icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>'
    />

    @livewire('users-table')
    @livewire('users-table-mobile')
</x-app-layout>