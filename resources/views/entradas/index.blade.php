<x-app-layout>
    <x-slot name="title">Entradas - {{ config('app.name') }}</x-slot>

    <div class="w-full p-4 sm:p-4">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold text-gray-800">Entradas de Material</h1>
            <a href="{{ route('entradas.create') }}"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
                Nueva Entrada
            </a>
        </div>
        @livewire('entradas-table')
    </div>

    <script src="{{ asset('js/imprimirQrAndroid.js') }}"></script>
</x-app-layout>
