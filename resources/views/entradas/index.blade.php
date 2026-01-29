<x-app-layout>
    <x-slot name="title">Entradas - {{ config('app.name') }}</x-slot>

    <x-page-header
        title="Entradas de Material"
        subtitle="Registro y control de entradas al almacén"
        icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20"/></svg>'
    />

    <div class="w-full p-4 sm:p-4">
        @livewire('entradas-table')
    </div>

    <script src="{{ asset('js/imprimirQrAndroid.js') }}"></script>
</x-app-layout>
