<x-app-layout>
    <x-slot name="title">Entradas - {{ config('app.name') }}</x-slot>

    <div class="w-full h-[calc(100vh-180px)] p-4 sm:p-4">
        @livewire('entradas-table')
    </div>

    <script src="{{ asset('js/imprimirQrAndroid.js') }}"></script>
</x-app-layout>
