<x-app-layout>
    <x-slot name="title">Detalles de la Obra - {{ $obra->obra }}</x-slot>

    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-bold mb-4">{{ $obra->obra }}</h2>

        <p><strong>Código de Obra:</strong> {{ $obra->cod_obra }}</p>
        <p><strong>Cliente:</strong> {{ $obra->cliente }}</p>
        <p><strong>Código Cliente:</strong> {{ $obra->cod_cliente ?? 'N/A' }}</p>

        <p><strong>Latitud:</strong> {{ $obra->latitud }}</p>
        <p><strong>Longitud:</strong> {{ $obra->longitud }}</p>

        <!-- Mapa de Google -->
        <div class="mt-4">
            <h3 class="text-lg font-semibold">Ubicación en Google Maps</h3>
            <iframe 
                width="100%" 
                height="300" 
                style="border:0;" 
                loading="lazy" 
                allowfullscreen 
                referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps/embed/v1/place?key=TU_API_KEY&q={{ $obra->latitud }},{{ $obra->longitud }}">
            </iframe>
        </div>

        <div class="mt-4">
            <a href="https://www.google.com/maps?q={{ $obra->latitud }},{{ $obra->longitud }}" 
               target="_blank" 
               class="text-blue-500 hover:underline">
                Abrir en Google Maps
            </a>
        </div>

        <div class="mt-6 flex justify-end">
            <a href="{{ route('obras.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                Volver
            </a>
        </div>
    </div>
</x-app-layout>
