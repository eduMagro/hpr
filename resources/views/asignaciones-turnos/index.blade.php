<x-app-layout>
    <x-slot name="title">Asignaciones de Turnos</x-slot>

    <x-page-header
        title="Asignaciones de Turnos"
        subtitle="Gestión de turnos y horarios del personal"
        icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
    />

    <div class="w-full px-6 py-4">
        @livewire('asignaciones-turnos-table')
    </div>
</x-app-layout>
