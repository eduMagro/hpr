@props(['etiqueta', 'planilla', 'elementos', 'maquina'])

<div x-data="{ open: false }">
    <button @click="open = true" class="btn btn-blue">Ver</button>

    <div x-show="open" x-transition class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-4 max-w-4xl w-full relative shadow-lg overflow-y-auto max-h-screen">
            <button @click="open = false" class="absolute top-2 right-2 text-red-600">✖</button>

            <div class="p-2">
                <h2 class="text-lg font-semibold text-gray-900">
                    <span>{{ $planilla->obra->obra }}</span> -
                    <span>{{ $planilla->cliente->empresa }}</span><br>
                    <span>{{ $planilla->codigo_limpio }}</span> - S:{{ $planilla->seccion }}
                </h2>

                <h3 class="text-lg font-semibold text-gray-900">
                    <span class="text-blue-700">{{ $etiqueta->etiqueta_sub_id }}</span>
                    {{ $etiqueta->nombre }} -
                    <span>Cal: B500SD</span> -
                    {{ $etiqueta->peso_kg ?? 'N/A' }}
                </h3>
            </div>

            <div id="canvas-container-modal-{{ $etiqueta->id }}">
                <canvas id="canvas-etiqueta-modal-{{ $etiqueta->id }}"></canvas>
            </div>
        </div>
    </div>
</div>
